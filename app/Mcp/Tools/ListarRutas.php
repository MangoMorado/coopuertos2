<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListarRutas extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Lista todas las rutas disponibles en la aplicación Laravel con sus métodos HTTP y nombres.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $filtro = $request->get('filtro');
        $rutas = Route::getRoutes();

        $rutasListadas = [];

        foreach ($rutas as $ruta) {
            $uri = $ruta->uri();
            $nombre = $ruta->getName();
            $metodos = $ruta->methods();

            // Aplicar filtro si se proporciona
            if ($filtro &&
                ! str_contains(strtolower($uri), strtolower($filtro)) &&
                ! str_contains(strtolower($nombre ?? ''), strtolower($filtro))) {
                continue;
            }

            $rutasListadas[] = [
                'uri' => $uri,
                'metodos' => array_filter($metodos, fn ($m) => $m !== 'HEAD'),
                'nombre' => $nombre,
                'accion' => $ruta->getActionName(),
            ];
        }

        return Response::structured([
            'total' => count($rutasListadas),
            'rutas' => $rutasListadas,
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
            'filtro' => $schema->string()->nullable()->description('Filtrar rutas por nombre o URI (opcional)'),
        ];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'listar_rutas';
    }
}
