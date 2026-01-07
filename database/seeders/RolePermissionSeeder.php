<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos por módulo
        $modules = [
            'conductores',
            'vehiculos',
            'propietarios',
            'carnets',
            'dashboard',
            'usuarios',
            'configuracion',
        ];

        foreach ($modules as $module) {
            Permission::firstOrCreate(['name' => "ver {$module}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "crear {$module}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "editar {$module}", 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => "eliminar {$module}", 'guard_name' => 'web']);
        }

        // Permiso especial de configuración (solo para Mango)
        Permission::firstOrCreate(['name' => 'gestionar configuracion', 'guard_name' => 'web']);

        // Crear roles
        $roleMango = Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $roleUser = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // Asignar todos los permisos a Mango
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $roleMango->syncPermissions($allPermissions);

        // Asignar permisos a Admin (todos excepto configuración, pero incluyendo usuarios)
        $adminPermissions = Permission::where('guard_name', 'web')
            ->where('name', '!=', 'gestionar configuracion')
            ->where('name', 'not like', '%configuracion%')
            ->get();
        $roleAdmin->syncPermissions($adminPermissions);

        // Asignar permisos básicos a User (solo ver)
        $userPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'like', 'ver %')
            ->where('name', 'not like', '%configuracion%')
            ->get();
        $roleUser->syncPermissions($userPermissions);

        // Asignar rol Mango al primer usuario si existe
        $firstUser = User::first();
        if ($firstUser && !$firstUser->hasAnyRole(['Mango', 'Admin', 'User'])) {
            $firstUser->assignRole('Mango');
        }
    }
}

