<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ObtenerEstadisticas extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Obtiene estadísticas generales del sistema: número de conductores, vehículos, propietarios, usuarios y otras métricas útiles.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $estadisticas = [
            'conductores' => [
                'total' => Conductor::count(),
                'activos' => Conductor::where('estado', 'activo')->count(),
                'inactivos' => Conductor::where('estado', 'inactivo')->count(),
            ],
            'vehiculos' => [
                'total' => Vehicle::count(),
                'activos' => Vehicle::where('estado', 'activo')->count(),
                'inactivos' => Vehicle::where('estado', 'inactivo')->count(),
            ],
            'propietarios' => [
                'total' => Propietario::count(),
            ],
            'usuarios' => [
                'total' => User::count(),
            ],
            'asignaciones' => [
                'activas' => DB::table('conductor_vehicle')
                    ->where('estado', 'activo')
                    ->count(),
                'total' => DB::table('conductor_vehicle')->count(),
            ],
        ];

        return Response::structured($estadisticas);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'obtener_estadisticas';
    }
}
