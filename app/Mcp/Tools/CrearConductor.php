<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para crear un nuevo conductor
 */
class CrearConductor extends Tool
{
    protected string $description = 'Crea un nuevo conductor en el sistema. Requiere permisos de creación de conductores.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear conductores')) {
            return Response::error(
                'No tienes permisos para crear conductores.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear conductores']
            );
        }

        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'cedula' => ['required', 'string', 'unique:conductors,cedula'],
            'conductor_tipo' => ['required', 'in:A,B'],
            'rh' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'numero_interno' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'otra_profesion' => ['nullable', 'string', 'max:255'],
            'nivel_estudios' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:activo,inactivo'],
            'foto_base64' => ['nullable', 'string'],
        ], [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.unique' => 'Ya existe un conductor con esta cédula.',
            'conductor_tipo.required' => 'El tipo de conductor es obligatorio.',
            'conductor_tipo.in' => 'El tipo de conductor debe ser A o B.',
            'rh.required' => 'El grupo sanguíneo es obligatorio.',
            'rh.in' => 'El grupo sanguíneo no es válido.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ]);

        // Si correo está vacío, poner "No tiene"
        if (empty($validated['correo'])) {
            $validated['correo'] = 'No tiene';
        }

        // Manejo de foto en base64
        if (isset($validated['foto_base64']) && ! empty($validated['foto_base64'])) {
            $validated['foto'] = $validated['foto_base64'];
            unset($validated['foto_base64']);
        }

        $conductor = Conductor::create($validated);

        return Response::structured([
            'success' => true,
            'message' => 'Conductor creado exitosamente',
            'conductor' => [
                'id' => $conductor->id,
                'uuid' => $conductor->uuid,
                'nombres' => $conductor->nombres,
                'apellidos' => $conductor->apellidos,
                'cedula' => $conductor->cedula,
                'numero_interno' => $conductor->numero_interno,
                'estado' => $conductor->estado,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nombres' => $schema->string()->description('Nombres del conductor'),
            'apellidos' => $schema->string()->description('Apellidos del conductor'),
            'cedula' => $schema->string()->description('Número de cédula (debe ser único)'),
            'conductor_tipo' => $schema->string()->enum(['A', 'B'])->description('Tipo de conductor: A o B'),
            'rh' => $schema->string()->enum(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->description('Grupo sanguíneo y factor RH'),
            'numero_interno' => $schema->string()->nullable()->description('Número interno asignado'),
            'celular' => $schema->string()->nullable()->description('Número de teléfono celular'),
            'correo' => $schema->string()->format('email')->nullable()->description('Dirección de correo electrónico'),
            'fecha_nacimiento' => $schema->string()->format('date')->nullable()->description('Fecha de nacimiento (formato: YYYY-MM-DD)'),
            'otra_profesion' => $schema->string()->nullable()->description('Otra profesión del conductor'),
            'nivel_estudios' => $schema->string()->nullable()->description('Nivel de estudios alcanzado'),
            'estado' => $schema->string()->enum(['activo', 'inactivo'])->description('Estado del conductor'),
            'foto_base64' => $schema->string()->nullable()->description('Foto del conductor en formato base64 (data URI)'),
        ];
    }

    public function name(): string
    {
        return 'crear_conductor';
    }
}
