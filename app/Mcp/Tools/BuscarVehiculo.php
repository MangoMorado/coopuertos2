<?php

namespace App\Mcp\Tools;

use App\Models\Vehicle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class BuscarVehiculo extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Busca vehículos en el sistema por placa, marca, modelo o propietario. Retorna información completa del vehículo incluyendo conductores asignados.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = $request->get('query');
        $limit = $request->get('limit', 10);

        $vehiculos = Vehicle::where('placa', 'like', "%{$query}%")
            ->orWhere('marca', 'like', "%{$query}%")
            ->orWhere('modelo', 'like', "%{$query}%")
            ->orWhere('propietario_nombre', 'like', "%{$query}%")
            ->with(['conductoresActivos'])
            ->limit($limit)
            ->get();

        $resultados = $vehiculos->map(function ($vehiculo) {
            $conductoresActivos = $vehiculo->conductoresActivos;

            return [
                'id' => $vehiculo->id,
                'placa' => $vehiculo->placa,
                'tipo' => $vehiculo->tipo,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'anio_fabricacion' => $vehiculo->anio_fabricacion,
                'propietario_nombre' => $vehiculo->propietario_nombre,
                'estado' => $vehiculo->estado,
                'conductores_activos' => $conductoresActivos->map(function ($conductor) {
                    return [
                        'id' => $conductor->id,
                        'nombres' => $conductor->nombres,
                        'apellidos' => $conductor->apellidos,
                        'cedula' => $conductor->cedula,
                        'numero_interno' => $conductor->numero_interno,
                    ];
                }),
            ];
        });

        return Response::structured([
            'total' => $vehiculos->count(),
            'resultados' => $resultados,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Término de búsqueda (placa, marca, modelo o propietario)'),
            'limit' => $schema->integer()->default(10)->description('Número máximo de resultados a retornar'),
        ];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'buscar_vehiculo';
    }
}
