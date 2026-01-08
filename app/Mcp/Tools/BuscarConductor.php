<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class BuscarConductor extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Busca conductores en el sistema por cédula, nombre, apellido o número interno. Retorna información completa del conductor incluyendo vehículos asignados.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = $request->get('query');
        $limit = $request->get('limit', 10);

        $conductores = Conductor::where('cedula', 'like', "%{$query}%")
            ->orWhere('nombres', 'like', "%{$query}%")
            ->orWhere('apellidos', 'like', "%{$query}%")
            ->orWhere('numero_interno', 'like', "%{$query}%")
            ->with(['vehiculoActivo', 'asignacionActiva'])
            ->limit($limit)
            ->get();

        $resultados = $conductores->map(function ($conductor) {
            $vehiculoActivo = $conductor->vehiculoActivo;

            return [
                'id' => $conductor->id,
                'uuid' => $conductor->uuid,
                'nombres' => $conductor->nombres,
                'apellidos' => $conductor->apellidos,
                'cedula' => $conductor->cedula,
                'numero_interno' => $conductor->numero_interno,
                'conductor_tipo' => $conductor->conductor_tipo,
                'celular' => $conductor->celular,
                'correo' => $conductor->correo,
                'estado' => $conductor->estado,
                'vehiculo_activo' => $vehiculoActivo ? [
                    'id' => $vehiculoActivo->id,
                    'placa' => $vehiculoActivo->placa,
                    'marca' => $vehiculoActivo->marca,
                    'modelo' => $vehiculoActivo->modelo,
                ] : null,
            ];
        });

        return Response::structured([
            'total' => $conductores->count(),
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
            'query' => $schema->string()->description('Término de búsqueda (cédula, nombre, apellido o número interno)'),
            'limit' => $schema->integer()->default(10)->description('Número máximo de resultados a retornar'),
        ];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'buscar_conductor';
    }
}
