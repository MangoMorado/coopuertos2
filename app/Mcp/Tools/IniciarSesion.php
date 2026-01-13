<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

/**
 * Herramienta MCP para iniciar sesión en el sistema
 *
 * Permite a los usuarios autenticarse proporcionando email y password.
 * Retorna un token de acceso que debe ser usado en requests posteriores.
 * Esta herramienta NO requiere autenticación previa.
 */
class IniciarSesion extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Inicia sesión en el sistema Coopuertos proporcionando email y contraseña. Retorna un token de acceso que debe ser guardado y usado en el header Authorization: Bearer <token> para todas las consultas posteriores.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Debes proporcionar un email para iniciar sesión.',
            'email.email' => 'El email proporcionado no es válido.',
            'password.required' => 'Debes proporcionar una contraseña para iniciar sesión.',
        ]);

        // Intentar autenticar al usuario
        if (! Auth::attempt($validated)) {
            return Response::error(
                'Las credenciales proporcionadas no son correctas. Por favor, verifica tu email y contraseña.',
                [
                    'code' => 'INVALID_CREDENTIALS',
                    'hint' => 'Asegúrate de que el email y la contraseña sean correctos.',
                ]
            );
        }

        $user = Auth::user();

        // Crear token de acceso
        $token = $user->createToken('mcp-token', ['*'])->plainTextToken;

        return Response::structured([
            'success' => true,
            'message' => 'Sesión iniciada exitosamente',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'instructions' => [
                'Guarda este token de forma segura.',
                'Usa este token en el header Authorization de todas las requests MCP:',
                'Authorization: Bearer '.$token,
                'El token es válido hasta que se revoque o expire.',
            ],
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
            'email' => $schema->string()
                ->format('email')
                ->description('Email del usuario registrado en el sistema'),
            'password' => $schema->string()
                ->format('password')
                ->description('Contraseña del usuario'),
        ];
    }

    /**
     * Get the tool's name.
     */
    public function name(): string
    {
        return 'iniciar_sesion';
    }
}
