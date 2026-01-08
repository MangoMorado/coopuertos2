<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\DocumentacionProyecto;
use App\Mcp\Resources\RoadmapProyecto;
use App\Mcp\Tools\BuscarConductor;
use App\Mcp\Tools\BuscarPropietario;
use App\Mcp\Tools\BuscarVehiculo;
use App\Mcp\Tools\ListarRutas;
use App\Mcp\Tools\ObtenerEstadisticas;
use Laravel\Mcp\Server;

class CoopuertosServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Coopuertos MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Este servidor MCP proporciona acceso a la información del sistema Coopuertos.
        Puedes buscar conductores, vehículos, propietarios, obtener estadísticas y listar rutas disponibles.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        BuscarConductor::class,
        BuscarVehiculo::class,
        BuscarPropietario::class,
        ObtenerEstadisticas::class,
        ListarRutas::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        DocumentacionProyecto::class,
        RoadmapProyecto::class,
    ];
}
