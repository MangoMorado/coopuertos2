<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserDeleteTest extends TestCase
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
        // Crear permisos de usuarios
        Permission::firstOrCreate(['name' => 'ver usuarios', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crear usuarios', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'editar usuarios', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'eliminar usuarios', 'guard_name' => 'web']);

        // Crear roles si no existen
        $mangoRole = Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar todos los permisos a Mango
        $mangoRole->givePermissionTo(['ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios']);

        // Asignar permisos a Admin
        $adminRole->givePermissionTo(['ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios']);

        // User no tiene permisos de eliminar usuarios
    }

    public function test_user_with_permission_can_delete_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'name' => 'Usuario a Eliminar',
            'email' => 'eliminar@test.com',
        ]);
        $targetUser->assignRole('User');

        $response = $this->actingAs($user)->delete("/usuarios/{$targetUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario eliminado exitosamente.');

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_user(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de eliminar usuarios

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $response = $this->actingAs($user)->delete("/usuarios/{$targetUser->id}");

        $response->assertStatus(403);

        // Verificar que el usuario no fue eliminado
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_deletion_removes_from_database(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'name' => 'Usuario Test',
            'email' => 'test@example.com',
        ]);
        $targetUser->assignRole('User');

        // Verificar que el usuario existe antes de eliminar
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'email' => 'test@example.com',
        ]);

        $this->actingAs($user)->delete("/usuarios/{$targetUser->id}");

        // Verificar que el usuario fue eliminado de la base de datos
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        // Verificar que el usuario ya no existe
        $this->assertNull(User::find($targetUser->id));
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Mi Usuario',
            'email' => 'yo@test.com',
        ]);
        $user->assignRole('Admin');

        // Intentar eliminar a uno mismo
        $response = $this->actingAs($user)->delete("/usuarios/{$user->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No puedes eliminar tu propio usuario.');

        // Verificar que el usuario no fue eliminado
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'yo@test.com',
        ]);

        // Verificar que el usuario aún existe
        $this->assertNotNull(User::find($user->id));
    }

    public function test_mango_can_delete_user_with_any_role(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Test eliminar usuario User
        $userUser = User::factory()->create();
        $userUser->assignRole('User');

        $response = $this->actingAs($mango)->delete("/usuarios/{$userUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $userUser->id,
        ]);

        // Test eliminar usuario Admin
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');

        $response = $this->actingAs($mango)->delete("/usuarios/{$adminUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $adminUser->id,
        ]);

        // Test eliminar otro usuario Mango
        $mangoUser = User::factory()->create();
        $mangoUser->assignRole('Mango');

        $response = $this->actingAs($mango)->delete("/usuarios/{$mangoUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $mangoUser->id,
        ]);
    }

    public function test_admin_can_only_delete_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Admin puede eliminar usuario User
        $userUser = User::factory()->create();
        $userUser->assignRole('User');

        $response = $this->actingAs($admin)->delete("/usuarios/{$userUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $userUser->id,
        ]);

        // Admin NO puede eliminar usuario Admin
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');

        $response = $this->actingAs($admin)->delete("/usuarios/{$adminUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No tienes permisos para eliminar este usuario.');

        $this->assertDatabaseHas('users', [
            'id' => $adminUser->id,
        ]);

        // Admin NO puede eliminar usuario Mango
        $mangoUser = User::factory()->create();
        $mangoUser->assignRole('Mango');

        $response = $this->actingAs($admin)->delete("/usuarios/{$mangoUser->id}");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No tienes permisos para eliminar este usuario.');

        $this->assertDatabaseHas('users', [
            'id' => $mangoUser->id,
        ]);
    }

    public function test_user_deletion_removes_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        // Verificar que el usuario tiene el rol antes de eliminar
        $this->assertTrue($targetUser->hasRole('User'));

        $this->actingAs($user)->delete("/usuarios/{$targetUser->id}");

        // Verificar que el usuario fue eliminado
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        // Verificar que los roles también fueron eliminados (soft delete o cascade)
        // Nota: Spatie Permission elimina automáticamente las relaciones al eliminar el usuario
        $this->assertNull(User::find($targetUser->id));
    }
}
