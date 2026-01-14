<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiDocumentationAccessTest extends TestCase
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
        // Crear todos los permisos necesarios para los módulos
        $modulos = ['conductores', 'vehiculos', 'propietarios', 'carnets', 'dashboard', 'usuarios'];

        foreach ($modulos as $modulo) {
            Permission::firstOrCreate(['name' => "ver {$modulo}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "crear {$modulo}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "editar {$modulo}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "eliminar {$modulo}", 'guard_name' => 'web']);
        }

        // Crear roles si no existen
        $mangoRole = Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar todos los permisos a Mango
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $mangoRole->syncPermissions($allPermissions);
    }

    public function test_unauthenticated_user_cannot_access_api_documentation(): void
    {
        $response = $this->get('/api/documentation');

        // Debe redirigir al login o retornar 403
        $response->assertStatus(302);
    }

    public function test_admin_user_cannot_access_api_documentation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/api/documentation');

        $response->assertStatus(403);
    }

    public function test_user_role_cannot_access_api_documentation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/api/documentation');

        $response->assertStatus(403);
    }

    public function test_mango_user_can_access_api_documentation(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $response = $this->actingAs($mango)->get('/api/documentation');

        // La documentación puede retornar 200 o 302 (redirección) dependiendo de la configuración
        // Lo importante es que no retorne 403
        $this->assertNotEquals(403, $response->status());
    }
}
