<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreateTest extends TestCase
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

        // User no tiene permisos de crear usuarios
    }

    public function test_user_with_permission_can_view_create_user_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/usuarios/create');

        $response->assertStatus(200);
        $response->assertViewIs('users.create');
    }

    public function test_user_without_permission_cannot_view_create_user_form(): void
    {
        $user = User::factory()->create();
        // No asignar permiso de crear usuarios

        $response = $this->actingAs($user)->get('/usuarios/create');

        $response->assertStatus(403);
    }

    public function test_mango_can_create_user_with_any_role(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Test crear usuario con rol User
        $userData = [
            'name' => 'Usuario Test',
            'email' => 'usuario@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'User',
            'theme' => 'light',
        ];

        $response = $this->actingAs($mango)->post('/usuarios', $userData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'usuario@test.com',
            'name' => 'Usuario Test',
        ]);

        $createdUser = User::where('email', 'usuario@test.com')->first();
        $this->assertTrue($createdUser->hasRole('User'));

        // Test crear usuario con rol Admin
        $adminData = [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Admin',
            'theme' => 'light',
        ];

        $response = $this->actingAs($mango)->post('/usuarios', $adminData);

        $response->assertRedirect(route('usuarios.index'));
        $createdAdmin = User::where('email', 'admin@test.com')->first();
        $this->assertTrue($createdAdmin->hasRole('Admin'));

        // Test crear usuario con rol Mango
        $mangoData = [
            'name' => 'Mango Test',
            'email' => 'mango@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Mango',
            'theme' => 'light',
        ];

        $response = $this->actingAs($mango)->post('/usuarios', $mangoData);

        $response->assertRedirect(route('usuarios.index'));
        $createdMango = User::where('email', 'mango@test.com')->first();
        $this->assertTrue($createdMango->hasRole('Mango'));
    }

    public function test_admin_can_only_create_user_with_user_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Admin puede crear usuario con rol User
        $userData = [
            'name' => 'Usuario Test',
            'email' => 'usuario@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'User',
            'theme' => 'light',
        ];

        $response = $this->actingAs($admin)->post('/usuarios', $userData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $createdUser = User::where('email', 'usuario@test.com')->first();
        $this->assertTrue($createdUser->hasRole('User'));

        // Admin NO puede crear usuario con rol Admin
        $adminData = [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Admin',
            'theme' => 'light',
        ];

        $response = $this->actingAs($admin)->post('/usuarios', $adminData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No tienes permisos para crear usuarios con ese rol.');

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@test.com',
        ]);

        // Admin NO puede crear usuario con rol Mango
        $mangoData = [
            'name' => 'Mango Test',
            'email' => 'mango@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Mango',
            'theme' => 'light',
        ];

        $response = $this->actingAs($admin)->post('/usuarios', $mangoData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'No tienes permisos para crear usuarios con ese rol.');

        $this->assertDatabaseMissing('users', [
            'email' => 'mango@test.com',
        ]);
    }

    public function test_user_cannot_create_user_without_required_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Test sin campos requeridos
        $response = $this->actingAs($user)->post('/usuarios', []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        // Verificar que no se creó ningún usuario
        $this->assertDatabaseCount('users', 1); // Solo el usuario autenticado
    }

    public function test_user_cannot_create_user_with_duplicate_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear un usuario existente
        $existingUser = User::factory()->create(['email' => 'existente@test.com']);

        $userData = [
            'name' => 'Usuario Test',
            'email' => 'existente@test.com', // Email duplicado
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'User',
            'theme' => 'light',
        ];

        $response = $this->actingAs($user)->post('/usuarios', $userData);

        $response->assertSessionHasErrors(['email']);

        // Verificar que no se creó un nuevo usuario
        $this->assertDatabaseCount('users', 2); // Solo el usuario autenticado y el existente
    }

    public function test_user_created_with_selected_role(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Crear usuario con rol User
        $userData = [
            'name' => 'Usuario Test',
            'email' => 'usuario@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'User',
            'theme' => 'light',
        ];

        $this->actingAs($mango)->post('/usuarios', $userData);

        $createdUser = User::where('email', 'usuario@test.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole('User'));
        $this->assertFalse($createdUser->hasRole('Admin'));
        $this->assertFalse($createdUser->hasRole('Mango'));

        // Crear usuario con rol Admin
        $adminData = [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Admin',
            'theme' => 'light',
        ];

        $this->actingAs($mango)->post('/usuarios', $adminData);

        $createdAdmin = User::where('email', 'admin@test.com')->first();
        $this->assertNotNull($createdAdmin);
        $this->assertTrue($createdAdmin->hasRole('Admin'));
        $this->assertFalse($createdAdmin->hasRole('User'));
        $this->assertFalse($createdAdmin->hasRole('Mango'));

        // Crear usuario con rol Mango
        $mangoData = [
            'name' => 'Mango Test',
            'email' => 'mango@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Mango',
            'theme' => 'light',
        ];

        $this->actingAs($mango)->post('/usuarios', $mangoData);

        $createdMango = User::where('email', 'mango@test.com')->first();
        $this->assertNotNull($createdMango);
        $this->assertTrue($createdMango->hasRole('Mango'));
        $this->assertFalse($createdMango->hasRole('User'));
        $this->assertFalse($createdMango->hasRole('Admin'));
    }

    public function test_user_created_successfully_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $userData = [
            'name' => 'Usuario Test',
            'email' => 'usuario@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'User',
            'theme' => 'light',
        ];

        $response = $this->actingAs($user)->post('/usuarios', $userData);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario creado exitosamente.');
    }
}
