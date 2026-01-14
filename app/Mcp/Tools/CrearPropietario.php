<?php

namespace App\Mcp\Tools;

use App\Models\Propietario;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para crear un nuevo propietario
 */
class CrearPropietario extends Tool
{
    protected string $description = 'Crea un nuevo propietario en el sistema. Requiere permisos de creación de propietarios.';

    public function handle(Request $request): Response|ResponseFactory
    {
        // Verificar permisos
        $user = $request->user();
        if (! $user || ! $user->can('crear propietarios')) {
            return Response::error(
                'No tienes permisos para crear propietarios.',
                ['code' => 'PERMISSION_DENIED', 'required_permission' => 'crear propietarios']
            );
        }

        $validated = $request->validate([
            'tipo_identificacion' => ['required', 'in:Cédula de Ciudadanía,RUC/NIT,Pasaporte'],
            'numero_identificacion' => ['required', 'string', 'unique:propietarios,numero_identificacion', 'max:50', 'regex:/^[0-9]+$/'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_propietario' => ['required', 'in:Persona Natural,Persona Jurídica'],
            'direccion_contacto' => ['nullable', 'string', 'max:500'],
            'telefono_contacto' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'estado' => ['required', 'in:Activo,Inactivo'],
        ], [
            'tipo_identificacion.required' => 'El tipo de identificación es obligatorio.',
            'numero_identificacion.required' => 'El número de identificación es obligatorio.',
            'numero_identificacion.unique' => 'Ya existe un propietario con este número de identificación.',
            'numero_identificacion.regex' => 'El número de identificación solo puede contener números.',
            'telefono_contacto.regex' => 'El teléfono de contacto solo puede contener números.',
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'tipo_propietario.required' => 'El tipo de propietario es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
        ]);

        $propietario = Propietario::create($validated);

        return Response::structured([
            'success' => true,
            'message' => 'Propietario creado exitosamente',
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
            'tipo_identificacion' => $schema->string()->enum(['Cédula de Ciudadanía', 'RUC/NIT', 'Pasaporte'])->description('Tipo de documento de identificación'),
            'numero_identificacion' => $schema->string()->description('Número de identificación (debe ser único)'),
            'nombre_completo' => $schema->string()->description('Nombre completo del propietario'),
            'tipo_propietario' => $schema->string()->enum(['Persona Natural', 'Persona Jurídica'])->description('Tipo de propietario'),
            'direccion_contacto' => $schema->string()->nullable()->description('Dirección de contacto'),
            'telefono_contacto' => $schema->string()->nullable()->description('Teléfono de contacto'),
            'correo_electronico' => $schema->string()->format('email')->nullable()->description('Correo electrónico'),
            'estado' => $schema->string()->enum(['Activo', 'Inactivo'])->description('Estado del propietario'),
        ];
    }

    public function name(): string
    {
        return 'crear_propietario';
    }
}
