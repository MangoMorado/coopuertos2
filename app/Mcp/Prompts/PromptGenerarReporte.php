<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * Prompt MCP para generar reportes de conductores y vehículos
 */
class PromptGenerarReporte extends Prompt
{
    protected string $name = 'generar-reporte';

    protected string $title = 'Generar Reporte de Conductores/Vehículos';

    protected string $description = <<<'MARKDOWN'
        Guía paso a paso para generar reportes de conductores o vehículos con filtros específicos.
        Proporciona instrucciones claras sobre cómo usar las herramientas de búsqueda y exportación
        para crear reportes personalizados según los criterios del usuario.
    MARKDOWN;

    public function handle(Request $request): array
    {
        $tipo = $request->string('tipo', 'conductores'); // conductores o vehiculos
        $filtros = $request->get('filtros', []);

        $systemMessage = <<<MARKDOWN
Eres un asistente experto en el sistema Coopuertos. Tu tarea es ayudar al usuario a generar un reporte de {$tipo} con los filtros especificados.

**Pasos para generar el reporte:**

1. **Buscar datos**: Usa las herramientas de búsqueda disponibles:
   - Para conductores: `buscar_conductor` con los criterios de búsqueda
   - Para vehículos: `buscar_vehiculo` con los criterios de búsqueda
   - Para propietarios: `buscar_propietario` si es necesario

2. **Aplicar filtros**: Si el usuario especificó filtros, úsalos en las búsquedas:
   - Filtros disponibles: estado, tipo, fecha, vehículo asignado, etc.
   - Puedes hacer múltiples búsquedas y combinar resultados

3. **Organizar datos**: Una vez obtenidos los datos, organízalos de forma clara:
   - Agrupa por categorías si es relevante
   - Presenta estadísticas resumidas
   - Incluye información relevante de cada registro

4. **Exportar (opcional)**: Si el usuario necesita exportar:
   - Los datos ya están disponibles en la respuesta estructurada
   - Puedes formatearlos en el formato que el usuario solicite (texto, lista, tabla)

**Herramientas disponibles:**
- `buscar_conductor`: Buscar conductores por cédula, nombre, apellido, número interno
- `buscar_vehiculo`: Buscar vehículos por placa, marca, modelo, tipo
- `buscar_propietario`: Buscar propietarios por nombre o identificación
- `obtener_estadisticas`: Obtener estadísticas generales del sistema

**Ejemplo de uso:**
Si el usuario quiere un reporte de "conductores activos con vehículo asignado":
1. Usa `buscar_conductor` con query vacío o amplio para obtener todos
2. Filtra en los resultados aquellos con estado "activo" y vehículo_activo no null
3. Presenta los resultados organizados con información relevante
MARKDOWN;

        $userMessage = "Genera un reporte de {$tipo}";

        if (! empty($filtros)) {
            $filtrosTexto = implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($filtros), $filtros));
            $userMessage .= " con los siguientes filtros: {$filtrosTexto}";
        }

        $userMessage .= '.';

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'tipo',
                description: 'Tipo de reporte a generar: "conductores", "vehiculos" o "propietarios"',
                required: false,
            ),
            new Argument(
                name: 'filtros',
                description: 'Array de filtros a aplicar (ej: {"estado": "activo", "tipo": "A"})',
                required: false,
            ),
        ];
    }
}
