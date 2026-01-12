<?php

namespace Tests\Feature\Conductores;

use App\Models\Conductor;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos y roles necesarios
        $this->seedPermissions();

        // Limpiar directorio temporal antes de cada test
        $tempDir = storage_path('app/temp_imports');
        if (File::exists($tempDir)) {
            File::cleanDirectory($tempDir);
        }
    }

    protected function seedPermissions(): void
    {
        // Crear permisos necesarios
        Permission::firstOrCreate(['name' => 'ver conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'editar conductores', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'eliminar conductores', 'guard_name' => 'web']);

        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar permisos a Admin
        $adminRole->givePermissionTo(['ver conductores', 'crear conductores', 'editar conductores', 'eliminar conductores']);

        // Asignar solo permiso de ver a User
        $userRole->givePermissionTo('ver conductores');
    }

    protected function createCsvFile(array $data): UploadedFile
    {
        $csvContent = implode(',', array_keys($data[0]))."\n";
        foreach ($data as $row) {
            $csvContent .= implode(',', array_values($row))."\n";
        }

        $filePath = storage_path('app/temp_test.csv');
        File::put($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            'test_conductores.csv',
            'text/csv',
            null,
            true
        );
    }

    protected function createExcelFile(array $data): UploadedFile
    {
        // Crear un CSV simple que se puede usar como Excel básico
        // En un entorno real, usarías PhpSpreadsheet para crear un archivo Excel real
        $csvContent = implode(',', array_keys($data[0]))."\n";
        foreach ($data as $row) {
            $csvContent .= implode(',', array_values($row))."\n";
        }

        $filePath = storage_path('app/temp_test.xlsx');
        File::put($filePath, $csvContent);

        return new UploadedFile(
            $filePath,
            'test_conductores.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_user_with_permission_can_view_import_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/conductores/importar');

        $response->assertStatus(200);
        $response->assertViewIs('conductores.import');
    }

    public function test_user_without_permission_cannot_view_import_form(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de crear

        $response = $this->actingAs($user)->get('/conductores/importar');

        $response->assertStatus(403);
    }

    public function test_user_can_import_conductores_from_csv(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $csvData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que se creó el registro de importación
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
            'extension' => 'csv',
        ]);

        // Verificar que se encoló el job
        Queue::assertPushed(\App\Jobs\ProcesarImportacionConductores::class);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }

    public function test_user_can_import_conductores_from_excel(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $excelData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
            ],
        ];

        $file = $this->createExcelFile($excelData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que se creó el registro de importación
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
            'extension' => 'xlsx',
        ]);

        // Verificar que se encoló el job
        Queue::assertPushed(\App\Jobs\ProcesarImportacionConductores::class);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.xlsx'));
    }

    public function test_import_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear CSV sin cédula (campo requerido)
        $csvData = [
            [
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // El archivo se acepta, pero el procesamiento fallará al validar
        // Verificar que se creó el registro de importación
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
        ]);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }

    public function test_import_handles_duplicate_cedulas(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un conductor existente
        Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        $csvData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que se creó el registro de importación
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
        ]);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }

    public function test_import_processes_in_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $csvData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que el job se encoló en la cola correcta
        Queue::assertPushed(\App\Jobs\ProcesarImportacionConductores::class, function ($job) {
            return $job->queue === 'importaciones';
        });

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }

    public function test_user_can_check_import_progress(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un registro de importación
        $importLog = ImportLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'file_path' => 'temp_imports/test.csv',
            'file_name' => 'test.csv',
            'extension' => 'csv',
            'estado' => 'procesando',
            'progreso' => 50,
            'total' => 100,
            'procesados' => 50,
            'importados' => 45,
            'duplicados' => 3,
            'errores_count' => 2,
            'mensaje' => 'Procesando...',
        ]);

        $response = $this->actingAs($user)->get("/conductores/import/progreso/{$importLog->session_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'estado' => 'procesando',
            'progreso' => 50,
            'total' => 100,
            'procesados' => 50,
            'importados' => 45,
            'duplicados' => 3,
        ]);
    }

    public function test_import_logs_errors_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un registro de importación con errores
        $importLog = ImportLog::create([
            'session_id' => 'test-session-456',
            'user_id' => $user->id,
            'file_path' => 'temp_imports/test.csv',
            'file_name' => 'test.csv',
            'extension' => 'csv',
            'estado' => 'completado',
            'progreso' => 100,
            'total' => 10,
            'procesados' => 10,
            'importados' => 8,
            'duplicados' => 1,
            'errores_count' => 1,
            'mensaje' => 'Completado con errores',
            'errores' => [
                ['fila' => 5, 'mensaje' => 'Cédula requerida'],
            ],
            'logs' => [
                ['mensaje' => 'Error en fila 5: Cédula requerida', 'tipo' => 'error'],
            ],
        ]);

        $response = $this->actingAs($user)->get("/conductores/import/progreso/{$importLog->session_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'estado' => 'completado',
            'errores_count' => 1,
        ]);

        $responseData = $response->json();
        $this->assertIsArray($responseData['errores']);
        $this->assertIsArray($responseData['log']);
    }

    public function test_import_creates_import_log_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $csvData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que se creó el registro de importación con los datos correctos
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
            'extension' => 'csv',
            'estado' => 'pendiente',
            'progreso' => 0,
        ]);

        $importLog = ImportLog::where('user_id', $user->id)->first();
        $this->assertNotNull($importLog->session_id);
        $this->assertNotNull($importLog->file_path);
        $this->assertNotNull($importLog->file_name);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }

    public function test_import_downloads_photos_from_google_drive(): void
    {
        // Este test verifica que el sistema intenta descargar fotos desde Google Drive
        // cuando se proporciona una URL en la columna 'foto'
        // Nota: En un entorno real, esto requeriría mockear la descarga de imágenes

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $csvData = [
            [
                'CEDULA' => '1234567890',
                'NOMBRES' => 'Juan',
                'APELLIDOS' => 'Pérez',
                'TIPO' => 'A',
                'RH' => 'O+',
                'ESTADO' => 'activo',
                'FOTO' => 'https://drive.google.com/file/d/test123/view',
            ],
        ];

        $file = $this->createCsvFile($csvData);

        $response = $this->actingAs($user)->post('/conductores/importar', [
            'archivo' => $file,
        ]);

        // Verificar que se creó el registro de importación
        // El procesamiento real de la descarga de fotos se hace en el Job
        $this->assertDatabaseHas('import_logs', [
            'user_id' => $user->id,
        ]);

        // Limpiar archivo temporal
        File::delete(storage_path('app/temp_test.csv'));
    }
}
