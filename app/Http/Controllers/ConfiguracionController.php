<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ConfiguracionController extends Controller
{
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

        // Obtener información de tests
        $testInfo = $this->getTestInfo();

        return view('configuracion.index', compact('roles', 'modulos', 'modulosPorRol', 'healthStatus', 'testInfo'));
    }

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

    private function getTestInfo(): array
    {
        $info = [
            'total' => 0,
            'feature' => 0,
            'unit' => 0,
        ];

        try {
            $featurePath = base_path('tests/Feature');
            $unitPath = base_path('tests/Unit');

            if (File::exists($featurePath)) {
                $featureFiles = File::allFiles($featurePath);
                $info['feature'] = collect($featureFiles)
                    ->filter(fn ($file) => $file->getExtension() === 'php' && $file->getFilename() !== 'TestCase.php')
                    ->count();
            }

            if (File::exists($unitPath)) {
                $unitFiles = File::allFiles($unitPath);
                $info['unit'] = collect($unitFiles)
                    ->filter(fn ($file) => $file->getExtension() === 'php' && $file->getFilename() !== 'TestCase.php')
                    ->count();
            }

            $info['total'] = $info['feature'] + $info['unit'];
        } catch (\Exception $e) {
            $info['error'] = $e->getMessage();
        }

        return $info;
    }
}
