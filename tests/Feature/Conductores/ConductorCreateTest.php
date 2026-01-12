<?php

namespace Tests\Feature\Conductores;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorCreateTest extends TestCase
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

    public function test_user_with_permission_can_view_create_conductor_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/conductores/create');

        $response->assertStatus(200);
        $response->assertViewIs('conductores.create');
    }

    public function test_user_without_permission_cannot_view_create_conductor_form(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de crear

        $response = $this->actingAs($user)->get('/conductores/create');

        $response->assertStatus(403);
    }

    public function test_user_can_create_conductor_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'numero_interno' => '123',
            'celular' => '3001234567',
            'correo' => 'juan@example.com',
            'fecha_nacimiento' => '1990-01-01',
            'nivel_estudios' => 'Universitario',
            'estado' => 'activo',
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('conductors', [
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor);
        $this->assertNotNull($conductor->uuid);
    }

    public function test_user_cannot_create_conductor_without_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->post('/conductores', []);

        $response->assertSessionHasErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);
    }

    public function test_user_cannot_create_conductor_with_duplicate_cedula(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un conductor existente
        Conductor::factory()->create(['cedula' => '1234567890']);

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890', // Cédula duplicada
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertSessionHasErrors(['cedula']);
        $this->assertDatabaseCount('conductors', 1);
    }

    public function test_user_can_create_conductor_with_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Storage::fake('public');

        $photo = UploadedFile::fake()->image('conductor.jpg', 100, 100);

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
            'foto' => $photo,
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor);
        $this->assertNotNull($conductor->foto);
        $this->assertStringStartsWith('data:image/', $conductor->foto);
    }

    public function test_user_can_create_conductor_without_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor);
        $this->assertNull($conductor->foto);
    }

    public function test_conductor_uuid_is_generated_automatically(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $this->actingAs($user)->post('/conductores', $data);

        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertNotNull($conductor->uuid);
        $this->assertNotEmpty($conductor->uuid);
        // UUID tiene formato válido (36 caracteres con guiones)
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $conductor->uuid);
    }

    public function test_conductor_created_successfully_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHas('success', 'Conductor creado correctamente.');
    }

    public function test_conductor_created_with_empty_email_sets_default_value(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $data = [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
            'correo' => '', // Correo vacío
        ];

        $response = $this->actingAs($user)->post('/conductores', $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor = Conductor::where('cedula', '1234567890')->first();
        $this->assertEquals('No tiene', $conductor->correo);
    }
}
