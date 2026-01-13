<?php

namespace App\Mcp\Tools;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Services\CarnetGeneratorService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para generar un carnet individual para un conductor
 */
class GenerarCarnet extends Tool
{
    protected string $description = 'Genera un carnet individual en formato PDF para un conductor específico. Requiere permisos de creación de carnets.';

    public function __construct(
        protected CarnetGeneratorService $carnetGenerator
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear carnets')) {
            return Response::error(
                'No tienes permisos para generar carnets.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear carnets']
            );
        }

        $conductorId = $request->get('conductor_id');
        $conductor = Conductor::with(['asignacionActiva.vehicle'])->find($conductorId);

        if (! $conductor) {
            return Response::error(
                'Conductor no encontrado.',
                ['code' => 'NOT_FOUND', 'conductor_id' => $conductorId]
            );
        }

        // Verificar que hay una plantilla activa
        $template = CarnetTemplate::where('activo', true)->first();

        if (! $template) {
            return Response::error(
                'No hay plantilla activa para generar el carnet. Por favor, configure una plantilla primero.',
                ['code' => 'NO_TEMPLATE']
            );
        }

        try {
            // Crear directorio temporal
            $tempDir = storage_path('app/temp/carnet_individual_'.$conductor->id.'_'.time());
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Generar carnet PDF
            $pdfPath = $this->carnetGenerator->generarCarnetPDF($conductor, $template, $tempDir);

            if (! File::exists($pdfPath)) {
                throw new \Exception('No se pudo generar el archivo PDF');
            }

            // Guardar en storage permanente
            $carnetsDir = storage_path('app/carnets');
            if (! File::exists($carnetsDir)) {
                File::makeDirectory($carnetsDir, 0755, true);
            }

            $nombreArchivo = 'carnet_'.$conductor->cedula.'_'.time().'.pdf';
            $rutaPermanente = $carnetsDir.'/'.$nombreArchivo;

            // Mover el archivo a la ubicación permanente
            File::move($pdfPath, $rutaPermanente);

            // Actualizar conductor con ruta del carnet
            $rutaRelativa = 'carnets/'.$nombreArchivo;
            $conductor->update(['ruta_carnet' => $rutaRelativa]);

            // Generar URL pública
            $urlPublica = asset('storage/'.$rutaRelativa);

            return Response::structured([
                'success' => true,
                'message' => 'Carnet generado exitosamente',
                'conductor' => [
                    'id' => $conductor->id,
                    'nombres' => $conductor->nombres,
                    'apellidos' => $conductor->apellidos,
                    'cedula' => $conductor->cedula,
                ],
                'carnet' => [
                    'ruta' => $rutaRelativa,
                    'url' => $urlPublica,
                    'nombre_archivo' => $nombreArchivo,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error generando carnet individual: '.$e->getMessage(), [
                'conductor_id' => $conductor->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error(
                'Error al generar el carnet: '.$e->getMessage(),
                ['code' => 'GENERATION_ERROR', 'conductor_id' => $conductorId]
            );
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'conductor_id' => $schema->integer()->description('ID del conductor para el cual generar el carnet'),
        ];
    }

    public function name(): string
    {
        return 'generar_carnet';
    }
}
