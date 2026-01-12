<?php

namespace Tests\Feature\Configuracion;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
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

        // Permiso especial de configuración
        Permission::firstOrCreate(['name' => 'gestionar configuracion', 'guard_name' => 'web']);

        // Crear roles si no existen
        $mangoRole = Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar todos los permisos a Mango
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $mangoRole->syncPermissions($allPermissions);
    }

    public function test_mango_can_view_configuracion_page(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $response = $this->actingAs($mango)->get('/configuracion');

        $response->assertStatus(200);
        $response->assertViewIs('configuracion.index');
        $response->assertViewHas(['roles', 'modulos', 'modulosPorRol', 'healthStatus']);
    }

    public function test_non_mango_cannot_view_configuracion_page(): void
    {
        // Admin sin permiso de gestionar configuracion
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/configuracion');

        $response->assertStatus(403);

        // User sin permiso de gestionar configuracion
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/configuracion');

        $response->assertStatus(403);
    }

    public function test_configuracion_displays_roles_and_modules(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $response = $this->actingAs($mango)->get('/configuracion');

        $response->assertStatus(200);

        $roles = $response->viewData('roles');
        $modulos = $response->viewData('modulos');

        // Verificar que se muestran los roles (excepto Mango que no se muestra en el formulario)
        $this->assertNotNull($roles);
        $roleNames = $roles->pluck('name')->toArray();
        $this->assertContains('Mango', $roleNames);
        $this->assertContains('Admin', $roleNames);
        $this->assertContains('User', $roleNames);

        // Verificar que se muestran todos los módulos
        $this->assertNotNull($modulos);
        $this->assertArrayHasKey('conductores', $modulos);
        $this->assertArrayHasKey('vehiculos', $modulos);
        $this->assertArrayHasKey('propietarios', $modulos);
        $this->assertArrayHasKey('carnets', $modulos);
        $this->assertArrayHasKey('dashboard', $modulos);
        $this->assertArrayHasKey('usuarios', $modulos);
    }

    public function test_configuracion_displays_current_permissions(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Crear un Admin con algunos permisos
        $adminRole = Role::where('name', 'Admin')->first();
        $adminRole->givePermissionTo(['ver conductores', 'crear conductores', 'ver vehiculos']);

        $response = $this->actingAs($mango)->get('/configuracion');

        $response->assertStatus(200);

        $modulosPorRol = $response->viewData('modulosPorRol');

        // Verificar que los permisos actuales se muestran correctamente
        $this->assertNotNull($modulosPorRol);
        $this->assertArrayHasKey('Admin', $modulosPorRol);
        $this->assertArrayHasKey('User', $modulosPorRol);

        // Admin tiene ver conductores, entonces conductores debe estar activo
        $this->assertTrue($modulosPorRol['Admin']['conductores'] ?? false, 'Admin debe tener el módulo conductores activo');
        $this->assertTrue($modulosPorRol['Admin']['vehiculos'] ?? false, 'Admin debe tener el módulo vehiculos activo');
    }

    public function test_configuracion_displays_health_status(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $response = $this->actingAs($mango)->get('/configuracion');

        $response->assertStatus(200);

        $healthStatus = $response->viewData('healthStatus');

        // Verificar que se incluye información de salud
        $this->assertNotNull($healthStatus);
        $this->assertArrayHasKey('database', $healthStatus);
        $this->assertArrayHasKey('queue', $healthStatus);
        $this->assertArrayHasKey('storage', $healthStatus);
        $this->assertArrayHasKey('versions', $healthStatus);
        $this->assertArrayHasKey('php_extensions', $healthStatus);

        // Verificar estructura básica de cada sección
        $this->assertArrayHasKey('status', $healthStatus['database']);
        $this->assertArrayHasKey('status', $healthStatus['queue']);
        $this->assertArrayHasKey('status', $healthStatus['storage']);
        $this->assertArrayHasKey('php', $healthStatus['versions']);
        $this->assertArrayHasKey('laravel', $healthStatus['versions']);
        $this->assertArrayHasKey('extensions', $healthStatus['php_extensions']);
    }

    public function test_mango_can_update_permissions(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        // Inicialmente, Admin no tiene permisos de conductores
        $adminRole->syncPermissions([]);

        $response = $this->actingAs($mango)->put('/configuracion', [
            'modulos' => [
                'Admin' => ['conductores', 'vehiculos'],
                'User' => ['conductores'],
            ],
        ]);

        $response->assertRedirect(route('configuracion.index'));
        $response->assertSessionHas('success', 'Permisos actualizados correctamente.');

        // Verificar que los permisos se actualizaron correctamente
        $adminRole->refresh();
        $adminPermissions = $adminRole->permissions->pluck('name')->toArray();

        $this->assertContains('ver conductores', $adminPermissions);
        $this->assertContains('crear conductores', $adminPermissions);
        $this->assertContains('editar conductores', $adminPermissions);
        $this->assertContains('eliminar conductores', $adminPermissions);
        $this->assertContains('ver vehiculos', $adminPermissions);
        $this->assertContains('crear vehiculos', $adminPermissions);
        $this->assertContains('editar vehiculos', $adminPermissions);
        $this->assertContains('eliminar vehiculos', $adminPermissions);

        // Verificar que User tiene permisos de conductores
        $userRole->refresh();
        $userPermissions = $userRole->permissions->pluck('name')->toArray();

        $this->assertContains('ver conductores', $userPermissions);
        $this->assertContains('crear conductores', $userPermissions);
        $this->assertContains('editar conductores', $userPermissions);
        $this->assertContains('eliminar conductores', $userPermissions);
    }

    public function test_mango_cannot_modify_mango_permissions(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $mangoRole = Role::where('name', 'Mango')->first();
        $initialPermissionsCount = $mangoRole->permissions->count();

        // Intentar actualizar (el controlador debe ignorar Mango y mantener todos los permisos)
        $response = $this->actingAs($mango)->put('/configuracion', [
            'modulos' => [
                'Admin' => ['conductores'],
                'Mango' => ['vehiculos'], // Esto no debería tener efecto
            ],
        ]);

        $response->assertRedirect(route('configuracion.index'));
        $response->assertSessionHas('success');

        // Verificar que Mango mantiene todos los permisos
        $mangoRole->refresh();
        $allPermissionsCount = Permission::where('guard_name', 'web')->count();

        $this->assertEquals($allPermissionsCount, $mangoRole->permissions->count());
        $this->assertGreaterThanOrEqual($initialPermissionsCount, $mangoRole->permissions->count());
    }

    public function test_permission_update_affects_user_access(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');

        $adminRole = Role::where('name', 'Admin')->first();

        // Inicialmente, Admin no tiene permisos de conductores
        $adminRole->syncPermissions([]);

        // Admin no puede acceder a conductores
        $response = $this->actingAs($adminUser)->get('/conductores');
        $response->assertStatus(403);

        // Mango actualiza permisos para dar acceso a conductores
        $this->actingAs($mango)->put('/configuracion', [
            'modulos' => [
                'Admin' => ['conductores'],
            ],
        ]);

        // Admin ahora puede acceder a conductores
        $response = $this->actingAs($adminUser)->get('/conductores');
        $response->assertStatus(200);
    }

    public function test_permission_update_persists_correctly(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        $adminRole = Role::where('name', 'Admin')->first();

        // Actualizar permisos
        $this->actingAs($mango)->put('/configuracion', [
            'modulos' => [
                'Admin' => ['conductores', 'vehiculos', 'carnets'],
            ],
        ]);

        // Recargar desde la base de datos
        $adminRole->refresh();
        DB::connection()->getPdo(); // Asegurar conexión

        // Verificar persistencia
        $adminPermissions = $adminRole->permissions->pluck('name')->toArray();

        $expectedPermissions = [
            'ver conductores', 'crear conductores', 'editar conductores', 'eliminar conductores',
            'ver vehiculos', 'crear vehiculos', 'editar vehiculos', 'eliminar vehiculos',
            'ver carnets', 'crear carnets', 'editar carnets', 'eliminar carnets',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $adminPermissions, "El permiso {$permission} debe estar presente");
        }
    }

    public function test_configuracion_update_validates_data(): void
    {
        $mango = User::factory()->create();
        $mango->assignRole('Mango');

        // Intentar actualizar sin el campo requerido 'modulos'
        $response = $this->actingAs($mango)->put('/configuracion', []);

        $response->assertSessionHasErrors('modulos');

        // Intentar actualizar con formato incorrecto
        $response = $this->actingAs($mango)->put('/configuracion', [
            'modulos' => 'not-an-array',
        ]);

        $response->assertSessionHasErrors('modulos');
    }
}
