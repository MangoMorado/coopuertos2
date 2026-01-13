<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;

/**
 * Controlador web para el dashboard principal
 *
 * Proporciona estadísticas y métricas generales del sistema incluyendo
 * conteos, distribuciones por tipo/estado/rol, y próximos cumpleaños de
 * conductores. Utiliza consultas optimizadas para mejor rendimiento.
 */
class DashboardController extends Controller
{
    /**
     * Muestra la página principal del dashboard
     *
     * Obtiene estadísticas optimizadas del sistema, calcula próximos cumpleaños
     * de conductores (próximos 7 días), y prepara todos los datos para la vista.
     *
     * @return \Illuminate\Contracts\View\View Vista del dashboard principal
     */
    public function index()
    {
        // Obtener estadísticas optimizadas
        $stats = $this->getDashboardStats();

        // Conductores
        $conductoresCount = $stats['conductores']['count'];
        $conductoresPorTipoFormateado = $stats['conductores']['por_tipo'];

        // Próximos cumpleaños de conductores (solo los que cumplen en 7 días o menos)
        $proximosCumpleanos = Conductor::whereNotNull('fecha_nacimiento')
            ->select('id', 'nombres', 'apellidos', 'cedula', 'fecha_nacimiento', 'conductor_tipo')
            ->get()
            ->map(function ($conductor) {
                $fechaNacimiento = $conductor->fecha_nacimiento;
                $hoy = now()->startOfDay();
                $anoActual = $hoy->year;
                $anoSiguiente = $anoActual + 1;

                // Crear fecha de cumpleaños para este año y el siguiente (sin hora)
                $cumpleanosEsteAno = $fechaNacimiento->copy()->year($anoActual)->startOfDay();
                $cumpleanosSiguienteAno = $fechaNacimiento->copy()->year($anoSiguiente)->startOfDay();

                // Determinar qué fecha usar
                // Si el cumpleaños de este año es hoy o en el futuro, usar ese
                // Si ya pasó, usar el del año siguiente
                if ($cumpleanosEsteAno->isBefore($hoy)) {
                    $proximoCumpleanos = $cumpleanosSiguienteAno;
                } else {
                    $proximoCumpleanos = $cumpleanosEsteAno;
                }

                // Calcular días hasta el cumpleaños (siempre positivo o 0)
                $diasRestantes = max(0, $hoy->diffInDays($proximoCumpleanos, false));

                return [
                    'id' => $conductor->id,
                    'nombres' => $conductor->nombres,
                    'apellidos' => $conductor->apellidos,
                    'cedula' => $conductor->cedula,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'tipo' => $conductor->conductor_tipo === 'A' ? 'Camionetas' : 'Busetas',
                    'proximo_cumpleanos' => $proximoCumpleanos,
                    'dias_restantes' => $diasRestantes,
                    'edad' => $proximoCumpleanos->year - $fechaNacimiento->year,
                ];
            })
            ->filter(function ($conductor) {
                // Solo incluir si el cumpleaños es en los próximos 7 días o menos
                return $conductor['dias_restantes'] <= 7;
            })
            ->sortBy('dias_restantes')
            ->values();

        // Vehículos
        $vehiculosCount = $stats['vehiculos']['count'];
        $vehiculosPorTipo = $stats['vehiculos']['por_tipo'];
        $vehiculosEstadosConPorcentaje = $stats['vehiculos']['por_estado'];

        // Propietarios
        $propietariosCount = $stats['propietarios']['count'];
        $propietariosPorTipo = $stats['propietarios']['por_tipo'];

        // Usuarios
        $usuariosCount = $stats['usuarios']['count'];
        $usuariosPorRol = $stats['usuarios']['por_rol'];

        return view('dashboard', compact(
            'conductoresCount',
            'conductoresPorTipoFormateado',
            'proximosCumpleanos',
            'vehiculosCount',
            'vehiculosPorTipo',
            'vehiculosEstadosConPorcentaje',
            'propietariosCount',
            'propietariosPorTipo',
            'usuariosCount',
            'usuariosPorRol'
        ));
    }

    /**
     * Obtiene estadísticas del dashboard optimizadas
     *
     * Combina múltiples consultas en menos queries para mejor rendimiento.
     * Retorna conteos y distribuciones de conductores, vehículos, propietarios
     * y usuarios agrupados por tipo, estado o rol.
     *
     * @return array<string, array<string, mixed>> Array con estadísticas organizadas por entidad
     */
    protected function getDashboardStats(): array
    {
        // Conductores - Obtener count y agrupación en consultas separadas pero optimizadas
        $conductoresCount = Conductor::count();
        $conductoresPorTipo = Conductor::selectRaw('conductor_tipo, COUNT(*) as total')
            ->groupBy('conductor_tipo')
            ->pluck('total', 'conductor_tipo')
            ->toArray();

        // Mapear tipos de conductores para mostrar
        $conductoresPorTipoFormateado = [];
        foreach ($conductoresPorTipo as $tipo => $total) {
            $nombreTipo = $tipo === 'A' ? 'Camionetas' : 'Busetas';
            $conductoresPorTipoFormateado[$nombreTipo] = $total;
        }

        // Vehículos - Combinar count, tipo y estado en consultas optimizadas
        $vehiculosCount = Vehicle::count();
        $vehiculosPorTipo = Vehicle::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        $vehiculosPorEstado = Vehicle::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Calcular porcentajes de estados
        $vehiculosEstadosConPorcentaje = [];
        foreach ($vehiculosPorEstado as $estado => $total) {
            $porcentaje = $vehiculosCount > 0 ? round(($total / $vehiculosCount) * 100, 1) : 0;
            $vehiculosEstadosConPorcentaje[$estado] = [
                'total' => $total,
                'porcentaje' => $porcentaje,
            ];
        }

        // Propietarios
        $propietariosCount = Propietario::count();
        $propietariosPorTipo = Propietario::selectRaw('tipo_propietario, COUNT(*) as total')
            ->groupBy('tipo_propietario')
            ->pluck('total', 'tipo_propietario')
            ->toArray();

        // Usuarios - Ya optimizado en Fase 1
        $usuariosCount = User::count();
        $usuariosPorRolData = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['Mango', 'Admin', 'User'])
            ->select('roles.name', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('roles.name')
            ->pluck('total', 'name')
            ->toArray();

        $usuariosPorRol = [
            'Mango' => $usuariosPorRolData['Mango'] ?? 0,
            'Admin' => $usuariosPorRolData['Admin'] ?? 0,
            'User' => $usuariosPorRolData['User'] ?? 0,
        ];

        return [
            'conductores' => [
                'count' => $conductoresCount,
                'por_tipo' => $conductoresPorTipoFormateado,
            ],
            'vehiculos' => [
                'count' => $vehiculosCount,
                'por_tipo' => $vehiculosPorTipo,
                'por_estado' => $vehiculosEstadosConPorcentaje,
            ],
            'propietarios' => [
                'count' => $propietariosCount,
                'por_tipo' => $propietariosPorTipo,
            ],
            'usuarios' => [
                'count' => $usuariosCount,
                'por_rol' => $usuariosPorRol,
            ],
        ];
    }
}
