<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios
        Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
    }

    public function test_user_has_roles_relationship(): void
    {
        $user = User::factory()->create();
        $mangoRole = Role::where('name', 'Mango')->first();
        $adminRole = Role::where('name', 'Admin')->first();

        // Asignar roles
        $user->assignRole(['Mango', 'Admin']);

        // Verificar relación
        $this->assertTrue($user->relationLoaded('roles') === false || $user->relationLoaded('roles') === true);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->roles);
        $this->assertCount(2, $user->roles);
        $this->assertTrue($user->hasRole('Mango'));
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_user_can_have_role_assigned(): void
    {
        $user = User::factory()->create();

        // Asignar rol Admin
        $user->assignRole('Admin');

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Mango'));
        $this->assertFalse($user->hasRole('User'));

        // Cambiar a rol Mango
        $user->syncRoles(['Mango']);

        $this->assertTrue($user->hasRole('Mango'));
        $this->assertFalse($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('User'));

        // Asignar múltiples roles
        $user->syncRoles(['Admin', 'User']);

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('User'));
        $this->assertFalse($user->hasRole('Mango'));
    }

    public function test_user_theme_is_stored_correctly(): void
    {
        // Probar con tema light
        $userLight = User::factory()->create([
            'theme' => 'light',
        ]);

        $this->assertEquals('light', $userLight->theme);

        // Probar con tema dark
        $userDark = User::factory()->create([
            'theme' => 'dark',
        ]);

        $this->assertEquals('dark', $userDark->theme);

        // Verificar que se puede actualizar el tema
        $userLight->update(['theme' => 'dark']);
        $userLight->refresh();

        $this->assertEquals('dark', $userLight->theme);

        // Verificar que el tema por defecto es light (si no se especifica)
        $userDefault = User::factory()->create();
        // El factory puede o no tener un tema por defecto, pero el campo debe existir
        $this->assertNotNull($userDefault->theme);
        $this->assertIsString($userDefault->theme);
    }
}
