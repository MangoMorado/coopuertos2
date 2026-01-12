<?php

namespace Tests\Feature\Conductores;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConductorDeleteTest extends TestCase
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

    public function test_user_with_permission_can_delete_conductor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
        ]);

        $response = $this->actingAs($user)->delete("/conductores/{$conductor->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Conductor eliminado.');

        $this->assertDatabaseMissing('conductors', [
            'id' => $conductor->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_conductor(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de eliminar

        $conductor = Conductor::factory()->create();

        $response = $this->actingAs($user)->delete("/conductores/{$conductor->id}");

        $response->assertStatus(403);

        // Verificar que el conductor no fue eliminado
        $this->assertDatabaseHas('conductors', [
            'id' => $conductor->id,
        ]);
    }

    public function test_conductor_deletion_removes_from_database(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $this->assertDatabaseHas('conductors', [
            'id' => $conductor->id,
            'cedula' => '1234567890',
        ]);

        $this->actingAs($user)->delete("/conductores/{$conductor->id}");

        $this->assertDatabaseMissing('conductors', [
            'id' => $conductor->id,
        ]);

        // Verificar que el conductor ya no existe
        $this->assertNull(Conductor::find($conductor->id));
    }

    public function test_conductor_deletion_removes_associated_files(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear directorio de carnets si no existe
        $carnetsDir = storage_path('app/carnets');
        if (! File::exists($carnetsDir)) {
            File::makeDirectory($carnetsDir, 0755, true);
        }

        // Crear un archivo de carnet simulado
        $rutaCarnet = 'carnets/test_carnet_1234567890.pdf';
        $rutaCompleta = storage_path('app/'.$rutaCarnet);
        File::put($rutaCompleta, 'contenido del carnet');

        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'ruta_carnet' => $rutaCarnet,
        ]);

        // Verificar que el archivo existe antes de eliminar
        $this->assertFileExists($rutaCompleta);

        // Eliminar el conductor
        $this->actingAs($user)->delete("/conductores/{$conductor->id}");

        // Verificar que el archivo fue eliminado (el Observer debería eliminarlo)
        // Nota: El Observer se ejecuta cuando se elimina el modelo
        $this->assertFileDoesNotExist($rutaCompleta);
    }

    public function test_conductor_deletion_handles_missing_carnet_file(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductor con ruta_carnet pero sin archivo físico
        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'ruta_carnet' => 'carnets/non_existent_file.pdf',
        ]);

        // El Observer debería manejar esto sin errores
        $response = $this->actingAs($user)->delete("/conductores/{$conductor->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que el conductor fue eliminado
        $this->assertDatabaseMissing('conductors', [
            'id' => $conductor->id,
        ]);
    }
}
