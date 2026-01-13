<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * Prompt MCP para asistir en la configuración de permisos
 */
class PromptConfigurarPermisos extends Prompt
{
    protected string $name = 'configurar-permisos';

    protected string $title = 'Asistencia para Configurar Permisos';

    protected string $description = <<<'MARKDOWN'
        Proporciona asistencia y guía para configurar permisos de usuarios en el sistema Coopuertos.
        Explica la estructura de roles, módulos y permisos, y cómo gestionarlos correctamente.
    MARKDOWN;

    public function handle(Request $request): array
    {
        $rol = $request->string('rol', null);

        $systemMessage = <<<'MARKDOWN'
Eres un asistente experto en el sistema Coopuertos. Tu tarea es ayudar al usuario a entender y configurar el sistema de permisos del sistema.

**Sistema de Roles y Permisos de Coopuertos:**

## Estructura de Roles

El sistema tiene 3 roles principales:

1. **Mango** (SuperAdmin)
   - Acceso completo a todos los módulos
   - Puede gestionar permisos de otros roles
   - Acceso a configuración y documentación
   - No puede ser modificado (siempre tiene todos los permisos)

2. **Admin**
   - Acceso a todos los módulos operativos
   - Puede crear, editar, eliminar y ver en todos los módulos
   - NO tiene acceso a configuración (solo Mango)
   - Permisos se configuran por módulo

3. **User**
   - Acceso básico de solo lectura
   - Puede ver información pero no modificar
   - Permisos se configuran por módulo

## Estructura de Permisos

Cada módulo tiene 4 permisos base:
- `ver {modulo}`: Ver/Listar elementos del módulo
- `crear {modulo}`: Crear nuevos elementos
- `editar {modulo}`: Editar elementos existentes
- `eliminar {modulo}`: Eliminar elementos

## Módulos Disponibles

1. **Dashboard** (`dashboard`)
   - Ver panel de control y estadísticas

2. **Conductores** (`conductores`)
   - Ver, crear, editar y eliminar conductores
   - Generar carnets
   - Importar conductores masivamente

3. **Vehículos** (`vehiculos`)
   - Ver, crear, editar y eliminar vehículos
   - Asociar con conductores

4. **Propietarios** (`propietarios`)
   - Ver, crear, editar y eliminar propietarios

5. **Carnets** (`carnets`)
   - Ver, crear, editar y eliminar carnets
   - Personalizar plantillas
   - Generar carnets masivos

6. **Usuarios** (`usuarios`)
   - Ver, crear, editar y eliminar usuarios
   - Asignar roles

7. **Configuración** (`configuracion`)
   - Gestionar permisos por módulo y rol
   - Ver estado de salud del sistema
   - Solo disponible para rol Mango

## Cómo Configurar Permisos

**Para configurar permisos (solo rol Mango):**

1. Accede a `/configuracion` en el navegador
2. Verás una tabla con roles (Mango, Admin, User) y módulos
3. Activa/desactiva módulos para cada rol marcando las casillas
4. Mango siempre tiene todos los permisos (no se puede modificar)
5. Admin y User reciben permisos completos (ver, crear, editar, eliminar) para los módulos activados

**Reglas importantes:**
- Mango siempre tiene todos los permisos (no se puede cambiar)
- Si un módulo está activo para un rol, recibe los 4 permisos base
- Si un módulo está inactivo, el rol no tiene acceso a ese módulo
- Los cambios se aplican inmediatamente

## Ejemplos de Configuración

**Ejemplo 1: Admin con acceso completo excepto configuración**
- Activar todos los módulos para Admin
- Configuración permanece desactivada (solo Mango)

**Ejemplo 2: User con acceso de solo lectura**
- Activar módulos que User puede ver
- User podrá ver pero no crear/editar/eliminar (aunque tenga los permisos, la UI puede restringir acciones)

**Nota**: La configuración de permisos se realiza desde la interfaz web en `/configuracion`, no directamente desde MCP.
MARKDOWN;

        if ($rol) {
            $systemMessage .= "\n\n**Información específica para rol {$rol}:**\n";
            match ($rol) {
                'Mango' => $systemMessage .= "- Tiene acceso completo a todo el sistema\n- Puede gestionar permisos de otros roles\n- Acceso a configuración y documentación",
                'Admin' => $systemMessage .= "- Acceso a módulos operativos\n- Puede gestionar conductores, vehículos, propietarios, carnets\n- NO tiene acceso a configuración",
                'User' => $systemMessage .= "- Acceso básico de solo lectura\n- Puede ver información pero no modificar",
                default => $systemMessage .= '- Rol no reconocido',
            };
        }

        $userMessage = $rol
            ? "Explícame cómo configurar permisos para el rol {$rol}."
            : 'Explícame cómo funciona el sistema de permisos y cómo configurarlo.';

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'rol',
                description: 'Rol específico sobre el cual obtener información (Mango, Admin, User)',
                required: false,
            ),
        ];
    }
}
