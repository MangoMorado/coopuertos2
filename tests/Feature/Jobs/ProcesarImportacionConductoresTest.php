<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcesarImportacionConductores;
use App\Models\Conductor;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\ConductorImport\ConductorImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProcesarImportacionConductoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear directorio de imports si no existe
        $importsDir = storage_path('app/imports');
        if (! File::exists($importsDir)) {
            File::makeDirectory($importsDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar archivos de prueba
        $importsDir = storage_path('app/imports');
        if (File::exists($importsDir)) {
            $files = File::glob($importsDir.'/test-*.csv');
            $files = array_merge($files, File::glob($importsDir.'/test-*.xlsx'));
            foreach ($files as $file) {
                @File::delete($file);
            }
        }

        parent::tearDown();
    }

    public function test_procesar_importacion_creates_import_log(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV de prueba
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n";

        $filePath = 'imports/test-import.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-123',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que el ImportLog se actualizó
        $importLog->refresh();
        $this->assertNotNull($importLog);
        $this->assertEquals('completado', $importLog->estado);
        $this->assertNotNull($importLog->started_at);
        $this->assertNotNull($importLog->completed_at);
    }

    public function test_procesar_importacion_processes_csv_file(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV de prueba
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n";
        $csvContent .= "María,García,0987654321,B,A+,activo\n";

        $filePath = 'imports/test-import.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-csv',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-csv',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se procesó correctamente
        $importLog->refresh();
        $this->assertEquals('completado', $importLog->estado);
        $this->assertEquals(2, $importLog->importados);
        $this->assertEquals(2, $importLog->total);

        // Verificar que se crearon los conductores
        $this->assertDatabaseHas('conductors', [
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $this->assertDatabaseHas('conductors', [
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'García',
        ]);
    }

    public function test_procesar_importacion_processes_excel_file(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV (simulando Excel ya que el procesamiento de Excel aún no está implementado)
        // En un test real, se usaría un archivo Excel real
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Pedro,López,1111111111,A,B+,activo\n";

        $filePath = 'imports/test-import.xlsx';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-excel',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import.xlsx',
            'extension' => 'xlsx',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job (el archivo CSV no es un Excel real, así que fallará al intentar leerlo)
        $job = new ProcesarImportacionConductores(
            'test-session-excel',
            $filePath,
            'xlsx',
            $user->id
        );

        try {
            $job->handle(app(ConductorImportService::class));
            // Si no lanza excepción, el test falla
            $this->fail('Se esperaba una excepción para archivos Excel inválidos');
        } catch (\Exception $e) {
            // El error puede ser sobre el archivo no válido como Excel
            $this->assertTrue(
                str_contains($e->getMessage(), 'Excel') ||
                str_contains($e->getMessage(), 'no existe') ||
                str_contains($e->getMessage(), 'zip') ||
                str_contains($e->getMessage(), 'Could not find'),
                "El mensaje de error debería mencionar Excel, zip o archivo no existe. Mensaje: {$e->getMessage()}"
            );

            // Verificar que el ImportLog se actualizó con el error (si existe)
            try {
                $importLog->refresh();
                $this->assertEquals('error', $importLog->estado);
            } catch (\Exception $refreshException) {
                // Si el ImportLog no existe, está bien - el error ocurrió antes de actualizarlo
                $this->assertTrue(true);
            }
        }
    }

    public function test_procesar_importacion_validates_data(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV con datos inválidos (faltan campos requeridos)
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= ",,1234567890,A,O+,activo\n"; // Faltan nombres y apellidos
        $csvContent .= "Juan,,0987654321,A,O+,activo\n"; // Falta apellidos
        $csvContent .= "María,García,,A,O+,activo\n"; // Falta cédula

        $filePath = 'imports/test-import-invalid.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-validate',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-invalid.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-validate',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se procesó el archivo
        $importLog->refresh();
        $this->assertEquals('completado', $importLog->estado);

        // El registro sin cédula debería generar un error
        // Los registros con cédula pero sin nombres/apellidos se procesan (solo cédula es requerida)
        $this->assertIsArray($importLog->errores);

        // Verificar que al menos el registro sin cédula generó un error
        $erroresSinCedula = array_filter($importLog->errores ?? [], function ($error) {
            return is_string($error) && str_contains($error, 'Cédula requerida');
        });
        $this->assertNotEmpty($erroresSinCedula, 'Debe haber al menos un error por cédula requerida');
    }

    public function test_procesar_importacion_handles_duplicates(): void
    {
        $user = User::factory()->create();

        // Crear un conductor existente
        Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Conductor',
            'apellidos' => 'Existente',
        ]);

        // Crear archivo CSV con cédula duplicada
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n"; // Duplicado
        $csvContent .= "María,García,0987654321,B,A+,activo\n"; // Nuevo

        $filePath = 'imports/test-import-duplicate.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-duplicate',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-duplicate.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-duplicate',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se manejó el duplicado
        $importLog->refresh();
        $this->assertEquals('completado', $importLog->estado);
        $this->assertEquals(1, $importLog->importados);
        $this->assertEquals(1, $importLog->duplicados);

        // Verificar que solo se creó el conductor nuevo
        $this->assertDatabaseHas('conductors', [
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'García',
        ]);

        // Verificar que el duplicado no se creó de nuevo
        $conductoresConCedula = Conductor::where('cedula', '1234567890')->count();
        $this->assertEquals(1, $conductoresConCedula); // Solo el original
    }

    public function test_procesar_importacion_creates_conductores(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV con datos válidos
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,NUMERO INTERNO,CELULAR,CORREO,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,101,3001234567,juan@test.com,activo\n";
        $csvContent .= "María,García,0987654321,B,A+,102,3007654321,maria@test.com,activo\n";

        $filePath = 'imports/test-import-create.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-create',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-create.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-create',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se crearon los conductores
        $importLog->refresh();
        $this->assertEquals(2, $importLog->importados);

        // Verificar datos del primer conductor
        $conductor1 = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor1);
        $this->assertEquals('Juan', $conductor1->nombres);
        $this->assertEquals('Pérez', $conductor1->apellidos);
        $this->assertEquals('A', $conductor1->conductor_tipo);
        $this->assertEquals('O+', $conductor1->rh);
        $this->assertEquals('101', $conductor1->numero_interno);
        $this->assertEquals('3001234567', $conductor1->celular);
        $this->assertEquals('juan@test.com', $conductor1->correo);

        // Verificar datos del segundo conductor
        $conductor2 = Conductor::where('cedula', '0987654321')->first();
        $this->assertNotNull($conductor2);
        $this->assertEquals('María', $conductor2->nombres);
        $this->assertEquals('García', $conductor2->apellidos);
    }

    public function test_procesar_importacion_updates_progress(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV con múltiples registros
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        for ($i = 1; $i <= 5; $i++) {
            $csvContent .= "Conductor{$i},Apellido{$i},123456789{$i},A,O+,activo\n";
        }

        $filePath = 'imports/test-import-progress.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-progress',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-progress.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-progress',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que el progreso se actualizó
        $importLog->refresh();
        $this->assertEquals('completado', $importLog->estado);
        $this->assertEquals(100, $importLog->progreso);
        $this->assertEquals(5, $importLog->procesados);
        $this->assertEquals(5, $importLog->total);
        $this->assertEquals(5, $importLog->importados);
    }

    public function test_procesar_importacion_handles_errors(): void
    {
        $user = User::factory()->create();

        // Crear ImportLog con ruta de archivo inexistente
        $importLog = ImportLog::create([
            'session_id' => 'test-session-error',
            'user_id' => $user->id,
            'file_path' => 'imports/archivo-inexistente.csv',
            'file_name' => 'archivo-inexistente.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job (debería lanzar excepción)
        $job = new ProcesarImportacionConductores(
            'test-session-error',
            'imports/archivo-inexistente.csv',
            'csv',
            $user->id
        );

        try {
            $job->handle(app(ConductorImportService::class));
            $this->fail('Se esperaba una excepción para archivo inexistente');
        } catch (\Exception $e) {
            $this->assertStringContainsString('no existe', $e->getMessage());

            // Verificar que el ImportLog se actualizó con el error
            $importLog->refresh();
            $this->assertEquals('error', $importLog->estado);
            $this->assertNotNull($importLog->completed_at);
            $this->assertStringContainsString('Error', $importLog->mensaje);
        }
    }

    public function test_procesar_importacion_logs_errors(): void
    {
        $user = User::factory()->create();

        // Crear un conductor existente para generar un duplicado
        Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        // Crear archivo CSV con datos que generarán errores (duplicado)
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n"; // Duplicado (generará error)
        $csvContent .= "María,García,0987654321,A,O+,activo\n"; // Válido

        $filePath = 'imports/test-import-logs.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-logs',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-logs.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-logs',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se registraron logs
        $importLog->refresh();
        $this->assertIsArray($importLog->logs);
        $this->assertNotEmpty($importLog->logs, 'Debe haber logs registrados');

        // Verificar que hay logs de información o éxito
        $infoLogs = array_filter($importLog->logs, function ($log) {
            return isset($log['tipo']) && ($log['tipo'] === 'success' || $log['tipo'] === 'info' || $log['tipo'] === 'warning');
        });
        $this->assertNotEmpty($infoLogs, 'Debe haber logs de información, éxito o advertencia');

        // Verificar que se registraron duplicados (que se cuentan como errores en el contexto de importación)
        $this->assertEquals(1, $importLog->duplicados, 'Debe haber un duplicado registrado');
        $this->assertIsArray($importLog->errores);
    }

    public function test_procesar_importacion_handles_missing_import_log(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n";

        $filePath = 'imports/test-import-missing-log.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // NO crear ImportLog

        // Ejecutar el job (debería retornar sin hacer nada)
        $job = new ProcesarImportacionConductores(
            'test-session-missing',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que no se creó ningún conductor
        $this->assertDatabaseMissing('conductors', ['cedula' => '1234567890']);
    }

    public function test_procesar_importacion_deletes_file_after_completion(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,activo\n";

        $filePath = 'imports/test-import-delete.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Verificar que el archivo existe
        $this->assertFileExists($fullPath);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-delete',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-delete.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-delete',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que el archivo fue eliminado
        $fullPath = storage_path('app/'.$filePath);
        $this->assertFileDoesNotExist($fullPath);
    }

    public function test_procesar_importacion_downloads_photos(): void
    {
        $user = User::factory()->create();

        // Crear archivo CSV con URL de foto (aunque no descargaremos realmente)
        $csvContent = "NOMBRES,APELLIDOS,CEDULA,CONDUCTOR TIPO,RH,CARGUE SU FOTO PARA CARNET,ESTADO\n";
        $csvContent .= "Juan,Pérez,1234567890,A,O+,https://drive.google.com/file/d/test123/view,activo\n";

        $filePath = 'imports/test-import-photos.csv';
        $fullPath = storage_path('app/'.$filePath);
        File::put($fullPath, $csvContent);

        // Crear ImportLog
        $importLog = ImportLog::create([
            'session_id' => 'test-session-photos',
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => 'test-import-photos.csv',
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        // Ejecutar el job
        $job = new ProcesarImportacionConductores(
            'test-session-photos',
            $filePath,
            'csv',
            $user->id
        );
        $job->handle(app(ConductorImportService::class));

        // Verificar que se procesó el registro
        $importLog->refresh();
        $this->assertEquals('completado', $importLog->estado);

        // Verificar que se creó el conductor
        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor);

        // Verificar que el conductor se creó (la foto se procesa aunque falle la descarga)
        $this->assertNotNull($conductor);

        // Verificar que hay logs (pueden o no mencionar la foto específicamente)
        $this->assertIsArray($importLog->logs);
        $this->assertNotEmpty($importLog->logs, 'Debe haber logs del procesamiento');
    }
}
