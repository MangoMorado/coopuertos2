<?php

namespace Tests\Feature\Conductores;

use App\Exports\ConductoresExport;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos y roles necesarios
        $this->seedPermissions();
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

    public function test_user_can_export_conductores_to_excel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear algunos conductores de prueba
        Conductor::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/conductores/exportar');

        $response->assertStatus(200);

        // Verificar que la respuesta es una descarga de archivo
        $this->assertTrue(
            $response->headers->has('Content-Disposition'),
            'La respuesta debe incluir el header Content-Disposition para descarga'
        );

        // Verificar que el tipo de contenido es Excel
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType ?? '');
    }

    public function test_exported_excel_contains_all_conductores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductores de prueba con datos específicos
        $conductor1 = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'González',
        ]);

        $conductor3 = Conductor::factory()->create([
            'cedula' => '1122334455',
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
        ]);

        // Verificar que todos los conductores existen en la base de datos
        $this->assertDatabaseHas('conductors', ['cedula' => '1234567890']);
        $this->assertDatabaseHas('conductors', ['cedula' => '0987654321']);
        $this->assertDatabaseHas('conductors', ['cedula' => '1122334455']);

        // Verificar que el export contiene todos los conductores
        $export = new ConductoresExport;
        $collection = $export->collection();

        $this->assertGreaterThanOrEqual(3, $collection->count());

        // Verificar que cada conductor está en la colección
        $cedulas = $collection->pluck('cedula')->toArray();
        $this->assertContains('1234567890', $cedulas);
        $this->assertContains('0987654321', $cedulas);
        $this->assertContains('1122334455', $cedulas);
    }

    public function test_exported_excel_has_correct_columns(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un conductor de prueba
        Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'numero_interno' => '123',
            'celular' => '3001234567',
            'correo' => 'juan@example.com',
            'otra_profesion' => 'Ingeniero',
            'nivel_estudios' => 'Universitario',
            'relevo' => true,
            'estado' => 'activo',
        ]);

        // Verificar que el export tiene las columnas correctas
        $export = new ConductoresExport;
        $headings = $export->headings();

        $expectedColumns = [
            'Cédula',
            'Nombres',
            'Apellidos',
            'Tipo',
            'RH',
            'Número Interno',
            'Vehículo',
            'Celular',
            'Correo',
            'Fecha de Nacimiento',
            'Otra Profesión',
            'Nivel de Estudios',
            'Relevo',
            'Estado',
        ];

        $this->assertEquals($expectedColumns, $headings);
        $this->assertCount(14, $headings);
    }
}
