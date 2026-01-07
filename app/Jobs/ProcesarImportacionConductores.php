<?php

namespace App\Jobs;

use App\Models\Conductor;
use App\Models\ImportLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ProcesarImportacionConductores implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

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
        
        if (!$importLog) {
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
                'logs' => [['mensaje' => 'Iniciando procesamiento en segundo plano...', 'tipo' => 'info']]
            ]);
            
            // Determinar ruta completa del archivo
            $fullPath = storage_path('app/' . $this->filePath);
            
            if (!file_exists($fullPath)) {
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
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✅ Importación completada exitosamente', 'tipo' => 'success']])
            ]);
            
            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en Job de importación: ' . $e->getMessage(), [
                'session_id' => $this->sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            
            $importLog->update([
                'estado' => 'error',
                'mensaje' => 'Error: ' . $e->getMessage(),
                'completed_at' => now(),
                'logs' => array_merge($importLog->logs ?? [], [['mensaje' => '✗ Error: ' . $e->getMessage(), 'tipo' => 'error']])
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
        if (!$handle) {
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
            $headers = array_map(function($h) {
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
                if (!isset($indexMap[$field])) {
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
        
        DB::beginTransaction();
        
        try {
            while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $registrosProcesados++;
                
                // Procesar registro (usar método similar al del controlador)
                $resultado = $this->procesarRegistro($row, $indexMap, $registrosProcesados);
                
                if ($resultado['tipo'] === 'importado') {
                    $importados++;
                } elseif ($resultado['tipo'] === 'duplicado') {
                    $duplicados++;
                    $errores[] = $resultado['mensaje'];
                } elseif ($resultado['tipo'] === 'error') {
                    $errores[] = $resultado['mensaje'];
                }
                
                // Actualizar progreso cada 10 registros
                if ($registrosProcesados % 10 == 0) {
                    $this->actualizarImportLog($importLog, [
                        'procesados' => $registrosProcesados,
                        'importados' => $importados,
                        'duplicados' => $duplicados,
                        'errores_count' => count($errores),
                        'errores' => array_slice($errores, -50), // Últimos 50 errores
                        'progreso' => min(90, ($registrosProcesados * 90 / max($registrosProcesados * 2, 1))),
                        'mensaje' => "Procesando... {$registrosProcesados} registros (Importados: {$importados}, Duplicados: {$duplicados}, Errores: " . count($errores) . ")",
                        'logs' => array_merge($logs, [['mensaje' => "📦 Procesados {$registrosProcesados} registros", 'tipo' => 'info']])
                    ]);
                }
            }
            
            DB::commit();
            
            // Actualización final
            $this->actualizarImportLog($importLog, [
                'procesados' => $registrosProcesados,
                'total' => $registrosProcesados,
                'importados' => $importados,
                'duplicados' => $duplicados,
                'errores_count' => count($errores),
                'errores' => $errores,
                'progreso' => 95,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Procesar un registro individual
     */
    private function procesarRegistro(array $row, array $indexMap, int $lineaNumero): array
    {
        try {
            // Extraer datos según el mapeo
            $data = [];
            foreach ($indexMap as $field => $index) {
                $value = $row[$index] ?? null;
                
                if ($value !== null) {
                    $value = trim((string) $value);
                    
                    // Procesar según el campo (lógica simplificada del controlador)
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
                                $data['rh'] = null;
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
                    }
                }
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
                    'mensaje' => "Fila {$lineaNumero}: Conductor duplicado - Cédula {$data['cedula']}"
                ];
            }
            
            // Crear conductor
            Conductor::create($data);
            
            return [
                'tipo' => 'importado',
                'mensaje' => "Fila {$lineaNumero}: Importado exitosamente"
            ];
            
        } catch (\Exception $e) {
            return [
                'tipo' => 'error',
                'mensaje' => "Fila {$lineaNumero}: " . $e->getMessage()
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
            if (count($logsExistentes) > 100) {
                $logsExistentes = array_slice($logsExistentes, -100);
            }
            $datos['logs'] = $logsExistentes;
        }
        
        $importLog->update($datos);
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
}
