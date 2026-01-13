<?php

namespace App\Services\ConductorImport;

use App\Models\Conductor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

/**
 * Procesador de archivos CSV y Excel para importación de conductores
 *
 * Maneja el procesamiento optimizado de archivos CSV (línea por línea) y Excel (en memoria).
 * Implementa detección automática de delimitadores, validación de estructura, procesamiento
 * en lotes y manejo de transacciones de base de datos.
 */
class ConductorImportFileProcessor
{
    /**
     * @param  ConductorImportDataTransformer  $transformer  Transformador de datos de filas
     * @param  ConductorImportProgressTracker  $progressTracker  Seguimiento de progreso
     */
    public function __construct(
        private ConductorImportDataTransformer $transformer,
        private ConductorImportProgressTracker $progressTracker
    ) {}

    /**
     * Procesa un archivo CSV optimizado línea por línea
     *
     * Este método procesa archivos CSV de manera eficiente, leyendo línea por línea
     * sin cargar todo el archivo en memoria. Detecta automáticamente el delimitador
     * (coma o punto y coma), valida la estructura, procesa en lotes y mantiene
     * transacciones de base de datos para consistencia.
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $fullPath  Ruta completa al archivo CSV
     * @param  callable  $progressCallback  Función callback para actualizar progreso: callable(array<string, mixed>): void
     * @return array{
     *     importados: int,
     *     duplicados: int,
     *     errores: array<int, string>,
     *     total: int
     * }
     *
     * @throws \Exception Si no se puede abrir el archivo, leer encabezados o faltan columnas requeridas
     */
    public function processCsv(
        string $sessionId,
        string $fullPath,
        callable $progressCallback
    ): array {
        try {
            // Detectar delimitador
            $handle = fopen($fullPath, 'r');
            if (! $handle) {
                throw new \Exception('No se pudo abrir el archivo CSV');
            }

            $firstLine = fgets($handle);
            rewind($handle);

            $comas = substr_count($firstLine, ',');
            $puntoComas = substr_count($firstLine, ';');
            $delimiter = ($puntoComas > $comas) ? ';' : ',';

            Log::info('CSV detectado (modo optimizado)', [
                'delimiter' => $delimiter,
                'comas' => $comas,
                'puntoComas' => $puntoComas,
            ]);

            $progressCallback([
                'progreso' => 7,
                'mensaje' => 'CSV detectado. Delimitador: '.($delimiter === ';' ? 'punto y coma (;)' : 'coma (,)'),
                'log' => [['mensaje' => '🚀 Modo optimizado activado para archivos grandes', 'tipo' => 'info']],
            ]);

            // Leer encabezados
            $headers = fgetcsv($handle, 0, $delimiter, '"');
            if (! $headers || empty($headers)) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                throw new \Exception('No se pudieron leer los encabezados del archivo');
            }

            // Limpiar encabezados: quitar BOM y espacios
            $headers = array_map(function ($header) {
                // Quitar BOM (Byte Order Mark) UTF-8
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

                return trim($header);
            }, $headers);

            // Normalizar a mayúsculas para comparación
            $headersUpper = array_map('strtoupper', $headers);

            Log::info('Encabezados CSV detectados', [
                'headers' => $headers,
                'delimiter' => $delimiter,
            ]);

            // Crear mapeo de columnas
            $columnMapping = $this->transformer->getColumnMapping();
            $indexMap = $this->transformer->createIndexMap($headers, $columnMapping);

            // Validar columnas requeridas
            $validation = $this->transformer->validateRequiredFields($indexMap, $headers);
            if (! $validation['valid']) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                $columnasEncontradas = implode(', ', $headers);
                throw new \Exception('Faltan columnas requeridas: '.implode(', ', $validation['missing']).'. Columnas encontradas en el archivo: '.$columnasEncontradas);
            }

            // Cerrar el handle actual después de leer los headers
            fclose($handle);
            $handle = null;

            $progressCallback([
                'progreso' => 15,
                'mensaje' => 'Archivo CSV listo. Iniciando procesamiento...',
                'log' => [['mensaje' => '✓ Archivo CSV preparado. Iniciando procesamiento línea por línea...', 'tipo' => 'success']],
            ]);

            // Reabrir el archivo para procesar
            $handle = fopen($fullPath, 'r');
            if (! $handle) {
                throw new \Exception('No se pudo reabrir el archivo CSV para procesamiento');
            }

            // Saltar la línea de headers
            fgetcsv($handle, 0, $delimiter, '"');

            // Procesar línea por línea
            return $this->processCsvRecords($sessionId, $handle, $delimiter, $indexMap, $progressCallback);

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Procesar registros CSV línea por línea
     */
    private function processCsvRecords(
        string $sessionId,
        $handle,
        string $delimiter,
        array $indexMap,
        callable $progressCallback
    ): array {
        try {
            $registrosProcesados = 0;
            $registrosValidos = 0;
            $importados = 0;
            $duplicados = 0;
            $errores = [];
            $loteSize = 10;
            $procesadosEnLote = 0;
            $lineaActual = 1;

            $progressCallback([
                'progreso' => 18,
                'mensaje' => 'Iniciando procesamiento línea por línea...',
                'log' => [['mensaje' => '🔄 Modo optimizado: procesando línea por línea sin cargar todo en memoria', 'tipo' => 'info']],
            ]);

            DB::beginTransaction();

            if (! is_resource($handle)) {
                throw new \Exception('El handle del archivo no es válido');
            }

            while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
                $lineaActual++;

                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                $registrosValidos++;
                $registrosProcesados++;

                // Procesar registro
                $resultado = $this->processRecord($row, $indexMap, $lineaActual);

                if ($resultado['tipo'] === 'importado') {
                    $importados++;
                } elseif ($resultado['tipo'] === 'duplicado') {
                    $duplicados++;
                    $errores[] = $resultado['mensaje'];
                } elseif ($resultado['tipo'] === 'error') {
                    $errores[] = $resultado['mensaje'];
                }

                $procesadosEnLote++;

                // Actualizar progreso cada lote o cada 3 registros
                if ($procesadosEnLote >= $loteSize || $registrosProcesados % 3 == 0) {
                    $progresoIncremental = min(20 + (($registrosProcesados * 0.5)), 90);

                    $progressCallback([
                        'progreso' => $progresoIncremental,
                        'procesados' => $registrosProcesados,
                        'total' => null,
                        'importados' => $importados,
                        'duplicados' => $duplicados,
                        'mensaje' => "Procesando... {$registrosProcesados} registros procesados (Importados: {$importados}, Duplicados: {$duplicados}, Errores: ".count($errores).')',
                        'log' => $procesadosEnLote >= $loteSize ? [['mensaje' => "📦 Lote completado: {$procesadosEnLote} registros. Total procesados: {$registrosProcesados}", 'tipo' => 'info']] : [],
                    ]);

                    if ($procesadosEnLote >= $loteSize) {
                        $procesadosEnLote = 0;
                    }
                }
            }

            if (is_resource($handle)) {
                fclose($handle);
                $handle = null;
            }

            DB::commit();

            $totalProcesado = $registrosProcesados;

            $progressCallback([
                'estado' => 'completado',
                'progreso' => 100,
                'procesados' => $registrosProcesados,
                'total' => $totalProcesado,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'mensaje' => "Importación completada: {$importados} importados, {$duplicados} duplicados, ".count($errores)." errores de {$totalProcesado} registros procesados",
                'log' => [['mensaje' => "✅ Importación completada exitosamente: {$importados} importados, {$duplicados} duplicados, ".count($errores).' errores', 'tipo' => 'success']],
            ]);

            return [
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'total' => $totalProcesado,
            ];

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
            }
            DB::rollBack();

            $progressCallback([
                'estado' => 'error',
                'error' => 'Error durante la importación: '.$e->getMessage(),
                'log' => [['mensaje' => '✗ Error: '.$e->getMessage(), 'tipo' => 'error']],
            ]);

            throw $e;
        }
    }

    /**
     * Procesar un registro individual
     */
    private function processRecord(array $row, array $indexMap, int $lineaNumero): array
    {
        try {
            // Transformar datos
            $data = $this->transformer->transformRow($row, $indexMap);

            // Validar cédula
            if (empty($data['cedula'])) {
                return [
                    'tipo' => 'error',
                    'mensaje' => "Fila {$lineaNumero}: Cédula requerida",
                ];
            }

            // Verificar duplicados
            $conductorExistente = Conductor::where('cedula', $data['cedula'])->first();
            if ($conductorExistente) {
                return [
                    'tipo' => 'duplicado',
                    'mensaje' => "Fila {$lineaNumero}: Conductor duplicado - Cédula {$data['cedula']} ya existe",
                ];
            }

            // Crear conductor
            Conductor::create($data);

            return [
                'tipo' => 'importado',
                'mensaje' => "Fila {$lineaNumero}: Conductor importado exitosamente",
            ];

        } catch (\Illuminate\Database\QueryException $e) {
            $errorMsg = $this->cleanErrorMessage($e->getMessage());

            return [
                'tipo' => 'error',
                'mensaje' => "Fila {$lineaNumero}: Error al crear conductor - ".$errorMsg,
            ];
        } catch (\Exception $e) {
            return [
                'tipo' => 'error',
                'mensaje' => "Fila {$lineaNumero}: ".$e->getMessage(),
            ];
        }
    }

    /**
     * Procesa un archivo Excel (.xlsx) cargándolo completamente en memoria
     *
     * Este método procesa archivos Excel usando PhpSpreadsheet, cargando todo el archivo
     * en memoria. Valida la estructura, procesa registros en lotes y mantiene transacciones
     * de base de datos. Adecuado para archivos pequeños/medianos (<10MB recomendado).
     *
     * @param  string  $sessionId  Identificador único de la sesión de importación
     * @param  string  $fullPath  Ruta completa al archivo Excel (.xlsx)
     * @param  callable  $progressCallback  Función callback para actualizar progreso: callable(array<string, mixed>): void
     * @return array{
     *     importados: int,
     *     duplicados: int,
     *     errores: array<int, string>,
     *     total: int
     * }
     *
     * @throws \Exception Si no se puede cargar el archivo, faltan columnas requeridas o hay errores de procesamiento
     */
    public function processExcel(
        string $sessionId,
        string $fullPath,
        callable $progressCallback
    ): array {
        try {
            $reader = new XlsxReader;

            $progressCallback([
                'progreso' => 8,
                'mensaje' => 'Leyendo archivo Excel...',
                'log' => [['mensaje' => '📖 Cargando archivo Excel en memoria...', 'tipo' => 'info']],
            ]);

            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $progressCallback([
                'progreso' => 10,
                'mensaje' => 'Archivo cargado correctamente...',
                'log' => [['mensaje' => '✓ Archivo cargado correctamente', 'tipo' => 'success']],
            ]);

            $rows = $worksheet->toArray();

            $progressCallback([
                'progreso' => 15,
                'mensaje' => 'Datos extraídos: '.count($rows).' filas encontradas',
                'log' => [['mensaje' => '✓ Datos extraídos: '.count($rows).' filas encontradas', 'tipo' => 'success']],
            ]);

            if (count($rows) < 2) {
                $progressCallback([
                    'estado' => 'error',
                    'error' => 'El archivo está vacío o no tiene datos.',
                ]);

                return ['importados' => 0, 'duplicados' => 0, 'errores' => [], 'total' => 0];
            }

            $totalFilas = count($rows) - 1;

            // Obtener encabezados de la primera fila
            $headers = array_map('trim', array_map('strtoupper', $rows[0]));

            // Crear mapeo de índices
            $columnMapping = $this->transformer->getColumnMapping();
            $indexMap = $this->transformer->createIndexMap($headers, $columnMapping);

            // Validar columnas requeridas
            $validation = $this->transformer->validateRequiredFields($indexMap, $headers);
            if (! $validation['valid']) {
                $columnName = array_search($validation['missing'][0], array_flip($columnMapping));
                $errorMsg = 'Falta la columna requerida: '.($columnName ?: $validation['missing'][0]);
                $progressCallback([
                    'estado' => 'error',
                    'error' => $errorMsg,
                ]);

                return ['importados' => 0, 'duplicados' => 0, 'errores' => [], 'total' => 0];
            }

            // Contar registros válidos
            $progressCallback([
                'progreso' => 18,
                'mensaje' => 'Analizando registros válidos...',
                'log' => [['mensaje' => 'Validando estructura de datos y contando registros', 'tipo' => 'info']],
            ]);

            $registrosValidos = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (! empty(array_filter($row))) {
                    $registrosValidos++;
                }
            }

            if ($registrosValidos == 0) {
                $progressCallback([
                    'estado' => 'error',
                    'error' => 'No se encontraron registros válidos para procesar.',
                ]);

                return ['importados' => 0, 'duplicados' => 0, 'errores' => [], 'total' => 0];
            }

            $progressCallback([
                'progreso' => 20,
                'total' => $registrosValidos,
                'mensaje' => "✅ Análisis completado: {$registrosValidos} registros válidos encontrados",
                'log' => [
                    ['mensaje' => "📊 Análisis completado: {$registrosValidos} registros válidos encontrados de {$totalFilas} filas totales", 'tipo' => 'success'],
                    ['mensaje' => '⚙️ El procesamiento se realizará en lotes de 10 registros para mejor rendimiento', 'tipo' => 'info'],
                ],
            ]);

            usleep(1000000); // 1 segundo

            DB::beginTransaction();

            $importados = 0;
            $errores = [];
            $duplicados = 0;
            $loteSize = 10;
            $procesadosEnLote = 0;
            $registrosProcesados = 0;

            $progressCallback([
                'progreso' => 22,
                'mensaje' => '🚀 Iniciando procesamiento por lotes...',
                'log' => [
                    ['mensaje' => '🔄 Iniciando procesamiento de registros...', 'tipo' => 'info'],
                    ['mensaje' => "📦 Configuración: Lotes de {$loteSize} registros", 'tipo' => 'info'],
                ],
            ]);

            // Procesar cada fila
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                $registrosProcesados++;

                $resultado = $this->processRecord($row, $indexMap, $i + 1);

                if ($resultado['tipo'] === 'importado') {
                    $importados++;
                } elseif ($resultado['tipo'] === 'duplicado') {
                    $duplicados++;
                    $errores[] = $resultado['mensaje'];
                } elseif ($resultado['tipo'] === 'error') {
                    $errores[] = $resultado['mensaje'];
                }

                $procesadosEnLote++;

                // Actualizar progreso
                if ($procesadosEnLote >= $loteSize || $i == count($rows) - 1) {
                    $progreso = 20 + (($registrosProcesados / $registrosValidos) * 70);

                    $progressCallback([
                        'progreso' => min($progreso, 90),
                        'procesados' => $registrosProcesados,
                        'importados' => $importados,
                        'duplicados' => $duplicados,
                        'mensaje' => "Procesando... {$registrosProcesados} de {$registrosValidos} registros (Importados: {$importados}, Duplicados: {$duplicados}, Errores: ".count($errores).')',
                        'log' => [['mensaje' => "📦 Lote completado: {$procesadosEnLote} registros procesados. Progreso: {$registrosProcesados}/{$registrosValidos} ({(round($progreso))}%)", 'tipo' => 'info']],
                    ]);

                    $procesadosEnLote = 0;
                } elseif ($registrosProcesados % 3 == 0) {
                    $progreso = 20 + (($registrosProcesados / $registrosValidos) * 70);
                    $progressCallback([
                        'progreso' => min($progreso, 90),
                        'procesados' => $registrosProcesados,
                        'mensaje' => "Procesando registro {$registrosProcesados} de {$registrosValidos}...",
                    ]);
                }

                // Liberar memoria cada 50 registros
                if ($i % 50 == 0) {
                    gc_collect_cycles();
                }
            }

            DB::commit();

            $mensaje = "Importación completada. Importados: {$importados}";
            if ($duplicados > 0) {
                $mensaje .= ", Duplicados omitidos: {$duplicados}";
            }
            if (count($errores) > 0) {
                $mensaje .= ', Errores: '.count($errores);
            }

            $progressCallback([
                'estado' => 'completado',
                'progreso' => 100,
                'procesados' => $registrosValidos,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'mensaje' => $mensaje,
                'log' => [['mensaje' => $mensaje, 'tipo' => 'success']],
            ]);

            return [
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'total' => $registrosValidos,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error procesando archivo de importación: '.$e->getMessage(), [
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            $progressCallback([
                'estado' => 'error',
                'error' => 'Error al importar archivo: '.$e->getMessage(),
                'log' => [['mensaje' => 'Error: '.$e->getMessage(), 'tipo' => 'error']],
            ]);

            throw $e;
        }
    }

    /**
     * Limpiar mensajes de error
     */
    private function cleanErrorMessage(string $mensaje): string
    {
        // Quitar paths de Windows (C:\...)
        $mensaje = preg_replace('/C:\\\\[^\s,]+/i', '', $mensaje);

        // Quitar paths Unix (/...)
        $mensaje = preg_replace('/\/[^\s,]+/i', '', $mensaje);

        // Quitar objetos JSON complejos
        $mensaje = preg_replace('/\{[^}]+\}/', '', $mensaje);

        // Quitar detalles de conexión de base de datos
        $mensaje = preg_replace('/\(Connection: [^)]+\)/i', '', $mensaje);
        $mensaje = preg_replace('/Host: [^,]+/i', '', $mensaje);
        $mensaje = preg_replace('/Port: \d+/i', '', $mensaje);
        $mensaje = preg_replace('/Database: [^,]+/i', '', $mensaje);

        // Limpiar espacios múltiples
        $mensaje = preg_replace('/\s+/', ' ', $mensaje);

        // Quitar prefijos técnicos comunes
        $mensaje = str_replace('SQLSTATE[', '', $mensaje);
        $mensaje = preg_replace('/^\d+:\s*/', '', $mensaje);

        return trim($mensaje);
    }
}
