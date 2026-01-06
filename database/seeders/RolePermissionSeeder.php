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
            Permission::firstOrCreate(['name' => "ver {$module}"]);
            Permission::firstOrCreate(['name' => "crear {$module}"]);
            Permission::firstOrCreate(['name' => "editar {$module}"]);
            Permission::firstOrCreate(['name' => "eliminar {$module}"]);
        }

        // Permiso especial de configuración (solo para Mango)
        Permission::firstOrCreate(['name' => 'gestionar configuracion']);

        // Crear roles
        $roleMango = Role::firstOrCreate(['name' => 'Mango']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleUser = Role::firstOrCreate(['name' => 'User']);

        // Asignar todos los permisos a Mango
        $allPermissions = Permission::all();
        $roleMango->syncPermissions($allPermissions);

        // Asignar permisos a Admin (todos excepto configuración, pero incluyendo usuarios)
        $adminPermissions = Permission::where('name', '!=', 'gestionar configuracion')
            ->where('name', 'not like', '%configuracion%')
            ->get();
        $roleAdmin->syncPermissions($adminPermissions);

        // Asignar permisos básicos a User (solo ver)
        $userPermissions = Permission::where('name', 'like', 'ver %')
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

