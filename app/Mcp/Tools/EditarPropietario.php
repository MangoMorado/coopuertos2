<?php

namespace App\Mcp\Tools;

use App\Models\Propietario;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para editar un propietario existente
 */
class EditarPropietario extends Tool
{
    protected string $description = 'Actualiza la información de un propietario existente. Requiere permisos de edición de propietarios.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('editar propietarios')) {
            return Response::error(
                'No tienes permisos para editar propietarios.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'editar propietarios']
            );
        }

        $propietarioId = $request->get('propietario_id');
        $propietario = Propietario::find($propietarioId);

        if (! $propietario) {
            return Response::error(
                'Propietario no encontrado.',
                ['code' => 'NOT_FOUND', 'propietario_id' => $propietarioId]
            );
        }

        $validated = $request->validate([
            'tipo_identificacion' => ['sometimes', 'required', 'in:Cédula de Ciudadanía,RUC/NIT,Pasaporte'],
            'numero_identificacion' => ['sometimes', 'required', 'string', 'unique:propietarios,numero_identificacion,'.$propietario->id.',id', 'max:50'],
            'nombre_completo' => ['sometimes', 'required', 'string', 'max:255'],
            'tipo_propietario' => ['sometimes', 'required', 'in:Persona Natural,Persona Jurídica'],
            'direccion_contacto' => ['nullable', 'string', 'max:500'],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'estado' => ['sometimes', 'required', 'in:Activo,Inactivo'],
        ]);

        $propietario->update($validated);
        $propietario->refresh();

        return Response::structured([
            'success' => true,
            'message' => 'Propietario actualizado exitosamente',
            'propietario' => [
                'id' => $propietario->id,
                'nombre_completo' => $propietario->nombre_completo,
                'tipo_identificacion' => $propietario->tipo_identificacion,
                'numero_identificacion' => $propietario->numero_identificacion,
                'tipo_propietario' => $propietario->tipo_propietario,
                'estado' => $propietario->estado,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'propietario_id' => $schema->integer()->description('ID del propietario a editar'),
            'tipo_identificacion' => $schema->string()->enum(['Cédula de Ciudadanía', 'RUC/NIT', 'Pasaporte'])->nullable()->description('Tipo de documento de identificación'),
            'numero_identificacion' => $schema->string()->nullable()->description('Número de identificación'),
            'nombre_completo' => $schema->string()->nullable()->description('Nombre completo del propietario'),
            'tipo_propietario' => $schema->string()->enum(['Persona Natural', 'Persona Jurídica'])->nullable()->description('Tipo de propietario'),
            'direccion_contacto' => $schema->string()->nullable()->description('Dirección de contacto'),
            'telefono_contacto' => $schema->string()->nullable()->description('Teléfono de contacto'),
            'correo_electronico' => $schema->string()->format('email')->nullable()->description('Correo electrónico'),
            'estado' => $schema->string()->enum(['Activo', 'Inactivo'])->nullable()->description('Estado del propietario'),
        ];
    }

    public function name(): string
    {
        return 'editar_propietario';
    }
}
