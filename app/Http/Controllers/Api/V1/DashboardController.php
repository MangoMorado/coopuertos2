<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conductor;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

#[OA\Tag(name: 'Dashboard', description: 'Estadísticas y métricas del sistema')]
class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard/stats',
        summary: 'Obtener estadísticas',
        description: 'Retorna estadísticas generales del sistema: conductores, vehículos, propietarios y usuarios',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas obtenidas exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Estadísticas obtenidas exitosamente'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function stats(): JsonResponse
    {
        // Conductores
        $conductoresCount = Conductor::count();
        $conductoresPorTipo = Conductor::selectRaw('conductor_tipo, COUNT(*) as total')
            ->groupBy('conductor_tipo')
            ->pluck('total', 'conductor_tipo')
            ->toArray();

        $conductoresPorTipoFormateado = [];
        foreach ($conductoresPorTipo as $tipo => $total) {
            $nombreTipo = $tipo === 'A' ? 'Camionetas' : 'Busetas';
            $conductoresPorTipoFormateado[$nombreTipo] = $total;
        }

        // Próximos cumpleaños (7 días o menos)
        $proximosCumpleanos = Conductor::whereNotNull('fecha_nacimiento')
            ->select('id', 'nombres', 'apellidos', 'cedula', 'fecha_nacimiento', 'conductor_tipo')
            ->get()
            ->map(function ($conductor) {
                $fechaNacimiento = $conductor->fecha_nacimiento;
                $hoy = now()->startOfDay();
                $anoActual = $hoy->year;
                $anoSiguiente = $anoActual + 1;

                $cumpleanosEsteAno = $fechaNacimiento->copy()->year($anoActual)->startOfDay();
                $cumpleanosSiguienteAno = $fechaNacimiento->copy()->year($anoSiguiente)->startOfDay();

                if ($cumpleanosEsteAno->isBefore($hoy)) {
                    $proximoCumpleanos = $cumpleanosSiguienteAno;
                } else {
                    $proximoCumpleanos = $cumpleanosEsteAno;
                }

                $diasRestantes = max(0, $hoy->diffInDays($proximoCumpleanos, false));

                return [
                    'id' => $conductor->id,
                    'nombres' => $conductor->nombres,
                    'apellidos' => $conductor->apellidos,
                    'cedula' => $conductor->cedula,
                    'fecha_nacimiento' => $fechaNacimiento->toDateString(),
                    'tipo' => $conductor->conductor_tipo === 'A' ? 'Camionetas' : 'Busetas',
                    'proximo_cumpleanos' => $proximoCumpleanos->toDateString(),
                    'dias_restantes' => $diasRestantes,
                    'edad' => $proximoCumpleanos->year - $fechaNacimiento->year,
                ];
            })
            ->filter(function ($conductor) {
                return $conductor['dias_restantes'] <= 7;
            })
            ->sortBy('dias_restantes')
            ->values();

        // Vehículos
        $vehiculosCount = Vehicle::count();
        $vehiculosPorTipo = Vehicle::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        $vehiculosPorEstado = Vehicle::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

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

        // Usuarios
        $usuariosCount = User::count();
        $roles = Role::whereIn('name', ['Mango', 'Admin', 'User'])->get();
        $usuariosPorRol = [];
        foreach ($roles as $rol) {
            $usuariosPorRol[$rol->name] = User::role($rol->name)->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'conductores' => [
                    'total' => $conductoresCount,
                    'por_tipo' => $conductoresPorTipoFormateado,
                    'proximos_cumpleanos' => $proximosCumpleanos,
                ],
                'vehiculos' => [
                    'total' => $vehiculosCount,
                    'por_tipo' => $vehiculosPorTipo,
                    'por_estado' => $vehiculosEstadosConPorcentaje,
                ],
                'propietarios' => [
                    'total' => $propietariosCount,
                    'por_tipo' => $propietariosPorTipo,
                ],
                'usuarios' => [
                    'total' => $usuariosCount,
                    'por_rol' => $usuariosPorRol,
                ],
            ],
            'message' => 'Estadísticas obtenidas exitosamente',
        ]);
    }
}
