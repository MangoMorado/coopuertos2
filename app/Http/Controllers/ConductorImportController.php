<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\Vehicle;
use App\Models\ImportLog;
use App\Jobs\ProcesarImportacionConductores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ConductorImportController extends Controller
{
    public function showImportForm()
    {
        $importSessionId = session('import_session_id');
        $importLog = null;
        
        if ($importSessionId) {
            $importLog = ImportLog::where('session_id', $importSessionId)
                ->where('user_id', auth()->id())
                ->first();
        }
        
        return view('conductores.import', [
            'importLog' => $importLog,
            'importSessionId' => $importSessionId
        ]);
    }

    public function import(Request $request)
    {
        // Aumentar tiempo de ejecución para importaciones grandes
        set_time_limit(300); // 5 minutos
        
        try {
            // Validar manualmente para poder retornar JSON en caso de error
            // Aceptar CSV con diferentes MIME types y extensiones
            $validator = \Validator::make($request->all(), [
                'archivo' => [
                    'required',
                    'file',
                    'max:10240', // Max 10MB
                    function ($attribute, $value, $fail) {
                        if (!$value) {
                            return;
                        }
                        
                        $extension = strtolower($value->getClientOriginalExtension());
                        $mimeType = $value->getMimeType();
                        
                        // Extensiones permitidas
                        $allowedExtensions = ['xlsx', 'xls', 'csv'];
                        
                        // MIME types permitidos
                        $allowedMimeTypes = [
                            // Excel
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                            // CSV - múltiples variantes
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/x-csv',
                            'application/x-csv',
                            'text/comma-separated-values',
                            'text/x-comma-separated-values',
                            'application/vnd.ms-excel', // Excel también puede leer CSV
                        ];
                        
                        // Validar extensión
                        if (!in_array($extension, $allowedExtensions)) {
                            $fail("El archivo debe ser de tipo: " . implode(', ', $allowedExtensions) . ". Extensión recibida: {$extension}");
                            return;
                        }
                        
                        // Para CSV, ser más flexible con MIME types
                        if ($extension === 'csv') {
                            // Aceptar cualquier MIME type para CSV ya que varían mucho
                            return;
                        }
                        
                        // Para Excel, validar MIME type
                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            $fail("El archivo tiene un tipo MIME no válido: {$mimeType}");
                            return;
                        }
                    }
                ],
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación: ' . $validator->errors()->first()
                    ], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }
            
            // Crear registro de importación en base de datos
            $sessionId = Str::uuid()->toString();
            $file = $request->file('archivo');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = $file->getClientOriginalName();
            
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp_imports');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }
            
            // Guardar archivo temporalmente
            $tempFilename = $sessionId . '.' . $extension;
            $tempPath = 'temp_imports/' . $tempFilename;
            $tempFullPath = storage_path('app/' . $tempPath);
            
            try {
                $file->move($tempDir, $tempFilename);
            } catch (\Exception $e) {
                \Log::error('Error moviendo archivo: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar archivo: ' . $e->getMessage()
                ], 500);
            }
            
            // Crear registro en base de datos
            $importLog = ImportLog::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'file_path' => $tempPath,
                'file_name' => $fileName,
                'extension' => $extension,
                'estado' => 'pendiente',
                'progreso' => 0,
                'mensaje' => 'Archivo cargado. Esperando procesamiento...',
                'logs' => [['mensaje' => 'Archivo cargado correctamente', 'tipo' => 'success']],
                'started_at' => null,
            ]);
            
            // Encolar job para procesar en segundo plano
            ProcesarImportacionConductores::dispatch($sessionId, $tempPath, $extension, auth()->id());
            
            // Si es petición AJAX, retornar inmediatamente
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'session_id' => $sessionId,
                    'message' => 'Importación iniciada. Procesando en segundo plano...',
                    'estado' => 'procesando'
                ]);
            }
            
            // Si no es AJAX, redirigir a la página de importación con el session_id
            return redirect()->route('conductores.import.index')
                ->with('import_session_id', $sessionId)
                ->with('success', 'Archivo cargado. La importación se procesará en segundo plano.');

        } catch (\Exception $e) {
            \Log::error('Error en import: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if (isset($sessionId)) {
                $this->actualizarProgreso($sessionId, [
                    'estado' => 'error',
                    'error' => 'Error al importar archivo: ' . $e->getMessage(),
                    'log' => [['mensaje' => 'Error: ' . $e->getMessage(), 'tipo' => 'error']]
                ]);
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al iniciar importación: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al importar archivo: ' . $e->getMessage());
        }
    }

    /**
     * Procesar archivo completo de forma síncrona (para requests no-AJAX)
     */
    private function procesarArchivoCompleto(Request $request, $sessionId, $file)
    {
        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/temp_imports');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        // Guardar el archivo temporalmente usando move directo
        $extension = $file->getClientOriginalExtension();
        $tempFilename = $sessionId . '.' . $extension;
        $tempPath = 'temp_imports/' . $tempFilename;
        $tempFullPath = storage_path('app/' . $tempPath);
        
        // Mover el archivo subido al directorio temporal
        try {
            $file->move($tempDir, $tempFilename);
        } catch (\Exception $e) {
            \Log::error('Error moviendo archivo (no-AJAX): ' . $e->getMessage());
            throw new \Exception("No se pudo mover el archivo: " . $e->getMessage());
        }
        
        // Verificar que el archivo se guardó correctamente
        if (!file_exists($tempFullPath)) {
            throw new \Exception("No se pudo guardar el archivo temporal en: {$tempFullPath}. Verifique permisos del directorio.");
        }
        
        try {
            $result = $this->procesarArchivo($sessionId, $tempPath, $extension, $request);
            
            // Limpiar archivo temporal
            if (file_exists($tempFullPath)) {
                @unlink($tempFullPath);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Limpiar archivo temporal en caso de error
            if (file_exists($tempFullPath)) {
                @unlink($tempFullPath);
            }
            throw $e;
        }
    }

    /**
     * Procesar archivo de forma asíncrona o síncrona
     */
    private function procesarArchivo($sessionId, $filePath, $extension, $request = null)
    {
        // Aumentar tiempo de ejecución y memoria para archivos grandes
        set_time_limit(600); // 10 minutos
        ini_set('memory_limit', '512M'); // Aumentar memoria a 512MB
        
        try {
            $this->actualizarProgreso($sessionId, [
                'estado' => 'procesando',
                'progreso' => 5,
                'mensaje' => 'Preparando lectura del archivo...',
                'log' => [['mensaje' => 'Archivo cargado correctamente', 'tipo' => 'success']]
            ]);

            // Determinar la ruta completa del archivo PRIMERO
            // Si la ruta ya es absoluta (comienza con / o contiene :\ en Windows), usarla directamente
            if (strpos($filePath, '/') === 0 || strpos($filePath, ':\\') !== false || strpos($filePath, ':/') !== false) {
                // Ya es una ruta absoluta (Unix/Linux/Windows)
                $fullPath = $filePath;
            } elseif (strpos($filePath, storage_path('app')) === 0) {
                // Ya es una ruta completa que incluye storage/app
                $fullPath = $filePath;
            } else {
                // Es una ruta relativa en storage (ej: temp_imports/xxx.xlsx)
                // Normalizar separadores de ruta para Windows
                $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
                $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $normalizedPath);
            }
            
            // Verificar que el archivo existe antes de procesarlo
            if (!file_exists($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}. Ruta original: {$filePath}");
            }

            // Determinar el tamaño del archivo para decidir la estrategia de lectura
            $fileSize = filesize($fullPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            
            $this->actualizarProgreso($sessionId, [
                'progreso' => 6,
                'mensaje' => "Analizando archivo ({$fileSizeMB} MB)...",
                'log' => [['mensaje' => "📊 Tamaño del archivo: {$fileSizeMB} MB", 'tipo' => 'info']]
            ]);
            session()->save();
            
            // Para archivos CSV grandes, usar lectura línea por línea
            if ($extension === 'csv') {
                return $this->procesarCSVOptimizado($sessionId, $fullPath);
            } else {
                // Para Excel, intentar leer en chunks si es muy grande
                if ($fileSize > 10 * 1024 * 1024) { // > 10MB
                    return $this->procesarExcelOptimizado($sessionId, $fullPath);
                } else {
                    return $this->procesarExcelNormal($sessionId, $fullPath);
                }
            }
        } catch (\Exception $e) {
            $this->actualizarProgreso($sessionId, [
                'estado' => 'error',
                'error' => 'Error al procesar archivo: ' . $e->getMessage(),
                'log' => [['mensaje' => "✗ Error: " . $e->getMessage(), 'tipo' => 'error']]
            ]);
            session()->save();
            throw $e;
        }
    }

    /**
     * Procesar CSV de forma optimizada (línea por línea sin cargar todo en memoria)
     */
    private function procesarCSVOptimizado($sessionId, $fullPath)
    {
        try {
            // Detectar delimitador
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \Exception('No se pudo abrir el archivo CSV');
            }
            
            $firstLine = fgets($handle);
            rewind($handle);
            
            $comas = substr_count($firstLine, ',');
            $puntoComas = substr_count($firstLine, ';');
            $delimiter = ($puntoComas > $comas) ? ';' : ',';
            
            \Log::info('CSV detectado (modo optimizado)', [
                'delimiter' => $delimiter,
                'comas' => $comas,
                'puntoComas' => $puntoComas
            ]);
            
            $logAmigable = $this->logAmigable('CSV detectado', 'info', [
                'delimiter' => $delimiter,
                'comas' => $comas,
                'puntoComas' => $puntoComas
            ]);
            $this->actualizarProgreso($sessionId, [
                'progreso' => 7,
                'mensaje' => str_replace('📄 ', '', $logAmigable['mensaje']),
                'log' => [['mensaje' => '🚀 Modo optimizado activado para archivos grandes', 'tipo' => 'info'], $logAmigable]
            ]);
            session()->save();
            
            // Leer encabezados
            $headers = fgetcsv($handle, 0, $delimiter, '"');
            if (!$headers || empty($headers)) {
                if (isset($handle) && is_resource($handle)) {
                    fclose($handle);
                }
                throw new \Exception('No se pudieron leer los encabezados del archivo');
            }
            
            // Limpiar encabezados: quitar BOM y espacios
            $headers = array_map(function($header) {
                // Quitar BOM (Byte Order Mark) UTF-8
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
                return trim($header);
            }, $headers);
            
            // Guardar headers originales para logging
            $headersOriginales = $headers;
            
            // Normalizar a mayúsculas para comparación
            $headersUpper = array_map('strtoupper', $headers);
            
            // Log de encabezados encontrados
            \Log::info('Encabezados CSV detectados', [
                'headers' => $headers,
                'delimiter' => $delimiter
            ]);
            
            // Mapeo de columnas (con variaciones posibles)
            $columnMapping = [
                'NOMBRES' => 'nombres',
                'APELLIDOS' => 'apellidos',
                'CEDULA' => 'cedula',
                'CÉDULA' => 'cedula',
                'CONDUCTOR TIPO' => 'conductor_tipo',
                'TIPO CONDUCTOR' => 'conductor_tipo',
                'RH' => 'rh',
                'GRUPO SANGUINEO' => 'rh',
                'VEHICULO PLACA' => 'vehiculo',
                'VEHÍCULO PLACA' => 'vehiculo',
                'PLACA' => 'vehiculo',
                'VEHICULO' => 'vehiculo',
                'VEHÍCULO' => 'vehiculo',
                'NUMERO INTERNO' => 'numero_interno',
                'NÚMERO INTERNO' => 'numero_interno',
                'NUMERO' => 'numero_interno',
                'CELULAR' => 'celular',
                'TELÉFONO' => 'celular',
                'TELEFONO' => 'celular',
                'CORREO' => 'correo',
                'EMAIL' => 'correo',
                'E-MAIL' => 'correo',
                'FECHA DE NACIMIENTO' => 'fecha_nacimiento',
                'FECHA NACIMIENTO' => 'fecha_nacimiento',
                'FECHA NAC.' => 'fecha_nacimiento',
                '¿SABE OTRA PROFESIÓN A PARTE DE SER CONDUCTOR?' => 'otra_profesion',
                'OTRA PROFESIÓN' => 'otra_profesion',
                'OTRA PROFESION' => 'otra_profesion',
                'CARGUE SU FOTO PARA CARNET' => 'foto',
                'FOTO' => 'foto',
                'IMAGEN' => 'foto',
                'NIVEL DE ESTUDIOS' => 'nivel_estudios',
                'ESTUDIOS' => 'nivel_estudios',
            ];
            
            $indexMap = [];
            foreach ($columnMapping as $header => $field) {
                $index = array_search(strtoupper($header), $headersUpper);
                if ($index !== false) {
                    $indexMap[$field] = $index;
                }
            }
            
            // Log del mapeo creado
            \Log::info('Mapeo de columnas CSV', [
                'indexMap' => $indexMap,
                'headers' => $headers
            ]);
            
            // Validar columnas requeridas con mensaje más informativo
            $requiredFields = ['nombres', 'apellidos', 'cedula'];
            $columnasFaltantes = [];
            foreach ($requiredFields as $field) {
                if (!isset($indexMap[$field])) {
                    $columnasFaltantes[] = $field;
                }
            }
            
            if (!empty($columnasFaltantes)) {
                if (isset($handle) && is_resource($handle)) {
                    fclose($handle);
                }
                $columnasEncontradas = implode(', ', $headers);
                throw new \Exception("Faltan columnas requeridas: " . implode(', ', $columnasFaltantes) . ". Columnas encontradas en el archivo: " . $columnasEncontradas);
            }
            
            // Cerrar el handle actual después de leer los headers
            // Reabriremos el archivo para procesar
            fclose($handle);
            $handle = null;
            
            // Estimar total de filas leyendo el archivo una vez (opcional, puede ser aproximado)
            // Por ahora, usaremos un conteo simple o procesaremos sin conocer el total exacto
            $this->actualizarProgreso($sessionId, [
                'progreso' => 15,
                'mensaje' => "Archivo CSV listo. Iniciando procesamiento...",
                'log' => [['mensaje' => "✓ Archivo CSV preparado. Iniciando procesamiento línea por línea...", 'tipo' => 'success']]
            ]);
            session()->save();
            
            // Reabrir el archivo para procesar (será cerrado dentro de procesarRegistrosCSV)
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \Exception('No se pudo reabrir el archivo CSV para procesamiento');
            }
            
            // Saltar la línea de headers
            fgetcsv($handle, 0, $delimiter, '"');
            
            // Procesar línea por línea (no pasamos totalFilas, se calculará sobre la marcha)
            return $this->procesarRegistrosCSV($sessionId, $handle, $delimiter, $indexMap, $fullPath);
            
        } catch (\Exception $e) {
            // Cerrar handle solo si es válido
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
                $handle = null;
            }
            throw $e;
        }
    }

    /**
     * Procesar registros CSV línea por línea (optimizado para archivos grandes)
     */
    private function procesarRegistrosCSV($sessionId, $handle, $delimiter, $indexMap, $fullPath = null)
    {
        try {
            // Preparar contadores
            $registrosProcesados = 0;
            $registrosValidos = 0;
            $importados = 0;
            $duplicados = 0;
            $errores = [];
            $loteSize = 10;
            $procesadosEnLote = 0;
            $lineaActual = 1; // Empezar desde 1 (después de headers)
            
            // Analizar y procesar línea por línea
            $this->actualizarProgreso($sessionId, [
                'progreso' => 18,
                'mensaje' => 'Iniciando procesamiento línea por línea...',
                'log' => [['mensaje' => '🔄 Modo optimizado: procesando línea por línea sin cargar todo en memoria', 'tipo' => 'info']]
            ]);
            session()->save();
            
            DB::beginTransaction();
            
            // Verificar que el handle es válido antes de procesar
            if (!is_resource($handle)) {
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
                $resultado = $this->procesarRegistro($sessionId, $row, $indexMap, $lineaActual);
                
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
                // Progreso incremental sin conocer el total exacto
                if ($procesadosEnLote >= $loteSize || $registrosProcesados % 3 == 0) {
                    // Calcular progreso incremental (20% inicial + 70% procesamiento)
                    // Estimamos que el progreso aumenta gradualmente
                    $progresoIncremental = min(20 + (($registrosProcesados * 0.5)), 90); // 0.5% por registro aproximadamente
                    
                    $this->actualizarProgreso($sessionId, [
                        'progreso' => $progresoIncremental,
                        'procesados' => $registrosProcesados,
                        'total' => null, // No conocemos el total exacto aún
                        'importados' => $importados,
                        'duplicados' => $duplicados,
                        'mensaje' => "Procesando... {$registrosProcesados} registros procesados (Importados: {$importados}, Duplicados: {$duplicados}, Errores: " . count($errores) . ")",
                        'log' => $procesadosEnLote >= $loteSize ? [['mensaje' => "📦 Lote completado: {$procesadosEnLote} registros. Total procesados: {$registrosProcesados}", 'tipo' => 'info']] : []
                    ]);
                    session()->save();
                    
                    if ($procesadosEnLote >= $loteSize) {
                        $procesadosEnLote = 0;
                    }
                }
            }
            
            // Cerrar el handle solo si es válido
            if (is_resource($handle)) {
                fclose($handle);
                $handle = null; // Marcar como cerrado
            }
            
            // Finalizar
            DB::commit();
            
            // Calcular total de líneas procesadas para el resumen final
            $totalProcesado = $registrosProcesados;
            
            $this->actualizarProgreso($sessionId, [
                'estado' => 'completado',
                'progreso' => 100,
                'procesados' => $registrosProcesados,
                'total' => $totalProcesado,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'mensaje' => "Importación completada: {$importados} importados, {$duplicados} duplicados, " . count($errores) . " errores de {$totalProcesado} registros procesados",
                'log' => [['mensaje' => "✅ Importación completada exitosamente: {$importados} importados, {$duplicados} duplicados, " . count($errores) . " errores", 'tipo' => 'success']]
            ]);
            session()->save();
            
        } catch (\Exception $e) {
            // Cerrar el handle solo si es válido
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
                $handle = null;
            }
            DB::rollBack();
            
            $this->actualizarProgreso($sessionId, [
                'estado' => 'error',
                'error' => 'Error durante la importación: ' . $e->getMessage(),
                'log' => [['mensaje' => "✗ Error: " . $e->getMessage(), 'tipo' => 'error']]
            ]);
            session()->save();
            
            throw $e;
        }
    }

    /**
     * Procesar un registro individual (extraído para reutilización)
     */
    private function procesarRegistro($sessionId, $row, $indexMap, $lineaNumero)
    {
        try {
            // Extraer datos según el mapeo
            $data = [];
            foreach ($indexMap as $field => $index) {
                $value = $row[$index] ?? null;
                
                if ($value !== null) {
                    $value = trim((string) $value);
                    
                    // Procesar según el campo (misma lógica que antes)
                    switch ($field) {
                        case 'cedula':
                            $data['cedula'] = $value;
                            break;
                        case 'nombres':
                            $data['nombres'] = ucwords(strtolower($value));
                            break;
                        case 'apellidos':
                            $data['apellidos'] = ucwords(strtolower($value));
                            break;
                        case 'conductor_tipo':
                            $normalizedValue = strtoupper(trim($value));
                            if (strpos($normalizedValue, 'TIPO A') !== false || 
                                strpos($normalizedValue, 'CAMIONETAS') !== false ||
                                $normalizedValue === 'A') {
                                $data['conductor_tipo'] = 'A';
                            } elseif (strpos($normalizedValue, 'TIPO B') !== false || 
                                     strpos($normalizedValue, 'BUSETAS') !== false ||
                                     $normalizedValue === 'B') {
                                $data['conductor_tipo'] = 'B';
                            } else {
                                $data['conductor_tipo'] = 'B';
                            }
                            break;
                        case 'rh':
                            $rhValue = strtoupper(str_replace(' ', '', trim($value)));
                            $rhValidos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            if (in_array($rhValue, $rhValidos)) {
                                $data['rh'] = $rhValue;
                            } else {
                                $rhValue = str_replace(['POSITIVO', 'POS', '+'], '+', $rhValue);
                                $rhValue = str_replace(['NEGATIVO', 'NEG', '-'], '-', $rhValue);
                                $data['rh'] = in_array($rhValue, $rhValidos) ? $rhValue : null;
                            }
                            break;
                        case 'vehiculo':
                            $vehiculoValue = trim((string) $value);
                            $data['vehiculo'] = !empty($vehiculoValue) ? strtoupper($vehiculoValue) : null;
                            break;
                        case 'numero_interno':
                            $data['numero_interno'] = $value;
                            break;
                        case 'celular':
                            $data['celular'] = $value;
                            break;
                        case 'correo':
                            $data['correo'] = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
                            break;
                        case 'fecha_nacimiento':
                            $data['fecha_nacimiento'] = $this->parseFecha($value);
                            break;
                        case 'otra_profesion':
                            $data['otra_profesion'] = $value;
                            break;
                        case 'nivel_estudios':
                            $data['nivel_estudios'] = $value;
                            break;
                        case 'foto':
                            if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                                $data['foto'] = $this->downloadImageFromDrive($value);
                            }
                            break;
                    }
                }
            }
            
            // Manejar relevo
            $conductorTipo = strtolower(trim($data['conductor_tipo'] ?? ''));
            $vehiculo = strtolower(trim($data['vehiculo'] ?? ''));
            $numeroInterno = strtolower(trim($data['numero_interno'] ?? ''));
            
            if (strpos($conductorTipo, 'relevo') !== false || 
                strpos($vehiculo, 'relevo') !== false || 
                strpos($numeroInterno, 'relevo') !== false) {
                $data['conductor_tipo'] = null;
                $data['vehiculo'] = null;
                $data['numero_interno'] = null;
                $data['relevo'] = true;
            } elseif (empty($data['vehiculo']) || trim($data['vehiculo'] ?? '') === '') {
                $data['relevo'] = true;
            }
            
            // Validar cédula
            if (empty($data['cedula'])) {
                return [
                    'tipo' => 'error',
                    'mensaje' => "Fila {$lineaNumero}: Cédula requerida"
                ];
            }
            
            // Verificar duplicados
            $conductorExistente = Conductor::where('cedula', $data['cedula'])->first();
            if ($conductorExistente) {
                return [
                    'tipo' => 'duplicado',
                    'mensaje' => "Fila {$lineaNumero}: Conductor duplicado - Cédula {$data['cedula']} ya existe"
                ];
            }
            
            // Crear conductor
            Conductor::create($data);
            
            return [
                'tipo' => 'importado',
                'mensaje' => "Fila {$lineaNumero}: Conductor importado exitosamente"
            ];
            
        } catch (\Illuminate\Database\QueryException $e) {
            $errorMsg = $this->limpiarMensajeError($e->getMessage());
            return [
                'tipo' => 'error',
                'mensaje' => "Fila {$lineaNumero}: Error al crear conductor - " . $errorMsg
            ];
        } catch (\Exception $e) {
            return [
                'tipo' => 'error',
                'mensaje' => "Fila {$lineaNumero}: " . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar Excel optimizado (para archivos grandes > 10MB)
     */
    private function procesarExcelOptimizado($sessionId, $fullPath)
    {
        // Por ahora, usar el método normal pero con advertencia
        // En el futuro, se podría implementar lectura por chunks usando PhpSpreadsheet Reader
        $this->actualizarProgreso($sessionId, [
            'progreso' => 8,
            'mensaje' => '⚠️ Archivo Excel grande detectado...',
            'log' => [['mensaje' => '⚠️ Archivo Excel grande. Usando procesamiento estándar (puede consumir mucha memoria)', 'tipo' => 'warning']]
        ]);
        session()->save();
        
        return $this->procesarExcelNormal($sessionId, $fullPath);
    }

    /**
     * Procesar Excel de forma normal (archivos pequeños/medianos)
     */
    private function procesarExcelNormal($sessionId, $fullPath)
    {
        try {
            $reader = new XlsxReader();
        
        $this->actualizarProgreso($sessionId, [
            'progreso' => 8,
            'mensaje' => 'Leyendo archivo Excel...',
            'log' => [['mensaje' => '📖 Cargando archivo Excel en memoria...', 'tipo' => 'info']]
        ]);
        session()->save();
        
        $spreadsheet = $reader->load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $this->actualizarProgreso($sessionId, [
            'progreso' => 10,
            'mensaje' => 'Archivo cargado correctamente...',
            'log' => [['mensaje' => '✓ Archivo cargado correctamente', 'tipo' => 'success']]
        ]);
        session()->save();
        
        $rows = $worksheet->toArray();
        
        $this->actualizarProgreso($sessionId, [
            'progreso' => 15,
            'mensaje' => "Datos extraídos: " . count($rows) . " filas encontradas",
            'log' => [['mensaje' => '✓ Datos extraídos: ' . count($rows) . ' filas encontradas', 'tipo' => 'success']]
        ]);
        session()->save();

        if (count($rows) < 2) {
                $this->actualizarProgreso($sessionId, [
                    'estado' => 'error',
                    'error' => 'El archivo está vacío o no tiene datos.'
                ]);
                return;
            }

            $totalFilas = count($rows) - 1; // Excluir encabezados
            
            // Obtener encabezados de la primera fila
            $headers = array_map('trim', array_map('strtoupper', $rows[0]));
            
            // Mapeo de columnas esperadas
            $columnMapping = [
                'NOMBRES' => 'nombres',
                'APELLIDOS' => 'apellidos',
                'CEDULA' => 'cedula',
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

            // Crear mapeo de índices
            $indexMap = [];
            foreach ($columnMapping as $header => $field) {
                $index = array_search($header, $headers);
                if ($index !== false) {
                    $indexMap[$field] = $index;
                }
            }

            // Validar que existan las columnas mínimas requeridas
            $requiredFields = ['nombres', 'apellidos', 'cedula'];
            foreach ($requiredFields as $field) {
                if (!isset($indexMap[$field])) {
                    $columnName = array_search($field, array_flip($columnMapping));
                    $errorMsg = "Falta la columna requerida: " . ($columnName ?: $field);
                    $this->actualizarProgreso($sessionId, [
                        'estado' => 'error',
                        'error' => $errorMsg
                    ]);
                    return;
                }
            }

            // Contar registros válidos antes de procesar
            $this->actualizarProgreso($sessionId, [
                'progreso' => 18,
                'mensaje' => 'Analizando registros válidos...',
                'log' => [['mensaje' => 'Validando estructura de datos y contando registros', 'tipo' => 'info']]
            ]);
            
            // Contar solo filas no vacías (sin procesar completamente)
            $registrosValidos = 0;
            $totalFilas = count($rows) - 1; // Excluir encabezados
            $contadorProgreso = 0;
            
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                // Contar solo filas no vacías (que tendrán al menos algunos datos)
                if (!empty(array_filter($row))) {
                    $registrosValidos++;
                }
                
                // Actualizar progreso cada 50 filas contadas
                $contadorProgreso++;
                if ($contadorProgreso % 50 == 0) {
                    $progresoConteo = 18 + (($contadorProgreso / $totalFilas) * 2);
                    $this->actualizarProgreso($sessionId, [
                        'progreso' => $progresoConteo,
                        'mensaje' => "Analizando registro {$contadorProgreso} de {$totalFilas}...",
                        'log' => [['mensaje' => "Analizadas {$contadorProgreso} de {$totalFilas} filas. Registros válidos encontrados: {$registrosValidos}", 'tipo' => 'info']]
                    ]);
                    session()->save();
                }
            }
            
            // Si no hay registros válidos, terminar
            if ($registrosValidos == 0) {
                $this->actualizarProgreso($sessionId, [
                    'estado' => 'error',
                    'error' => 'No se encontraron registros válidos para procesar.'
                ]);
                return;
            }
            
            // Mostrar información antes de procesar
            $this->actualizarProgreso($sessionId, [
                'progreso' => 20,
                'total' => $registrosValidos,
                'mensaje' => "✅ Análisis completado: {$registrosValidos} registros válidos encontrados",
                'log' => [
                    ['mensaje' => "📊 Análisis completado: {$registrosValidos} registros válidos encontrados de {$totalFilas} filas totales", 'tipo' => 'success'],
                    ['mensaje' => "⚙️ El procesamiento se realizará en lotes de 10 registros para mejor rendimiento", 'tipo' => 'info'],
                    ['mensaje' => "🚀 Iniciando procesamiento en 1 segundo...", 'tipo' => 'info']
                ]
            ]);
            
            // Pausa breve para que el usuario vea la información
            session()->save();
            usleep(1000000); // 1 segundo para que el usuario vea el resumen

            DB::beginTransaction();
            
            $importados = 0;
            $errores = [];
            $duplicados = 0;
            $loteSize = 10;
            $procesadosEnLote = 0;
            $registrosProcesados = 0; // Contador de registros realmente procesados

            $this->actualizarProgreso($sessionId, [
                'progreso' => 22,
                'mensaje' => '🚀 Iniciando procesamiento por lotes...',
                'log' => [
                    ['mensaje' => '🔄 Iniciando procesamiento de registros...', 'tipo' => 'info'],
                    ['mensaje' => "📦 Configuración: Lotes de {$loteSize} registros", 'tipo' => 'info']
                ]
            ]);
            session()->save();

            // Procesar cada fila (empezando desde la segunda, ya que la primera son encabezados)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $registrosProcesados++; // Incrementar solo cuando realmente procesamos un registro

                try {
                    // Extraer datos según el mapeo
                    $data = [];
                    foreach ($indexMap as $field => $index) {
                        $value = $row[$index] ?? null;
                        
                        if ($value !== null) {
                            $value = trim((string) $value);
                            
                            // Procesar según el campo
                            switch ($field) {
                                case 'cedula':
                                    $data['cedula'] = $value;
                                    break;
                                case 'nombres':
                                    $data['nombres'] = ucwords(strtolower($value));
                                    break;
                                case 'apellidos':
                                    $data['apellidos'] = ucwords(strtolower($value));
                                    break;
                                case 'conductor_tipo':
                                    // Normalizar tipo A o B
                                    // Manejar formatos: "TIPO A (CAMIONETAS)", "TIPO B (BUSETAS)", "A", "B", etc.
                                    $normalizedValue = strtoupper(trim($value));
                                    
                                    // Verificar si contiene "TIPO A" o "CAMIONETAS" o solo "A"
                                    if (strpos($normalizedValue, 'TIPO A') !== false || 
                                        strpos($normalizedValue, 'CAMIONETAS') !== false ||
                                        $normalizedValue === 'A') {
                                        $data['conductor_tipo'] = 'A';
                                    }
                                    // Verificar si contiene "TIPO B" o "BUSETAS" o solo "B"
                                    elseif (strpos($normalizedValue, 'TIPO B') !== false || 
                                            strpos($normalizedValue, 'BUSETAS') !== false ||
                                            $normalizedValue === 'B') {
                                        $data['conductor_tipo'] = 'B';
                                    }
                                    // Por defecto asignar B si no coincide con nada
                                    else {
                                        $data['conductor_tipo'] = 'B';
                                    }
                                    break;
                                case 'rh':
                                    // Normalizar RH: eliminar espacios y convertir a mayúsculas
                                    $rhValue = strtoupper(str_replace(' ', '', trim($value)));
                                    // Validar que sea un valor válido del enum
                                    $rhValidos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    if (in_array($rhValue, $rhValidos)) {
                                        $data['rh'] = $rhValue;
                                    } else {
                                        // Si no es válido, intentar corregir formatos comunes
                                        $rhValue = str_replace(['POSITIVO', 'POS', '+'], '+', $rhValue);
                                        $rhValue = str_replace(['NEGATIVO', 'NEG', '-'], '-', $rhValue);
                                        // Si después de la corrección sigue sin ser válido, dejar null
                                        if (!in_array($rhValue, $rhValidos)) {
                                            $data['rh'] = null;
                                            \Log::warning("RH inválido: '{$value}' -> normalizado a null");
                                        } else {
                                            $data['rh'] = $rhValue;
                                        }
                                    }
                                    break;
                                case 'vehiculo':
                                    // Normalizar y manejar vacíos
                                    $vehiculoValue = trim((string) $value);
                                    if (!empty($vehiculoValue)) {
                                        $data['vehiculo'] = strtoupper($vehiculoValue);
                                    } else {
                                        $data['vehiculo'] = null;
                                    }
                                    break;
                                case 'numero_interno':
                                    $data['numero_interno'] = $value;
                                    break;
                                case 'celular':
                                    $data['celular'] = $value;
                                    break;
                                case 'correo':
                                    $data['correo'] = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
                                    break;
                                case 'fecha_nacimiento':
                                    $data['fecha_nacimiento'] = $this->parseFecha($value);
                                    break;
                                case 'otra_profesion':
                                    $data['otra_profesion'] = $value;
                                    break;
                                case 'nivel_estudios':
                                    $data['nivel_estudios'] = $value;
                                    break;
                                case 'foto':
                                    // Descargar imagen de Google Drive
                                    if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                                        $data['foto'] = $this->downloadImageFromDrive($value);
                                    }
                                    break;
                            }
                        }
                    }

                    // Verificar si alguno de los campos clave contiene "relevo"
                    $conductorTipo = strtolower(trim($data['conductor_tipo'] ?? ''));
                    $vehiculo = strtolower(trim($data['vehiculo'] ?? ''));
                    $numeroInterno = strtolower(trim($data['numero_interno'] ?? ''));
                    
                    $esRelevo = false;
                    if (strpos($conductorTipo, 'relevo') !== false || 
                        strpos($vehiculo, 'relevo') !== false || 
                        strpos($numeroInterno, 'relevo') !== false) {
                        $esRelevo = true;
                        // Establecer los 3 campos como null
                        $data['conductor_tipo'] = null;
                        $data['vehiculo'] = null;
                        $data['numero_interno'] = null;
                        $data['relevo'] = true;
                    }
                    // Si el vehículo está vacío (pero no es relevo explícito), establecer relevo en true
                    elseif (empty($data['vehiculo']) || trim($data['vehiculo'] ?? '') === '') {
                        $data['relevo'] = true;
                    }

                    // Validar cédula requerida
                    if (empty($data['cedula'])) {
                        $errores[] = "Fila " . ($i + 1) . ": Cédula requerida";
                        continue;
                    }

                    // Verificar si ya existe (duplicado por cédula)
                    $conductorExistente = Conductor::where('cedula', $data['cedula'])->first();
                    if ($conductorExistente) {
                        $duplicados++;
                        $errorMsg = "Fila " . ($i + 1) . ": Conductor duplicado - Cédula {$data['cedula']} ya existe (ID: {$conductorExistente->id}, Nombre: {$conductorExistente->nombres} {$conductorExistente->apellidos})";
                        $errores[] = $errorMsg;
                        $this->actualizarProgreso($sessionId, [
                            'duplicados' => $duplicados,
                            'log' => [['mensaje' => $errorMsg, 'tipo' => 'warning']]
                        ]);
                        continue;
                    }

                    // Crear conductor
                    try {
                        Conductor::create($data);
                        $importados++;
                        
                        // Log cada registro importado exitosamente
                        if ($importados % 5 == 0) {
                            $this->actualizarProgreso($sessionId, [
                                'importados' => $importados,
                                'log' => [['mensaje' => "✓ Registro {$registrosProcesados}: Conductor importado exitosamente (Cédula: {$data['cedula']})", 'tipo' => 'success']]
                            ]);
                            session()->save();
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Capturar errores de base de datos (incluyendo warnings de enum)
                        $errorMsg = "Fila " . ($i + 1) . ": Error al crear conductor";
                        
                        // Limpiar mensaje de error (quitar paths y detalles técnicos)
                        $errorMsgLimpio = $this->limpiarMensajeError($e->getMessage());
                        $errores[] = $errorMsg . " - " . $errorMsgLimpio;
                        
                        // Usar logAmigable para el log de Laravel
                        $logAmigable = $this->logAmigable('Error creando conductor en importación', 'error', [
                            'fila' => $i + 1,
                            'cedula' => $data['cedula'] ?? 'N/A',
                        ]);
                        
                        $this->actualizarProgreso($sessionId, [
                            'errores' => count($errores),
                            'log' => [['mensaje' => "✗ Registro {$registrosProcesados}: {$errorMsg} - {$errorMsgLimpio}", 'tipo' => 'error']]
                        ]);
                        
                        \Log::warning('Error creando conductor en importación', [
                            'fila' => $i + 1,
                            'cedula' => $data['cedula'] ?? 'N/A',
                            'error' => $e->getMessage(),
                            'data' => $data
                        ]);
                        session()->save();
                        continue;
                    }
                    
                    $procesadosEnLote++;
                    
                    // Actualizar progreso después de cada registro (no solo en lotes)
                    // Calcular progreso: 20% inicial + 70% procesamiento + 10% finalización
                    $progreso = 20 + (($registrosProcesados / $registrosValidos) * 70);
                    
                    // Si completamos un lote de 10 o llegamos al final, actualizar progreso con más detalle
                    if ($procesadosEnLote >= $loteSize || $i == count($rows) - 1) {
                        $this->actualizarProgreso($sessionId, [
                            'progreso' => min($progreso, 90),
                            'procesados' => $registrosProcesados,
                            'importados' => $importados,
                            'duplicados' => $duplicados,
                            'mensaje' => "Procesando... {$registrosProcesados} de {$registrosValidos} registros (Importados: {$importados}, Duplicados: {$duplicados}, Errores: " . count($errores) . ")",
                            'log' => [['mensaje' => "📦 Lote completado: {$procesadosEnLote} registros procesados. Progreso: {$registrosProcesados}/{$registrosValidos} ({(round($progreso))}%)", 'tipo' => 'info']]
                        ]);
                        
                        // Forzar guardado de sesión para que el frontend vea los cambios
                        session()->save();
                        
                        // Reiniciar contador de lote
                        $procesadosEnLote = 0;
                    } else {
                        // Actualizar progreso más frecuentemente (cada 3 registros)
                        if ($registrosProcesados % 3 == 0) {
                            $this->actualizarProgreso($sessionId, [
                                'progreso' => min($progreso, 90),
                                'procesados' => $registrosProcesados,
                                'mensaje' => "Procesando registro {$registrosProcesados} de {$registrosValidos}...",
                            ]);
                            session()->save();
                        }
                    }
                    
                    // Liberar memoria cada 5 lotes (50 registros)
                    if ($i % 50 == 0) {
                        gc_collect_cycles();
                    }

                } catch (\Exception $e) {
                    $errorMsg = "Fila " . ($i + 1) . ": " . $e->getMessage();
                    $errores[] = $errorMsg;
                    $this->actualizarProgreso($sessionId, [
                        'errores' => $errores,
                        'log' => [['mensaje' => $errorMsg, 'tipo' => 'error']]
                    ]);
                }
            }

            DB::commit();

            $mensaje = "Importación completada. Importados: {$importados}";
            if ($duplicados > 0) {
                $mensaje .= ", Duplicados omitidos: {$duplicados}";
            }
            if (count($errores) > 0) {
                $mensaje .= ", Errores: " . count($errores);
            }

            // Finalizar progreso
            $this->actualizarProgreso($sessionId, [
                'estado' => 'completado',
                'progreso' => 100,
                'procesados' => $registrosValidos,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores' => $errores,
                'mensaje' => $mensaje,
                'log' => [['mensaje' => $mensaje, 'tipo' => 'success']]
            ]);

            // Limpiar archivo temporal si existe
            if (strpos($filePath, 'temp_imports') !== false) {
                $tempFullPath = storage_path('app/' . $filePath);
                if (file_exists($tempFullPath)) {
                    @unlink($tempFullPath);
                }
            }

            // Si es request síncrono, retornar redirect
            if ($request) {
                return redirect()->route('conductores.index')
                    ->with('success', $mensaje)
                    ->with('errores', $errores);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Error procesando archivo de importación: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            
            if (isset($sessionId)) {
                $this->actualizarProgreso($sessionId, [
                    'estado' => 'error',
                    'error' => 'Error al importar archivo: ' . $e->getMessage(),
                    'log' => [['mensaje' => 'Error: ' . $e->getMessage(), 'tipo' => 'error']]
                ]);
            }
            
            // Limpiar archivo temporal si existe
            if (strpos($filePath, 'temp_imports') !== false) {
                $tempFullPath = storage_path('app/' . $filePath);
                if (file_exists($tempFullPath)) {
                    @unlink($tempFullPath);
                }
            }
            
            // Si es request síncrono, retornar redirect
            if ($request) {
                return redirect()->back()
                    ->with('error', 'Error al importar archivo: ' . $e->getMessage());
            }
        }
    }

    public function obtenerProgreso($sessionId)
    {
        try {
            $importLog = ImportLog::where('session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();
            
            if (!$importLog) {
                \Log::warning("ImportLog no encontrado: {$sessionId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Registro de importación no encontrado. La importación puede haber expirado o no haberse iniciado correctamente.'
                ], 404);
            }

            // Calcular tiempo transcurrido y estimado
            $tiempoTranscurrido = $importLog->tiempo_transcurrido;
            $tiempoEstimadoRestante = $importLog->tiempo_estimado_restante;
            $tiempoFormateado = $importLog->formatearTiempo($tiempoTranscurrido);
            $tiempoRestanteFormateado = $tiempoEstimadoRestante > 0 ? $importLog->formatearTiempo($tiempoEstimadoRestante) : null;

            return response()->json([
                'success' => true,
                'estado' => $importLog->estado,
                'progreso' => $importLog->progreso,
                'total' => $importLog->total,
                'procesados' => $importLog->procesados,
                'importados' => $importLog->importados,
                'duplicados' => $importLog->duplicados,
                'errores' => $importLog->errores ?? [],
                'errores_count' => $importLog->errores_count,
                'mensaje' => $importLog->mensaje ?? 'Procesando...',
                'log' => $importLog->logs ?? [],
                'tiempo_transcurrido' => $tiempoFormateado,
                'tiempo_estimado_restante' => $tiempoRestanteFormateado,
                'started_at' => $importLog->started_at?->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo progreso: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener progreso: ' . $e->getMessage()
            ], 500);
        }
    }

    private function actualizarProgreso($sessionId, $datos)
    {
        try {
            $progreso = session()->get("import_progress_{$sessionId}", []);
            
            // Obtener logs existentes
            $logsExistentes = $progreso['log'] ?? [];
            
            // Agregar nuevos logs
            if (isset($datos['log']) && is_array($datos['log'])) {
                $logsExistentes = array_merge($logsExistentes, $datos['log']);
                // Mantener solo los últimos 50 logs
                if (count($logsExistentes) > 50) {
                    $logsExistentes = array_slice($logsExistentes, -50);
                }
                $datos['log'] = $logsExistentes;
            }
            
            // Actualizar progreso
            $progreso = array_merge($progreso, $datos);
            session()->put("import_progress_{$sessionId}", $progreso);
            
            // Forzar guardado de sesión para que el frontend pueda leer los cambios inmediatamente
            session()->save();
            
        } catch (\Exception $e) {
            \Log::error('Error actualizando progreso: ' . $e->getMessage());
        }
    }

    /**
     * Convertir mensaje de log de Laravel en mensaje amigable para el usuario
     */
    private function logAmigable($mensaje, $tipo = 'info', $contexto = [])
    {
        // Mapeo de mensajes técnicos a mensajes amigables
        $mensajesAmigables = [
            'Archivo temporal guardado correctamente' => '✓ Archivo guardado correctamente',
            'CSV detectado' => function($ctx) {
                $delimiter = $ctx['delimiter'] ?? ',';
                $delimiterText = $delimiter === ';' ? 'punto y coma (;)' : 'coma (,)';
                return "📄 Archivo CSV detectado. Delimitador: {$delimiterText}";
            },
            'Procesando archivo' => '✓ Archivo encontrado y listo para procesar',
            'Archivo temporal no encontrado después de mover' => '✗ Error: No se pudo guardar el archivo temporal',
            'Error moviendo archivo' => '✗ Error: No se pudo mover el archivo',
            'Error procesando importación' => '✗ Error durante la importación',
            'Error procesando archivo de importación' => '✗ Error al procesar el archivo',
            'Error creando conductor en importación' => '✗ Error al crear el conductor',
        ];
        
        // Si hay un mapeo directo, usarlo
        if (isset($mensajesAmigables[$mensaje]) && is_string($mensajesAmigables[$mensaje])) {
            return [
                'mensaje' => $mensajesAmigables[$mensaje],
                'tipo' => $tipo
            ];
        }
        
        // Si es una función, ejecutarla
        if (isset($mensajesAmigables[$mensaje]) && is_callable($mensajesAmigables[$mensaje])) {
            return [
                'mensaje' => $mensajesAmigables[$mensaje]($contexto),
                'tipo' => $tipo
            ];
        }
        
        // Por defecto, limpiar el mensaje quitando paths y variables técnicas
        $mensajeLimpio = preg_replace('/C:\\\\[^\s]+/i', '', $mensaje); // Quitar paths de Windows
        $mensajeLimpio = preg_replace('/\/[^\s]+\//', '', $mensajeLimpio); // Quitar paths Unix
        $mensajeLimpio = preg_replace('/\{[^}]+\}/', '', $mensajeLimpio); // Quitar variables JSON
        $mensajeLimpio = trim($mensajeLimpio);
        
        return [
            'mensaje' => $mensajeLimpio ?: $mensaje,
            'tipo' => $tipo
        ];
    }

    /**
     * Limpiar mensajes de error quitando paths y detalles técnicos
     */
    private function limpiarMensajeError($mensaje)
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

    /**
     * Parsear fecha desde diferentes formatos
     */
    private function parseFecha($value)
    {
        if (empty($value)) {
            return null;
        }

        // Intentar diferentes formatos comunes
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Si es un timestamp de Excel
        if (is_numeric($value)) {
            try {
                $date = Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignorar
            }
        }

        return null;
    }

    /**
     * Descargar imagen desde Google Drive
     */
    private function downloadImageFromDrive($url)
    {
        try {
            // Extraer el ID del archivo de Google Drive
            $fileId = $this->extractGoogleDriveFileId($url);
            
            if (!$fileId) {
                \Log::warning('No se pudo extraer el ID de Google Drive de la URL: ' . $url);
                return null;
            }

            // Intentar diferentes métodos de descarga
            // Método 1: URL directa simple
            $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
            $imageContent = @file_get_contents($downloadUrl);
            
            // Si falla, intentar método alternativo
            if ($imageContent === false || empty($imageContent)) {
                $downloadUrl = "https://drive.google.com/uc?export=download&confirm=t&id={$fileId}";
                $imageContent = @file_get_contents($downloadUrl);
            }
            
            if ($imageContent === false || empty($imageContent)) {
                \Log::warning('No se pudo descargar la imagen de Google Drive. ID: ' . $fileId);
                return null;
            }

            // Verificar que sea una imagen válida
            $imageInfo = @getimagesizefromstring($imageContent);
            if ($imageInfo === false) {
                \Log::warning('El contenido descargado no es una imagen válida. ID: ' . $fileId);
                return null;
            }

            // Generar nombre único
            $extension = $this->getImageExtension($imageInfo['mime']);
            $uploadPath = public_path('uploads/conductores');

            // Crear directorio si no existe
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $filename = Str::uuid() . '.' . $extension;
            $fullPath = $uploadPath . '/' . $filename;

            // Guardar la imagen
            file_put_contents($fullPath, $imageContent);

            return 'uploads/conductores/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error descargando imagen de Google Drive: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer ID de archivo de URL de Google Drive
     */
    private function extractGoogleDriveFileId($url)
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
    private function getImageExtension($mimeType)
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

