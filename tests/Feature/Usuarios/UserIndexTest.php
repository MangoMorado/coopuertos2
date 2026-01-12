<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexTest extends TestCase
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

        // Asignar solo permiso de ver a User
        $userRole->givePermissionTo('ver usuarios');
    }

    public function test_user_with_permission_can_view_users_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/usuarios');

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    public function test_user_without_permission_cannot_view_users_index(): void
    {
        $user = User::factory()->create();
        // No asignar ningún rol o permiso

        $response = $this->actingAs($user)->get('/usuarios');

        $response->assertStatus(403);
    }

    public function test_users_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 20 usuarios para probar la paginación (15 por página)
        User::factory()->count(20)->create();

        $response = $this->actingAs($user)->get('/usuarios');

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $users);
        $this->assertEquals(15, $users->perPage());
        $this->assertGreaterThanOrEqual(1, $users->count());
    }

    public function test_users_index_can_search_by_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        User::factory()->create(['name' => 'Juan Pérez']);
        User::factory()->create(['name' => 'María González']);

        $response = $this->actingAs($user)->get('/usuarios?search=Juan');

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $this->assertTrue(
            $users->contains('name', 'Juan Pérez'),
            'Los resultados deben incluir usuarios con nombre que contenga "Juan"'
        );
        $this->assertFalse(
            $users->contains('name', 'María González'),
            'Los resultados no deben incluir usuarios que no coincidan con la búsqueda'
        );
    }

    public function test_users_index_can_search_by_email(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        User::factory()->create(['email' => 'juan@example.com']);
        User::factory()->create(['email' => 'maria@example.com']);

        $response = $this->actingAs($user)->get('/usuarios?search=juan@example.com');

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $this->assertTrue(
            $users->contains('email', 'juan@example.com'),
            'Los resultados deben incluir usuarios con email "juan@example.com"'
        );
        $this->assertFalse(
            $users->contains('email', 'maria@example.com'),
            'Los resultados no deben incluir usuarios que no coincidan con la búsqueda'
        );
    }

    public function test_users_index_includes_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $mangoUser = User::factory()->create(['name' => 'Mango User']);
        $mangoUser->assignRole('Mango');

        $adminUser = User::factory()->create(['name' => 'Admin User']);
        $adminUser->assignRole('Admin');

        $normalUser = User::factory()->create(['name' => 'Normal User']);
        $normalUser->assignRole('User');

        $response = $this->actingAs($user)->get('/usuarios');

        $response->assertStatus(200);
        $users = $response->viewData('users');

        // Verificar que la relación roles está cargada (no debería generar N+1 queries)
        $firstUser = $users->first();
        $this->assertTrue($firstUser->relationLoaded('roles'), 'La relación roles debe estar cargada mediante eager loading');

        // Verificar que los usuarios tienen sus roles asignados
        $mangoUserInResults = $users->firstWhere('name', 'Mango User');
        $this->assertNotNull($mangoUserInResults);
        $this->assertTrue($mangoUserInResults->hasRole('Mango'));

        $adminUserInResults = $users->firstWhere('name', 'Admin User');
        $this->assertNotNull($adminUserInResults);
        $this->assertTrue($adminUserInResults->hasRole('Admin'));

        $normalUserInResults = $users->firstWhere('name', 'Normal User');
        $this->assertNotNull($normalUserInResults);
        $this->assertTrue($normalUserInResults->hasRole('User'));
    }

    public function test_users_index_ajax_returns_json_response(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        User::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->get('/usuarios?ajax=1', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'pagination',
        ]);
    }
}
