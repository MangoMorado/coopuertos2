<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * Prompt MCP para ayudar a resolver problemas comunes del sistema
 */
class PromptTroubleshooting extends Prompt
{
    protected string $name = 'troubleshooting';

    protected string $title = 'Solución de Problemas del Sistema';

    protected string $description = <<<'MARKDOWN'
        Proporciona ayuda para resolver problemas comunes del sistema Coopuertos.
        Incluye soluciones para errores frecuentes, problemas de permisos, generación de carnets,
        importaciones y otros problemas técnicos.
    MARKDOWN;

    public function handle(Request $request): array
    {
        $problema = $request->string('problema', null);

        $systemMessage = <<<'MARKDOWN'
Eres un asistente experto en solución de problemas del sistema Coopuertos. Tu tarea es ayudar al usuario a diagnosticar y resolver problemas comunes.

**Problemas Comunes y Soluciones:**

## 1. Problemas de Autenticación
**Síntoma**: Error 401, "No autenticado", "Token inválido"
**Soluciones**:
- Verifica que estés usando el token correcto en el header `Authorization: Bearer <token>`
- Si el token expiró, usa la herramienta `iniciar_sesion` para obtener uno nuevo
- Verifica que el token no haya sido revocado
- Asegúrate de que el servidor MCP esté configurado correctamente

## 2. Problemas de Permisos
**Síntoma**: Error "No tienes permisos", "PERMISSION_DENIED"
**Soluciones**:
- Verifica que el usuario tenga el rol correcto (Mango, Admin, User)
- Revisa que los permisos estén configurados en `/configuracion`
- Asegúrate de que el módulo esté activo para tu rol
- Si eres Mango, deberías tener todos los permisos automáticamente

## 3. Problemas con Generación de Carnets
**Síntoma**: "No hay plantilla activa", "Error al generar carnet"
**Soluciones**:
- Verifica que exista una plantilla activa usando `obtener_plantilla_activa`
- Si no hay plantilla, crea una usando `personalizar_plantilla`
- Verifica que Imagick esté instalado y configurado correctamente
- Revisa los logs del sistema para errores específicos
- Para generación masiva, consulta el progreso con `obtener_estado_generacion`

## 4. Problemas con Importación de Conductores
**Síntoma**: "Error de validación", "Archivo no válido", "Importación fallida"
**Soluciones**:
- Verifica el formato del archivo (Excel .xlsx/.xls o CSV .csv)
- Asegúrate de que todas las columnas requeridas estén presentes
- Verifica que las cédulas sean únicas (no duplicadas)
- Revisa que los tipos de datos sean correctos (fechas, enums, etc.)
- Si hay errores de fotos, verifica que las URLs de Google Drive sean públicas
- Consulta el log de importación para ver errores detallados

## 5. Problemas con Base de Datos
**Síntoma**: "Error de conexión", "Query failed", "Database error"
**Soluciones**:
- Verifica la conexión a la base de datos en `.env`
- Revisa que el servidor de base de datos esté corriendo
- Verifica que las credenciales sean correctas
- Usa `obtener_salud_sistema` para verificar el estado de la BD
- Revisa los logs de Laravel para errores específicos

## 6. Problemas con Colas/Jobs
**Síntoma**: "Jobs no se procesan", "Generación no avanza"
**Soluciones**:
- Verifica que el worker de colas esté corriendo: `php artisan queue:work`
- En producción, verifica que Supervisor esté configurado y corriendo
- Revisa la tabla `failed_jobs` para trabajos fallidos
- Verifica la configuración de colas en `.env`
- Usa `obtener_salud_sistema` para ver estado de colas

## 7. Problemas con Almacenamiento
**Síntoma**: "No se puede guardar archivo", "Espacio insuficiente"
**Soluciones**:
- Verifica permisos de escritura en `storage/` y `public/storage/`
- Revisa el espacio disponible en disco
- Asegúrate de que los directorios necesarios existan
- Usa `obtener_salud_sistema` para ver estado de almacenamiento

## 8. Problemas con Fotos/Imágenes
**Síntoma**: "Foto no se muestra", "Error procesando imagen"
**Soluciones**:
- Verifica que Imagick esté instalado: `php -m | grep imagick`
- Las fotos se guardan como base64 en la base de datos
- Verifica que el formato de base64 sea correcto (data URI)
- Revisa que la extensión Imagick esté habilitada en PHP

## 9. Problemas con API/MCP
**Síntoma**: "Endpoint no encontrado", "Error en request MCP"
**Soluciones**:
- Verifica que las rutas estén registradas correctamente
- Revisa que el middleware de autenticación esté configurado
- Verifica que el servidor esté corriendo
- Revisa los logs de Laravel para errores específicos
- Usa `listar_rutas` para ver rutas disponibles

## 10. Problemas Generales
**Herramientas útiles para diagnóstico:**
- `obtener_salud_sistema`: Verifica estado general del sistema
- `obtener_estadisticas`: Obtiene estadísticas del sistema
- `listar_rutas`: Lista todas las rutas disponibles
- Revisa logs en `storage/logs/laravel.log`

**Cómo reportar problemas:**
1. Describe el problema específico
2. Incluye mensajes de error exactos
3. Menciona qué acción estabas realizando
4. Proporciona contexto relevante (usuario, permisos, datos)
MARKDOWN;

        if ($problema) {
            $systemMessage .= "\n\n**Diagnóstico para: {$problema}**\n";
            $systemMessage .= 'Basándote en el problema descrito, proporciona soluciones específicas y pasos detallados para resolverlo.';
        }

        $userMessage = $problema
            ? "Tengo un problema: {$problema}. Ayúdame a resolverlo."
            : 'Necesito ayuda para resolver problemas comunes del sistema.';

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'problema',
                description: 'Descripción del problema específico que el usuario está experimentando',
                required: false,
            ),
        ];
    }
}
