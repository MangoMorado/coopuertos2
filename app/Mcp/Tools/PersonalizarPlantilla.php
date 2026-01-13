<?php

namespace App\Mcp\Tools;

use App\Models\CarnetTemplate;
use App\Services\CarnetTemplateService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para personalizar la plantilla de carnet
 */
class PersonalizarPlantilla extends Tool
{
    protected string $description = 'Actualiza la configuración de la plantilla de carnet activa. Permite modificar el nombre, la imagen y la configuración de variables. Requiere permisos de edición de carnets.';

    public function __construct(
        protected CarnetTemplateService $templateService
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('editar carnets')) {
            return Response::error(
                'No tienes permisos para personalizar plantillas de carnets.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'editar carnets']
            );
        }

        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:255'],
            'imagen_plantilla_base64' => ['nullable', 'string'],
            'variables_config' => ['required', 'json'],
        ], [
            'variables_config.required' => 'La configuración de variables es obligatoria.',
            'variables_config.json' => 'La configuración de variables debe ser un JSON válido.',
        ]);

        // Desactivar todas las plantillas anteriores
        CarnetTemplate::where('activo', true)->update(['activo' => false]);

        // Manejo de la imagen si se proporciona en base64
        $imagenPath = null;
        if (isset($validated['imagen_plantilla_base64']) && ! empty($validated['imagen_plantilla_base64'])) {
            // Decodificar base64 y guardar
            $imagenPath = $this->storeBase64Image($validated['imagen_plantilla_base64']);
            unset($validated['imagen_plantilla_base64']);
        } else {
            // Si no se sube nueva imagen, mantener la anterior si existe
            $templateAnterior = CarnetTemplate::latest()->first();
            if ($templateAnterior && $templateAnterior->imagen_plantilla) {
                $imagenPath = $templateAnterior->imagen_plantilla;
            }
        }

        // Decodificar variables_config
        $variablesConfig = json_decode($validated['variables_config'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return Response::error(
                'El JSON de configuración de variables no es válido: '.json_last_error_msg(),
                ['code' => 'INVALID_JSON']
            );
        }

        // Crear nueva plantilla activa
        $template = CarnetTemplate::create([
            'nombre' => $validated['nombre'] ?? 'Plantilla Principal',
            'imagen_plantilla' => $imagenPath,
            'variables_config' => $variablesConfig,
            'activo' => true,
        ]);

        return Response::structured([
            'success' => true,
            'message' => 'Plantilla de carnet guardada correctamente',
            'plantilla' => [
                'id' => $template->id,
                'nombre' => $template->nombre,
                'activo' => $template->activo,
                'variables_config' => $template->variables_config,
            ],
        ]);
    }

    private function storeBase64Image(string $base64Data): string
    {
        // Detectar si es un data URI
        if (str_starts_with($base64Data, 'data:')) {
            $parts = explode(',', $base64Data, 2);
            $base64Data = $parts[1] ?? $base64Data;
        }

        // Decodificar base64
        $imageData = base64_decode($base64Data, true);
        if ($imageData === false) {
            throw new \Exception('No se pudo decodificar la imagen base64');
        }

        // Detectar tipo de imagen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'png',
        };

        // Guardar imagen
        $fileName = 'plantilla_'.time().'.'.$extension;
        $storagePath = storage_path('app/public/carnet_templates');
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        $filePath = $storagePath.'/'.$fileName;
        File::put($filePath, $imageData);

        return 'carnet_templates/'.$fileName;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()->nullable()->description('Nombre de la plantilla'),
            'imagen_plantilla_base64' => $schema->string()->nullable()->description('Imagen de la plantilla en formato base64 (data URI o base64 puro)'),
            'variables_config' => $schema->string()->description('Configuración de variables en formato JSON. Debe incluir todas las variables disponibles con sus posiciones, tamaños, fuentes y colores.'),
        ];
    }

    public function name(): string
    {
        return 'personalizar_plantilla';
    }
}
