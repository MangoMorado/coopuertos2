<?php

namespace App\Jobs;

use App\Models\Conductor;
use App\Models\ImportLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProcesarImportacionConductores implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    protected $filePath;

    protected $extension;

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId, string $filePath, string $extension, int $userId)
    {
        $this->sessionId = $sessionId;
        $this->filePath = $filePath;
        $this->extension = $extension;
        $this->userId = $userId;

        // Configurar timeout para archivos grandes
        $this->timeout = 600; // 10 minutos
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Aumentar límites para archivos grandes
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $importLog = ImportLog::where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->first();

        if (! $importLog) {
            \Log::error("ImportLog no encontrado para procesar: {$this->sessionId}");

            return;
        }

        try {
            // Actualizar estado a procesando
            $importLog->update([
                'estado' => 'procesando',
                'progreso' => 5,
                'mensaje' => 'Preparando lectura del archivo...',
                'started_at' => now(),
                'logs' => [['mensaje' => 'Iniciando procesamiento en segundo plano...', 'tipo' => 'info']],
            ]);

            // Determinar ruta completa del archivo
            $fullPath = storage_path('app/'.$this->filePath);

            if (! file_exists($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}");
            }

            // Procesar según extensión
            if ($this->extension === 'csv') {
                $this->procesarCSV($importLog, $fullPath);
            } else {
                $this->procesarExcel($importLog, $fullPath);
            }

            // Marcar como completado
            $importLog->update([
                'estado' => 'completado',
                'progreso' => 100,
                'completed_at' => now(),
                'mensaje' => "Importación completada: {$importLog->importados} importados, {$importLog->duplicados} duplicados, {$importLog->errores_count} errores",
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✅ Importación completada exitosamente', 'tipo' => 'success']]),
            ]);

            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

        } catch (\Exception $e) {
            \Log::error('Error en Job de importación: '.$e->getMessage(), [
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString(),
            ]);

            $importLog->update([
                'estado' => 'error',
                'mensaje' => 'Error: '.$e->getMessage(),
                'completed_at' => now(),
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✗ Error: '.$e->getMessage(), 'tipo' => 'error']]),
            ]);

            throw $e;
        }
    }

    /**
     * Procesar archivo CSV
     */
    private function procesarCSV(ImportLog $importLog, string $fullPath): void
    {
        // Usar la misma lógica del controlador pero actualizando ImportLog en lugar de sesión
        // Por ahora, moveré la lógica del método procesarCSVOptimizado aquí
        // Para mantener el código simple, llamaré a un método helper que ya existe en el controlador
        // Pero como es un Job separado, necesito implementar la lógica aquí o extraerla a un servicio

        // Por ahora, implementación simplificada que actualiza ImportLog directamente
        $handle = fopen($fullPath, 'r');
        if (! $handle) {
            throw new \Exception('No se pudo abrir el archivo CSV');
        }

        try {
            // Detectar delimitador
            $firstLine = fgets($handle);
            rewind($handle);
            $comas = substr_count($firstLine, ',');
            $puntoComas = substr_count($firstLine, ';');
            $delimiter = ($puntoComas > $comas) ? ';' : ',';

            // Leer encabezados
            $headers = fgetcsv($handle, 0, $delimiter, '"');
            $headers = array_map(function ($h) {
                return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h));
            }, $headers);
            $headersUpper = array_map('strtoupper', $headers);

            // Mapeo de columnas
            $columnMapping = [
                'NOMBRES' => 'nombres',
                'APELLIDOS' => 'apellidos',
                'CEDULA' => 'cedula',
                'CÉDULA' => 'cedula',
                'CONDUCTOR TIPO' => 'conductor_tipo',
                'RH' => 'rh',
                'VEHICULO PLACA' => 'vehiculo',
                'NUMERO INTERNO' => 'numero_interno',
                'CELULAR' => 'celular',
                'CORREO' => 'correo',
                'FECHA DE NACIMIENTO' => 'fecha_nacimiento',
                '¿SABE OTRA PROFESIÓN A PARTE DE SER CONDUCTOR?' => 'otra_profesion',
                'CARGUE SU FOTO PARA CARNET' => 'foto',
                'NIVEL DE ESTUDIOS' => 'nivel_estudios',
            ];

            $indexMap = [];
            foreach ($columnMapping as $header => $field) {
                $index = array_search(strtoupper($header), $headersUpper);
                if ($index !== false) {
                    $indexMap[$field] = $index;
                }
            }

            // Validar columnas requeridas
            $requiredFields = ['nombres', 'apellidos', 'cedula'];
            foreach ($requiredFields as $field) {
                if (! isset($indexMap[$field])) {
                    throw new \Exception("Falta la columna requerida: {$field}");
                }
            }

            // Procesar registros
            $this->procesarRegistrosCSV($importLog, $handle, $delimiter, $indexMap);

        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /**
     * Procesar registros CSV línea por línea
     */
    private function procesarRegistrosCSV(ImportLog $importLog, $handle, string $delimiter, array $indexMap): void
    {
        $registrosProcesados = 0;
        $importados = 0;
        $duplicados = 0;
        $errores = [];
        $logs = $importLog->logs ?? [];

        // Contar total de líneas primero para calcular progreso
        $totalLineas = 0;
        $posicionActual = ftell($handle);
        while (fgetcsv($handle, 0, $delimiter, '"') !== false) {
            $totalLineas++;
        }
        fseek($handle, $posicionActual);

        $this->agregarLog($importLog, "📊 Total de registros a procesar: {$totalLineas}", 'info');

        // No usar transacción global, procesamos uno por uno
        try {
            while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                $registrosProcesados++;
                $lineaNumero = $registrosProcesados;

                // Log de inicio de procesamiento del registro
                $this->agregarLog($importLog, "🔄 Procesando registro #{$lineaNumero}...", 'info');

                // Procesar registro (usar método similar al del controlador)
                $resultado = $this->procesarRegistro($importLog, $row, $indexMap, $lineaNumero, $totalLineas);

                if ($resultado['tipo'] === 'importado') {
                    $importados++;
                    $this->agregarLog($importLog, "✅ Registro #{$lineaNumero}: Importado exitosamente - {$resultado['mensaje']}", 'success');
                } elseif ($resultado['tipo'] === 'duplicado') {
                    $duplicados++;
                    $errores[] = $resultado['mensaje'];
                    $this->agregarLog($importLog, "⚠️ Registro #{$lineaNumero}: {$resultado['mensaje']}", 'warning');
                } elseif ($resultado['tipo'] === 'error') {
                    $errores[] = $resultado['mensaje'];
                    $this->agregarLog($importLog, "❌ Registro #{$lineaNumero}: {$resultado['mensaje']}", 'error');
                }

                // Actualizar progreso DESPUÉS DE CADA REGISTRO
                $progreso = $totalLineas > 0 ? min(95, (($registrosProcesados / $totalLineas) * 95)) : 0;

                $this->actualizarImportLog($importLog, [
                    'procesados' => $registrosProcesados,
                    'total' => $totalLineas,
                    'importados' => $importados,
                    'duplicados' => $duplicados,
                    'errores_count' => count($errores),
                    'errores' => array_slice($errores, -50), // Últimos 50 errores
                    'progreso' => $progreso,
                    'mensaje' => "Procesando registro {$registrosProcesados} de {$totalLineas} (Importados: {$importados}, Duplicados: {$duplicados}, Errores: ".count($errores).')',
                ]);

                // Pequeña pausa para no saturar la base de datos
                usleep(100000); // 0.1 segundos
            }

            // Actualización final
            $this->actualizarImportLog($importLog, [
                'procesados' => $registrosProcesados,
                'total' => $totalLineas,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores_count' => count($errores),
                'errores' => $errores,
                'progreso' => 100,
            ]);

            $this->agregarLog($importLog, "✅ Procesamiento completado: {$importados} importados, {$duplicados} duplicados, ".count($errores).' errores', 'success');

        } catch (\Exception $e) {
            $this->agregarLog($importLog, '❌ Error crítico: '.$e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Procesar un registro individual
     */
    private function procesarRegistro(ImportLog $importLog, array $row, array $indexMap, int $lineaNumero, int $totalLineas): array
    {
        try {
            // Extraer datos según el mapeo
            $data = [];
            $this->agregarLog($importLog, "  📝 Extrayendo datos del registro #{$lineaNumero}...", 'info');

            foreach ($indexMap as $field => $index) {
                $value = $row[$index] ?? null;

                if ($value !== null) {
                    $value = trim((string) $value);

                    // Procesar según el campo (lógica simplificada del controlador)
                    switch ($field) {
                        case 'cedula':
                            $data['cedula'] = $value;
                            $this->agregarLog($importLog, "    ✓ Cédula: {$value}", 'info');
                            break;
                        case 'nombres':
                            $data['nombres'] = ucwords(strtolower($value));
                            $this->agregarLog($importLog, "    ✓ Nombres: {$data['nombres']}", 'info');
                            break;
                        case 'apellidos':
                            $data['apellidos'] = ucwords(strtolower($value));
                            $this->agregarLog($importLog, "    ✓ Apellidos: {$data['apellidos']}", 'info');
                            break;
                        case 'conductor_tipo':
                            $normalizedValue = strtoupper(trim($value));
                            if (strpos($normalizedValue, 'TIPO A') !== false ||
                                strpos($normalizedValue, 'CAMIONETAS') !== false ||
                                $normalizedValue === 'A') {
                                $data['conductor_tipo'] = 'A';
                            } else {
                                $data['conductor_tipo'] = 'B';
                            }
                            $this->agregarLog($importLog, "    ✓ Tipo Conductor: {$data['conductor_tipo']}", 'info');
                            break;
                        case 'rh':
                            $rhValue = strtoupper(str_replace(' ', '', trim($value)));
                            $rhValidos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            if (in_array($rhValue, $rhValidos)) {
                                $data['rh'] = $rhValue;
                            } else {
                                $data['rh'] = null;
                            }
                            $this->agregarLog($importLog, '    ✓ RH: '.($data['rh'] ?? 'No válido'), 'info');
                            break;
                        case 'vehiculo':
                            $vehiculoValue = trim((string) $value);
                            $data['vehiculo'] = ! empty($vehiculoValue) ? strtoupper($vehiculoValue) : null;
                            $this->agregarLog($importLog, '    ✓ Vehículo: '.($data['vehiculo'] ?? 'No especificado'), 'info');
                            break;
                        case 'numero_interno':
                            $data['numero_interno'] = $value;
                            $this->agregarLog($importLog, "    ✓ Número Interno: {$value}", 'info');
                            break;
                        case 'celular':
                            $data['celular'] = $value;
                            $this->agregarLog($importLog, "    ✓ Celular: {$value}", 'info');
                            break;
                        case 'correo':
                            $data['correo'] = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
                            $this->agregarLog($importLog, '    ✓ Correo: '.($data['correo'] ?? 'No válido'), 'info');
                            break;
                        case 'fecha_nacimiento':
                            $data['fecha_nacimiento'] = $this->parseFecha($value);
                            $this->agregarLog($importLog, '    ✓ Fecha Nacimiento: '.($data['fecha_nacimiento'] ?? 'No válida'), 'info');
                            break;
                        case 'otra_profesion':
                            $data['otra_profesion'] = $value;
                            $this->agregarLog($importLog, "    ✓ Otra Profesión: {$value}", 'info');
                            break;
                        case 'nivel_estudios':
                            $data['nivel_estudios'] = $value;
                            $this->agregarLog($importLog, "    ✓ Nivel Estudios: {$value}", 'info');
                            break;
                        case 'foto':
                            // Descargar imagen de Google Drive
                            if (! empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                                $this->agregarLog($importLog, "    📷 URL de foto detectada: {$value}", 'info');
                                $this->agregarLog($importLog, '    ⬇️ Descargando imagen desde Google Drive...', 'info');
                                $fotoPath = $this->downloadImageFromDrive($importLog, $value, $lineaNumero);
                                if ($fotoPath) {
                                    $data['foto'] = $fotoPath;
                                    $this->agregarLog($importLog, "    ✅ Foto descargada y guardada: {$fotoPath}", 'success');
                                } else {
                                    $this->agregarLog($importLog, '    ⚠️ No se pudo descargar la foto desde Google Drive', 'warning');
                                }
                            } else {
                                $this->agregarLog($importLog, '    ℹ️ No hay URL de foto o URL inválida', 'info');
                            }
                            break;
                    }
                }
            }

            // Validar cédula
            if (empty($data['cedula'])) {
                $this->agregarLog($importLog, '  ❌ Validación fallida: Cédula requerida', 'error');

                return [
                    'tipo' => 'error',
                    'mensaje' => 'Cédula requerida',
                ];
            }

            // Verificar duplicados
            $this->agregarLog($importLog, "  🔍 Verificando duplicados (Cédula: {$data['cedula']})...", 'info');
            $conductorExistente = Conductor::where('cedula', $data['cedula'])->first();
            if ($conductorExistente) {
                $this->agregarLog($importLog, "  ⚠️ Conductor duplicado encontrado (ID: {$conductorExistente->id})", 'warning');

                return [
                    'tipo' => 'duplicado',
                    'mensaje' => "Conductor duplicado - Cédula {$data['cedula']} ya existe",
                ];
            }

            // Crear conductor
            $this->agregarLog($importLog, '  💾 Creando conductor en la base de datos...', 'info');
            Conductor::create($data);
            $this->agregarLog($importLog, '  ✅ Conductor creado exitosamente', 'success');

            return [
                'tipo' => 'importado',
                'mensaje' => "Conductor {$data['nombres']} {$data['apellidos']} (Cédula: {$data['cedula']})",
            ];

        } catch (\Exception $e) {
            $this->agregarLog($importLog, '  ❌ Error: '.$e->getMessage(), 'error');

            return [
                'tipo' => 'error',
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Actualizar ImportLog
     */
    private function actualizarImportLog(ImportLog $importLog, array $datos): void
    {
        $logsExistentes = $importLog->logs ?? [];

        if (isset($datos['logs']) && is_array($datos['logs'])) {
            $logsExistentes = array_merge($logsExistentes, $datos['logs']);
            // Mantener más logs para debug (200 en lugar de 100)
            if (count($logsExistentes) > 200) {
                $logsExistentes = array_slice($logsExistentes, -200);
            }
            $datos['logs'] = $logsExistentes;
        }

        $importLog->update($datos);
    }

    /**
     * Agregar log al ImportLog
     */
    private function agregarLog(ImportLog $importLog, string $mensaje, string $tipo = 'info'): void
    {
        $logsExistentes = $importLog->logs ?? [];
        $logsExistentes[] = [
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Mantener máximo 200 logs
        if (count($logsExistentes) > 200) {
            $logsExistentes = array_slice($logsExistentes, -200);
        }

        $importLog->update(['logs' => $logsExistentes]);
    }

    /**
     * Parsear fecha desde diferentes formatos
     */
    private function parseFecha($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Procesar archivo Excel (simplificado - usar lógica similar)
     */
    private function procesarExcel(ImportLog $importLog, string $fullPath): void
    {
        // Implementación simplificada - por ahora usar CSV
        // TODO: Implementar procesamiento de Excel completo
        throw new \Exception('Procesamiento de Excel aún no implementado en el Job');
    }

    /**
     * Descargar imagen desde Google Drive
     */
    private function downloadImageFromDrive(ImportLog $importLog, string $url, int $lineaNumero): ?string
    {
        try {
            // Extraer el ID del archivo de Google Drive
            $this->agregarLog($importLog, '      🔑 Extrayendo ID de Google Drive de la URL...', 'info');
            $fileId = $this->extractGoogleDriveFileId($url);

            if (! $fileId) {
                $this->agregarLog($importLog, '      ❌ No se pudo extraer el ID de Google Drive de la URL', 'error');
                \Log::warning('No se pudo extraer el ID de Google Drive de la URL: '.$url);

                return null;
            }

            $this->agregarLog($importLog, "      ✓ ID extraído: {$fileId}", 'info');

            // Intentar diferentes métodos de descarga
            // Método 1: URL directa simple
            $this->agregarLog($importLog, '      ⬇️ Intentando descarga (método 1)...', 'info');
            $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
            $imageContent = @file_get_contents($downloadUrl);

            // Si falla, intentar método alternativo
            if ($imageContent === false || empty($imageContent)) {
                $this->agregarLog($importLog, '      ⬇️ Método 1 falló, intentando método 2...', 'info');
                $downloadUrl = "https://drive.google.com/uc?export=download&confirm=t&id={$fileId}";
                $imageContent = @file_get_contents($downloadUrl);
            }

            if ($imageContent === false || empty($imageContent)) {
                $this->agregarLog($importLog, '      ❌ No se pudo descargar la imagen de Google Drive', 'error');
                \Log::warning('No se pudo descargar la imagen de Google Drive. ID: '.$fileId);

                return null;
            }

            $this->agregarLog($importLog, '      ✓ Imagen descargada ('.strlen($imageContent).' bytes)', 'info');

            // Verificar que sea una imagen válida
            $this->agregarLog($importLog, '      🔍 Validando que sea una imagen válida...', 'info');
            $imageInfo = @getimagesizefromstring($imageContent);
            if ($imageInfo === false) {
                $this->agregarLog($importLog, '      ❌ El contenido descargado no es una imagen válida', 'error');
                \Log::warning('El contenido descargado no es una imagen válida. ID: '.$fileId);

                return null;
            }

            $this->agregarLog($importLog, "      ✓ Imagen válida: {$imageInfo['mime']} ({$imageInfo[0]}x{$imageInfo[1]})", 'info');

            // Generar nombre único
            $extension = $this->getImageExtension($imageInfo['mime']);
            $uploadPath = public_path('uploads/conductores');

            // Crear directorio si no existe
            if (! File::exists($uploadPath)) {
                $this->agregarLog($importLog, '      📁 Creando directorio de uploads...', 'info');
                File::makeDirectory($uploadPath, 0755, true);
            }

            $filename = Str::uuid().'.'.$extension;
            $fullPath = $uploadPath.'/'.$filename;

            $this->agregarLog($importLog, "      💾 Guardando imagen: {$filename}...", 'info');

            // Guardar la imagen
            file_put_contents($fullPath, $imageContent);

            $this->agregarLog($importLog, "      ✅ Imagen guardada exitosamente en: uploads/conductores/{$filename}", 'success');

            return 'uploads/conductores/'.$filename;

        } catch (\Exception $e) {
            $this->agregarLog($importLog, '      ❌ Error descargando imagen: '.$e->getMessage(), 'error');
            \Log::error('Error descargando imagen de Google Drive: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extraer ID de archivo de URL de Google Drive
     */
    private function extractGoogleDriveFileId($url): ?string
    {
        // Patrón 1: https://drive.google.com/open?id=FILE_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Patrón 2: https://drive.google.com/file/d/FILE_ID/view
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Patrón 3: https://drive.google.com/uc?id=FILE_ID
        if (preg_match('/\/uc\?id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Obtener extensión desde MIME type
     */
    private function getImageExtension($mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }
}
