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

class ConductorEditTest extends TestCase
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

    public function test_user_with_permission_can_view_edit_conductor_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
        ]);

        $response = $this->actingAs($user)->get("/conductores/{$conductor->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('conductores.edit');
        $response->assertViewHas('conductor');
        $this->assertEquals($conductor->id, $response->viewData('conductor')->id);
    }

    public function test_user_without_permission_cannot_view_edit_conductor_form(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de editar

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->get("/conductores/{$conductor->id}/edit");

        $response->assertStatus(403);
    }

    public function test_user_can_update_conductor_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'cedula' => '1234567890',
            'conductor_tipo' => 'A',
            'rh' => 'O+',
            'estado' => 'activo',
        ]);

        $data = [
            'nombres' => 'Pedro',
            'apellidos' => 'García',
            'cedula' => '1234567890', // Misma cédula (permitida en update)
            'conductor_tipo' => 'B',
            'rh' => 'A+',
            'celular' => '3007654321',
            'correo' => 'pedro@example.com',
            'estado' => 'inactivo',
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHas('success', 'Conductor actualizado correctamente.');

        $conductor->refresh();
        $this->assertEquals('Pedro', $conductor->nombres);
        $this->assertEquals('García', $conductor->apellidos);
        $this->assertEquals('B', $conductor->conductor_tipo);
        $this->assertEquals('A+', $conductor->rh);
        $this->assertEquals('3007654321', $conductor->celular);
        $this->assertEquals('pedro@example.com', $conductor->correo);
        $this->assertEquals('inactivo', $conductor->estado);
    }

    public function test_user_cannot_update_conductor_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", []);

        $response->assertSessionHasErrors(['nombres', 'apellidos', 'cedula', 'conductor_tipo', 'rh', 'estado']);
    }

    public function test_user_can_update_conductor_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Storage::fake('public');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'foto' => null,
        ]);

        $photo = UploadedFile::fake()->image('nueva-foto.jpg', 100, 100);

        $data = [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => $conductor->estado,
            'foto' => $photo,
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor->refresh();
        $this->assertNotNull($conductor->foto);
        $this->assertStringStartsWith('data:image/', $conductor->foto);
    }

    public function test_user_can_update_conductor_without_changing_photo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $originalPhoto = 'data:image/jpeg;base64,test-photo-data';
        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'foto' => $originalPhoto,
        ]);

        $data = [
            'nombres' => 'Pedro',
            'apellidos' => 'García',
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => $conductor->estado,
            // No incluir foto
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor->refresh();
        $this->assertEquals('Pedro', $conductor->nombres);
        $this->assertEquals('García', $conductor->apellidos);
        // La foto original debería mantenerse
        $this->assertEquals($originalPhoto, $conductor->foto);
    }

    public function test_user_cannot_update_conductor_cedula_to_existing_one(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor1 = Conductor::factory()->create(['cedula' => '1111111111']);
        $conductor2 = Conductor::factory()->create(['cedula' => '2222222222']);

        $data = [
            'nombres' => $conductor2->nombres,
            'apellidos' => $conductor2->apellidos,
            'cedula' => '1111111111', // Cédula de otro conductor
            'conductor_tipo' => $conductor2->conductor_tipo,
            'rh' => $conductor2->rh,
            'estado' => $conductor2->estado,
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor2->id}", $data);

        $response->assertSessionHasErrors(['cedula']);

        // Verificar que la cédula no cambió
        $conductor2->refresh();
        $this->assertEquals('2222222222', $conductor2->cedula);
    }

    public function test_user_can_update_conductor_with_same_cedula(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
        ]);

        $data = [
            'nombres' => 'Pedro',
            'apellidos' => $conductor->apellidos,
            'cedula' => '1234567890', // Misma cédula (permitida)
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => $conductor->estado,
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHasNoErrors();

        $conductor->refresh();
        $this->assertEquals('Pedro', $conductor->nombres);
        $this->assertEquals('1234567890', $conductor->cedula);
    }

    public function test_user_can_change_conductor_status_without_email_when_conductor_has_no_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'correo' => 'No tiene',
            'estado' => 'activo',
        ]);

        // Cambiar solo el estado sin proporcionar correo
        $data = [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => 'inactivo', // Cambiar estado
            // No incluir correo
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHasNoErrors();

        $conductor->refresh();
        $this->assertEquals('inactivo', $conductor->estado);
        $this->assertEquals('No tiene', $conductor->correo);
    }

    public function test_user_can_change_conductor_status_without_email_when_conductor_email_is_null(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'correo' => null,
            'estado' => 'activo',
        ]);

        // Cambiar solo el estado sin proporcionar correo
        $data = [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => 'inactivo', // Cambiar estado
            // No incluir correo
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));
        $response->assertSessionHasNoErrors();

        $conductor->refresh();
        $this->assertEquals('inactivo', $conductor->estado);
        $this->assertEquals('No tiene', $conductor->correo);
    }

    public function test_update_conductor_with_empty_email_sets_default_value(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'correo' => 'juan@example.com',
        ]);

        $data = [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'estado' => $conductor->estado,
            'correo' => '', // Correo vacío
        ];

        $response = $this->actingAs($user)->patch("/conductores/{$conductor->id}", $data);

        $response->assertRedirect(route('conductores.index'));

        $conductor->refresh();
        $this->assertEquals('No tiene', $conductor->correo);
    }
}
