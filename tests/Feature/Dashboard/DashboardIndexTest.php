<?php

namespace Tests\Feature\Dashboard;

use App\Models\Conductor;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardIndexTest extends TestCase
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
        Permission::firstOrCreate(['name' => 'ver dashboard', 'guard_name' => 'web']);

        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $mangoRole = Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);

        // Asignar permisos a Admin y User
        $adminRole->givePermissionTo('ver dashboard');
        $userRole->givePermissionTo('ver dashboard');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    public function test_unauthenticated_user_cannot_view_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_displays_conductores_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 5 conductores
        Conductor::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('conductoresCount', 5);
        $response->assertSee('5', false);
    }

    public function test_dashboard_displays_vehiculos_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 8 vehículos
        Vehicle::factory()->count(8)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('vehiculosCount', 8);
        $response->assertSee('8', false);
    }

    public function test_dashboard_displays_propietarios_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 12 propietarios
        Propietario::factory()->count(12)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('propietariosCount', 12);
        $response->assertSee('12', false);
    }

    public function test_dashboard_displays_usuarios_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear 3 usuarios adicionales (el usuario actual ya cuenta)
        User::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('usuariosCount', 4); // 1 actual + 3 creados
        $response->assertSee('4', false);
    }

    public function test_dashboard_displays_conductores_por_tipo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductores de tipo A (Camionetas) y B (Busetas)
        Conductor::factory()->count(3)->create(['conductor_tipo' => 'A']);
        Conductor::factory()->count(2)->create(['conductor_tipo' => 'B']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('conductoresPorTipoFormateado');

        $conductoresPorTipo = $response->viewData('conductoresPorTipoFormateado');
        $this->assertArrayHasKey('Camionetas', $conductoresPorTipo);
        $this->assertArrayHasKey('Busetas', $conductoresPorTipo);
        $this->assertEquals(3, $conductoresPorTipo['Camionetas']);
        $this->assertEquals(2, $conductoresPorTipo['Busetas']);
    }

    public function test_dashboard_displays_vehiculos_por_tipo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear vehículos de diferentes tipos
        Vehicle::factory()->count(4)->create(['tipo' => 'Bus']);
        Vehicle::factory()->count(3)->create(['tipo' => 'Camioneta']);
        Vehicle::factory()->count(2)->create(['tipo' => 'Taxi']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('vehiculosPorTipo');

        $vehiculosPorTipo = $response->viewData('vehiculosPorTipo');
        $this->assertEquals(4, $vehiculosPorTipo['Bus']);
        $this->assertEquals(3, $vehiculosPorTipo['Camioneta']);
        $this->assertEquals(2, $vehiculosPorTipo['Taxi']);
    }

    public function test_dashboard_displays_vehiculos_por_estado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear vehículos con diferentes estados
        Vehicle::factory()->count(5)->create(['estado' => 'Activo']);
        Vehicle::factory()->count(3)->create(['estado' => 'En Mantenimiento']);
        Vehicle::factory()->count(2)->create(['estado' => 'Fuera de Servicio']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('vehiculosEstadosConPorcentaje');

        $vehiculosPorEstado = $response->viewData('vehiculosEstadosConPorcentaje');
        $this->assertArrayHasKey('Activo', $vehiculosPorEstado);
        $this->assertArrayHasKey('En Mantenimiento', $vehiculosPorEstado);
        $this->assertArrayHasKey('Fuera de Servicio', $vehiculosPorEstado);
        $this->assertEquals(5, $vehiculosPorEstado['Activo']['total']);
        $this->assertEquals(3, $vehiculosPorEstado['En Mantenimiento']['total']);
        $this->assertEquals(2, $vehiculosPorEstado['Fuera de Servicio']['total']);
    }

    public function test_dashboard_displays_propietarios_por_tipo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear propietarios de diferentes tipos
        Propietario::factory()->count(6)->create(['tipo_propietario' => 'Persona Natural']);
        Propietario::factory()->count(4)->create(['tipo_propietario' => 'Persona Jurídica']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('propietariosPorTipo');

        $propietariosPorTipo = $response->viewData('propietariosPorTipo');
        $this->assertEquals(6, $propietariosPorTipo['Persona Natural']);
        $this->assertEquals(4, $propietariosPorTipo['Persona Jurídica']);
    }

    public function test_dashboard_displays_usuarios_por_rol(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear usuarios con diferentes roles
        $mangoUser = User::factory()->create();
        $mangoUser->assignRole('Mango');

        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');

        $userRole = User::factory()->create();
        $userRole->assignRole('User');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('usuariosPorRol');

        $usuariosPorRol = $response->viewData('usuariosPorRol');
        $this->assertArrayHasKey('Mango', $usuariosPorRol);
        $this->assertArrayHasKey('Admin', $usuariosPorRol);
        $this->assertArrayHasKey('User', $usuariosPorRol);
        $this->assertEquals(1, $usuariosPorRol['Mango']);
        $this->assertEquals(2, $usuariosPorRol['Admin']); // 1 actual + 1 creado
        $this->assertEquals(1, $usuariosPorRol['User']);
    }

    public function test_dashboard_displays_proximos_cumpleanos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductor con cumpleaños en 3 días
        // La fecha de nacimiento debe tener el mismo mes y día que hoy + 3 días, pero en un año pasado
        $fechaCumpleanos = now()->addDays(3);
        $fechaNacimiento = Carbon::create(
            $fechaCumpleanos->year - 30,
            $fechaCumpleanos->month,
            $fechaCumpleanos->day
        );

        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento,
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('proximosCumpleanos');

        $proximosCumpleanos = $response->viewData('proximosCumpleanos');
        $this->assertCount(1, $proximosCumpleanos);
        $this->assertEquals('Juan', $proximosCumpleanos->first()['nombres']);
        $this->assertEquals(3, $proximosCumpleanos->first()['dias_restantes']);
    }

    public function test_dashboard_filters_cumpleanos_within_7_days(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductores con cumpleaños en diferentes días
        // Dentro del rango (5 días)
        $fechaCumpleanos5 = now()->addDays(5);
        $fechaNacimiento5 = Carbon::create(
            $fechaCumpleanos5->year - 30,
            $fechaCumpleanos5->month,
            $fechaCumpleanos5->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento5,
            'nombres' => 'Dentro',
        ]);

        // Fuera del rango (10 días)
        $fechaCumpleanos10 = now()->addDays(10);
        $fechaNacimiento10 = Carbon::create(
            $fechaCumpleanos10->year - 30,
            $fechaCumpleanos10->month,
            $fechaCumpleanos10->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento10,
            'nombres' => 'Fuera',
        ]);

        // Justo en el límite (7 días)
        $fechaCumpleanos7 = now()->addDays(7);
        $fechaNacimiento7 = Carbon::create(
            $fechaCumpleanos7->year - 30,
            $fechaCumpleanos7->month,
            $fechaCumpleanos7->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento7,
            'nombres' => 'Limite',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $proximosCumpleanos = $response->viewData('proximosCumpleanos');

        // Debe incluir solo los que están dentro de 7 días
        $this->assertLessThanOrEqual(2, $proximosCumpleanos->count());

        $nombres = $proximosCumpleanos->pluck('nombres')->toArray();
        $this->assertContains('Dentro', $nombres);
        $this->assertContains('Limite', $nombres);
        $this->assertNotContains('Fuera', $nombres);
    }

    public function test_dashboard_cumpleanos_sorted_by_days_remaining(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear conductores con cumpleaños en diferentes días
        // 5 días
        $fechaCumpleanos5 = now()->addDays(5);
        $fechaNacimiento5 = Carbon::create(
            $fechaCumpleanos5->year - 30,
            $fechaCumpleanos5->month,
            $fechaCumpleanos5->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento5,
            'nombres' => 'Cinco',
        ]);

        // 2 días
        $fechaCumpleanos2 = now()->addDays(2);
        $fechaNacimiento2 = Carbon::create(
            $fechaCumpleanos2->year - 30,
            $fechaCumpleanos2->month,
            $fechaCumpleanos2->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento2,
            'nombres' => 'Dos',
        ]);

        // 7 días
        $fechaCumpleanos7 = now()->addDays(7);
        $fechaNacimiento7 = Carbon::create(
            $fechaCumpleanos7->year - 30,
            $fechaCumpleanos7->month,
            $fechaCumpleanos7->day
        );
        Conductor::factory()->create([
            'fecha_nacimiento' => $fechaNacimiento7,
            'nombres' => 'Siete',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $proximosCumpleanos = $response->viewData('proximosCumpleanos');

        // Debe estar ordenado por días restantes (ascendente)
        $diasRestantes = $proximosCumpleanos->pluck('dias_restantes')->toArray();
        $this->assertEquals([2, 5, 7], $diasRestantes);

        // Verificar que el orden de nombres coincide
        $nombres = $proximosCumpleanos->pluck('nombres')->toArray();
        $this->assertEquals(['Dos', 'Cinco', 'Siete'], $nombres);
    }

    public function test_dashboard_reflects_vehicle_status_changes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Crear vehículos con diferentes estados iniciales
        $vehiculo1 = Vehicle::factory()->create(['estado' => 'Activo']);
        $vehiculo2 = Vehicle::factory()->create(['estado' => 'En Mantenimiento']);
        $vehiculo3 = Vehicle::factory()->create(['estado' => 'Fuera de Servicio']);

        // Verificar estado inicial en el dashboard
        $response1 = $this->actingAs($user)->get('/dashboard');
        $response1->assertStatus(200);
        $vehiculosPorEstado1 = $response1->viewData('vehiculosEstadosConPorcentaje');
        $this->assertEquals(1, $vehiculosPorEstado1['Activo']['total']);
        $this->assertEquals(1, $vehiculosPorEstado1['En Mantenimiento']['total']);
        $this->assertEquals(1, $vehiculosPorEstado1['Fuera de Servicio']['total']);

        // Cambiar estado de un vehículo
        $vehiculo1->update(['estado' => 'Fuera de Servicio']);

        // Verificar que el dashboard refleja el cambio
        $response2 = $this->actingAs($user)->get('/dashboard');
        $response2->assertStatus(200);
        $vehiculosPorEstado2 = $response2->viewData('vehiculosEstadosConPorcentaje');
        
        // Verificar que el estado "Activo" ya no tiene vehículos (o no existe en el array)
        $activosTotal = $vehiculosPorEstado2['Activo']['total'] ?? 0;
        $this->assertEquals(0, $activosTotal);
        
        // Verificar que los otros estados se mantienen correctamente
        $this->assertEquals(1, $vehiculosPorEstado2['En Mantenimiento']['total']);
        $this->assertEquals(2, $vehiculosPorEstado2['Fuera de Servicio']['total']);
    }
}
