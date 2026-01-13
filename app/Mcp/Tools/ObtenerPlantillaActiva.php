<?php

namespace App\Mcp\Tools;

use App\Models\CarnetTemplate;
use App\Services\CarnetTemplateService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para consultar la plantilla de carnet activa
 */
class ObtenerPlantillaActiva extends Tool
{
    protected string $description = 'Obtiene la información de la plantilla de carnet activa, incluyendo su configuración de variables y metadatos.';

    public function __construct(
        protected CarnetTemplateService $templateService
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template) {
            return Response::error(
                'No hay plantilla activa configurada.',
                ['code' => 'NO_TEMPLATE', 'hint' => 'Usa la herramienta personalizar_plantilla para crear una plantilla activa.']
            );
        }

        $variables = $this->templateService->getAvailableVariables();
        $variablesConfig = $this->templateService->prepareVariablesConfig($template->variables_config);

        return Response::structured([
            'success' => true,
            'plantilla' => [
                'id' => $template->id,
                'nombre' => $template->nombre,
                'activo' => $template->activo,
                'imagen_plantilla' => $template->imagen_plantilla ? asset('storage/'.$template->imagen_plantilla) : null,
                'variables_disponibles' => $variables,
                'variables_config' => $variablesConfig,
                'fecha_creacion' => $template->created_at?->toDateTimeString(),
                'fecha_actualizacion' => $template->updated_at?->toDateTimeString(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function name(): string
    {
        return 'obtener_plantilla_activa';
    }
}
