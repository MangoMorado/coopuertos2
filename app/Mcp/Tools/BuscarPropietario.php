<?php

namespace App\Mcp\Tools;

use App\Models\Propietario;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class BuscarPropietario extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Busca propietarios en el sistema por nombre completo o número de identificación. Retorna información completa del propietario.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $request->get('query');
        $limit = $request->get('limit', 10);

        $propietarios = Propietario::where('nombre_completo', 'like', "%{$query}%")
            ->orWhere('numero_identificacion', 'like', "%{$query}%")
            ->limit($limit)
            ->get();

        $resultados = $propietarios->map(function ($propietario) {
            return [
                'id' => $propietario->id,
                'nombre_completo' => $propietario->nombre_completo,
                'tipo_identificacion' => $propietario->tipo_identificacion,
                'numero_identificacion' => $propietario->numero_identificacion,
                'tipo_propietario' => $propietario->tipo_propietario,
                'telefono_contacto' => $propietario->telefono_contacto,
                'correo_electronico' => $propietario->correo_electronico,
                'direccion_contacto' => $propietario->direccion_contacto,
                'estado' => $propietario->estado,
            ];
        });

        return Response::structured([
            'total' => $propietarios->count(),
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
            'query' => $schema->string()->description('Término de búsqueda (nombre completo o número de identificación)'),
            'limit' => $schema->integer()->default(10)->description('Número máximo de resultados a retornar'),
        ];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'buscar_propietario';
    }
}
