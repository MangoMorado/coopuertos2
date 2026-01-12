<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserEditTest extends TestCase
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

        // User no tiene permisos de editar usuarios
    }

    public function test_user_with_permission_can_view_edit_user_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $response = $this->actingAs($user)->get("/usuarios/{$targetUser->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user');
        $this->assertEquals($targetUser->id, $response->viewData('user')->id);
    }

    public function test_user_without_permission_cannot_view_edit_user_form(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de editar usuarios

        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/usuarios/{$targetUser->id}/edit");

        $response->assertStatus(403);
    }

    public function test_user_can_update_user_info(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'name' => 'Usuario Original',
            'email' => 'original@test.com',
            'theme' => 'light',
        ]);
        $targetUser->assignRole('User');

        $updateData = [
            'name' => 'Usuario Actualizado',
            'email' => 'actualizado@test.com',
            'role' => 'User',
            'theme' => 'dark',
        ];

        $response = $this->actingAs($user)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario actualizado exitosamente.');

        $targetUser->refresh();
        $this->assertEquals('Usuario Actualizado', $targetUser->name);
        $this->assertEquals('actualizado@test.com', $targetUser->email);
        $this->assertEquals('dark', $targetUser->theme);
    }

    public function test_user_can_update_user_role(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $updateData = [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'Admin',
            'theme' => $targetUser->theme ?? 'light',
        ];

        $response = $this->actingAs($mango)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('Admin'));
        $this->assertFalse($targetUser->hasRole('User'));
    }

    public function test_mango_can_update_user_to_any_role(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Test actualizar a rol User
        $targetUser1 = User::factory()->create();
        $targetUser1->assignRole('Admin');

        $updateData1 = [
            'name' => $targetUser1->name,
            'email' => $targetUser1->email,
            'role' => 'User',
            'theme' => $targetUser1->theme ?? 'light',
        ];

        $this->actingAs($mango)->put("/usuarios/{$targetUser1->id}", $updateData1);

        $targetUser1->refresh();
        $this->assertTrue($targetUser1->hasRole('User'));
        $this->assertFalse($targetUser1->hasRole('Admin'));

        // Test actualizar a rol Admin
        $targetUser2 = User::factory()->create();
        $targetUser2->assignRole('User');

        $updateData2 = [
            'name' => $targetUser2->name,
            'email' => $targetUser2->email,
            'role' => 'Admin',
            'theme' => $targetUser2->theme ?? 'light',
        ];

        $this->actingAs($mango)->put("/usuarios/{$targetUser2->id}", $updateData2);

        $targetUser2->refresh();
        $this->assertTrue($targetUser2->hasRole('Admin'));
        $this->assertFalse($targetUser2->hasRole('User'));

        // Test actualizar a rol Mango
        $targetUser3 = User::factory()->create();
        $targetUser3->assignRole('User');

        $updateData3 = [
            'name' => $targetUser3->name,
            'email' => $targetUser3->email,
            'role' => 'Mango',
            'theme' => $targetUser3->theme ?? 'light',
        ];

        $this->actingAs($mango)->put("/usuarios/{$targetUser3->id}", $updateData3);

        $targetUser3->refresh();
        $this->assertTrue($targetUser3->hasRole('Mango'));
        $this->assertFalse($targetUser3->hasRole('User'));
    }

    public function test_admin_can_only_update_user_to_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        // Admin puede actualizar usuario User manteniendo rol User
        $updateData = [
            'name' => 'Usuario Actualizado',
            'email' => $targetUser->email,
            'role' => 'User',
            'theme' => 'dark',
        ];

        $response = $this->actingAs($admin)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('User'));

        // Admin NO puede actualizar usuario User a rol Admin
        $updateDataAdmin = [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'Admin',
            'theme' => $targetUser->theme ?? 'light',
        ];

        $response = $this->actingAs($admin)->put("/usuarios/{$targetUser->id}", $updateDataAdmin);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No tienes permisos para asignar ese rol.');

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('User'));
        $this->assertFalse($targetUser->hasRole('Admin'));

        // Admin NO puede editar usuarios que no sean User
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');

        $updateDataMango = [
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'role' => 'User',
            'theme' => $adminUser->theme ?? 'light',
        ];

        $response = $this->actingAs($admin)->get("/usuarios/{$adminUser->id}/edit");

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No tienes permisos para editar este usuario.');
    }

    public function test_user_cannot_update_email_to_existing_one(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'email' => 'target@test.com',
        ]);
        $targetUser->assignRole('User');

        // Crear otro usuario con email diferente
        $existingUser = User::factory()->create([
            'email' => 'existente@test.com',
        ]);

        // Intentar actualizar el email del targetUser al email del existingUser
        $updateData = [
            'name' => $targetUser->name,
            'email' => 'existente@test.com', // Email de otro usuario
            'role' => 'User',
            'theme' => $targetUser->theme ?? 'light',
        ];

        $response = $this->actingAs($user)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertSessionHasErrors(['email']);

        // Verificar que el email no cambió
        $targetUser->refresh();
        $this->assertEquals('target@test.com', $targetUser->email);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);
        $targetUser->assignRole('User');

        $oldPasswordHash = $targetUser->password;

        $updateData = [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => 'User',
            'theme' => $targetUser->theme ?? 'light',
        ];

        $response = $this->actingAs($user)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $targetUser->refresh();

        // Verificar que la contraseña cambió
        $this->assertNotEquals($oldPasswordHash, $targetUser->password);

        // Verificar que la nueva contraseña funciona
        $this->assertTrue(Hash::check('newpassword123', $targetUser->password));
        $this->assertFalse(Hash::check('oldpassword', $targetUser->password));
    }

    public function test_user_can_update_without_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'password' => Hash::make('originalpassword'),
        ]);
        $targetUser->assignRole('User');

        $originalPasswordHash = $targetUser->password;

        $updateData = [
            'name' => 'Usuario Actualizado',
            'email' => 'actualizado@test.com',
            'role' => 'User',
            'theme' => 'dark',
            // No incluir password
        ];

        $response = $this->actingAs($user)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));

        $targetUser->refresh();

        // Verificar que la contraseña no cambió
        $this->assertEquals($originalPasswordHash, $targetUser->password);

        // Verificar que otros campos sí cambiaron
        $this->assertEquals('Usuario Actualizado', $targetUser->name);
        $this->assertEquals('actualizado@test.com', $targetUser->email);
        $this->assertEquals('dark', $targetUser->theme);
    }

    public function test_user_can_update_with_same_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $targetUser = User::factory()->create([
            'name' => 'Usuario Original',
            'email' => 'usuario@test.com',
        ]);
        $targetUser->assignRole('User');

        $updateData = [
            'name' => 'Usuario Actualizado',
            'email' => 'usuario@test.com', // Mismo email (permitido)
            'role' => 'User',
            'theme' => 'dark',
        ];

        $response = $this->actingAs($user)->put("/usuarios/{$targetUser->id}", $updateData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHasNoErrors();

        $targetUser->refresh();
        $this->assertEquals('Usuario Actualizado', $targetUser->name);
        $this->assertEquals('usuario@test.com', $targetUser->email);
    }
}
