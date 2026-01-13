<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Controlador web para configuración del sistema
 *
 * Gestiona la configuración de permisos por rol y módulo, mostrando el estado
 * actual de permisos y permitiendo su actualización. Mango siempre tiene todos
 * los permisos, mientras que Admin y User tienen permisos basados en módulos activos.
 */
class ConfiguracionController extends Controller
{
    /**
     * Muestra la página de configuración de permisos
     *
     * Obtiene los roles del sistema (Mango, Admin, User), los módulos disponibles
     * y el estado actual de permisos por rol y módulo. También obtiene el estado
     * de salud del sistema para mostrar en la vista.
     *
     * @return \Illuminate\Contracts\View\View Vista de configuración de permisos
     */
    public function index()
    {
        $roles = Role::whereIn('name', ['Mango', 'Admin', 'User'])->where('guard_name', 'web')->get();

        // Módulos disponibles
        $modulos = [
            'conductores' => 'Conductores',
            'vehiculos' => 'Vehículos',
            'propietarios' => 'Propietarios',
            'carnets' => 'Carnets',
            'dashboard' => 'Dashboard',
            'usuarios' => 'Usuarios',
        ];

        // Obtener permisos actuales de cada rol por módulo
        $modulosPorRol = [];
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $modulosPorRol[$role->name] = [];

            foreach ($modulos as $modulo => $nombre) {
                // Un módulo está activo si el rol tiene al menos el permiso "ver"
                $modulosPorRol[$role->name][$modulo] = in_array("ver {$modulo}", $rolePermissions);
            }
        }

        // Obtener información de salud del sistema
        $healthCheckService = new HealthCheckService;
        $healthStatus = $healthCheckService->getHealthStatus();

        return view('configuracion.index', compact('roles', 'modulos', 'modulosPorRol', 'healthStatus'));
    }

    /**
     * Actualiza los permisos del sistema por rol y módulo
     *
     * Actualiza los permisos de cada rol basándose en los módulos activos.
     * Mango siempre recibe todos los permisos. Admin y User reciben permisos
     * básicos (ver, crear, editar, eliminar) solo para los módulos activos.
     * Usa transacciones de base de datos para garantizar consistencia.
     *
     * @param  Request  $request  Request HTTP con array 'modulos' indexado por nombre de rol
     * @return \Illuminate\Http\RedirectResponse Redirección a la página de configuración
     *
     * @throws \Exception Si hay errores al actualizar los permisos
     */
    public function update(Request $request)
    {
        $request->validate([
            'modulos' => 'required|array',
            'modulos.*' => 'array',
        ]);

        DB::beginTransaction();
        try {
            $roles = Role::whereIn('name', ['Mango', 'Admin', 'User'])->where('guard_name', 'web')->get();

            foreach ($roles as $role) {
                $roleName = $role->name;

                // Mango siempre tiene todos los permisos
                if ($roleName === 'Mango') {
                    $allPermissions = Permission::all();
                    $role->syncPermissions($allPermissions);

                    continue;
                }

                // Para Admin y User, aplicar permisos basados en módulos activos
                $permisosSeleccionados = [];

                if (isset($request->modulos[$roleName])) {
                    $modulosActivos = $request->modulos[$roleName];

                    foreach ($modulosActivos as $modulo) {
                        // Si el módulo está activo, dar todos los permisos básicos
                        $permisosSeleccionados[] = Permission::where('name', "ver {$modulo}")->where('guard_name', 'web')->first();
                        $permisosSeleccionados[] = Permission::where('name', "crear {$modulo}")->where('guard_name', 'web')->first();
                        $permisosSeleccionados[] = Permission::where('name', "editar {$modulo}")->where('guard_name', 'web')->first();
                        $permisosSeleccionados[] = Permission::where('name', "eliminar {$modulo}")->where('guard_name', 'web')->first();
                    }
                }

                // Filtrar nulls por si algún permiso no existe
                $permisosSeleccionados = array_filter($permisosSeleccionados);

                $role->syncPermissions($permisosSeleccionados);
            }

            DB::commit();

            return redirect()->route('configuracion.index')
                ->with('success', 'Permisos actualizados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('configuracion.index')
                ->with('error', 'Error al actualizar permisos: '.$e->getMessage());
        }
    }
}
