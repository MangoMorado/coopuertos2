<?php

namespace App\Mcp\Tools;

use App\Models\Conductor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para editar un conductor existente
 */
class EditarConductor extends Tool
{
    protected string $description = 'Actualiza la información de un conductor existente. Requiere permisos de edición de conductores.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('editar conductores')) {
            return Response::error(
                'No tienes permisos para editar conductores.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'editar conductores']
            );
        }

        $conductorId = $request->get('conductor_id');
        $conductor = Conductor::find($conductorId);

        if (! $conductor) {
            return Response::error(
                'Conductor no encontrado.',
                ['code' => 'NOT_FOUND', 'conductor_id' => $conductorId]
            );
        }

        $validated = $request->validate([
            'nombres' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'required', 'string', 'unique:conductors,cedula,'.$conductor->id],
            'conductor_tipo' => ['sometimes', 'required', 'in:A,B'],
            'rh' => ['sometimes', 'required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'numero_interno' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'otra_profesion' => ['nullable', 'string', 'max:255'],
            'nivel_estudios' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'required', 'in:activo,inactivo'],
            'foto_base64' => ['nullable', 'string'],
        ]);

        // Si correo está vacío, poner "No tiene"
        if (isset($validated['correo']) && empty($validated['correo'])) {
            $validated['correo'] = 'No tiene';
        }

        // Manejo de foto en base64
        if (isset($validated['foto_base64']) && ! empty($validated['foto_base64'])) {
            $validated['foto'] = $validated['foto_base64'];
            unset($validated['foto_base64']);
        }

        $conductor->update($validated);
        $conductor->refresh();

        return Response::structured([
            'success' => true,
            'message' => 'Conductor actualizado exitosamente',
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
            'conductor_id' => $schema->integer()->description('ID del conductor a editar'),
            'nombres' => $schema->string()->nullable()->description('Nombres del conductor'),
            'apellidos' => $schema->string()->nullable()->description('Apellidos del conductor'),
            'cedula' => $schema->string()->nullable()->description('Número de cédula'),
            'conductor_tipo' => $schema->string()->enum(['A', 'B'])->nullable()->description('Tipo de conductor: A o B'),
            'rh' => $schema->string()->enum(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable()->description('Grupo sanguíneo y factor RH'),
            'numero_interno' => $schema->string()->nullable()->description('Número interno asignado'),
            'celular' => $schema->string()->nullable()->description('Número de teléfono celular'),
            'correo' => $schema->string()->format('email')->nullable()->description('Dirección de correo electrónico'),
            'fecha_nacimiento' => $schema->string()->format('date')->nullable()->description('Fecha de nacimiento (formato: YYYY-MM-DD)'),
            'otra_profesion' => $schema->string()->nullable()->description('Otra profesión del conductor'),
            'nivel_estudios' => $schema->string()->nullable()->description('Nivel de estudios alcanzado'),
            'estado' => $schema->string()->enum(['activo', 'inactivo'])->nullable()->description('Estado del conductor'),
            'foto_base64' => $schema->string()->nullable()->description('Foto del conductor en formato base64 (data URI)'),
        ];
    }

    public function name(): string
    {
        return 'editar_conductor';
    }
}
