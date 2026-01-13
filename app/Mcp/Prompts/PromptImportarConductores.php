<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

/**
 * Prompt MCP para guiar la importación masiva de conductores
 */
class PromptImportarConductores extends Prompt
{
    protected string $name = 'importar-conductores';

    protected string $title = 'Guía de Importación Masiva de Conductores';

    protected string $description = <<<'MARKDOWN'
        Proporciona una guía paso a paso detallada para importar conductores desde archivos Excel/CSV.
        Incluye información sobre formato de archivo, columnas requeridas, validaciones y seguimiento del progreso.
    MARKDOWN;

    public function handle(Request $request): array
    {
        $systemMessage = <<<'MARKDOWN'
Eres un asistente experto en el sistema Coopuertos. Tu tarea es guiar al usuario paso a paso en el proceso de importación masiva de conductores desde archivos Excel o CSV.

**Proceso de Importación Masiva de Conductores:**

## Paso 1: Preparar el Archivo
El archivo debe estar en formato Excel (.xlsx, .xls) o CSV (.csv) con las siguientes columnas:

**Columnas requeridas:**
- `cedula` (obligatorio, único)
- `nombres` (obligatorio)
- `apellidos` (obligatorio)
- `conductor_tipo` (obligatorio: A o B)
- `rh` (obligatorio: A+, A-, B+, B-, AB+, AB-, O+, O-)

**Columnas opcionales:**
- `numero_interno`
- `celular`
- `correo`
- `fecha_nacimiento` (formato: YYYY-MM-DD)
- `otra_profesion`
- `nivel_estudios`
- `foto_url` (URL de Google Drive para descargar foto)
- `estado` (activo o inactivo, por defecto: activo)

## Paso 2: Validación del Archivo
El sistema validará automáticamente:
- Formato del archivo (Excel o CSV)
- Columnas requeridas presentes
- Tipos de datos correctos
- Valores únicos (cédulas no duplicadas)
- URLs de fotos válidas (si se proporcionan)

## Paso 3: Iniciar Importación
**Nota importante**: La importación masiva se realiza mediante la interfaz web, no directamente desde MCP.
Para importar conductores, el usuario debe:
1. Acceder a la ruta `/conductores/importar` en el navegador
2. Subir el archivo Excel/CSV
3. El sistema procesará la importación en segundo plano

## Paso 4: Seguimiento del Progreso
Una vez iniciada la importación:
- Se crea un registro de seguimiento con un `session_id`
- El progreso se actualiza en tiempo real
- Puedes consultar el progreso usando la ruta `/conductores/import/progreso/{session_id}`
- El sistema muestra: total, procesados, exitosos, errores, tiempo transcurrido y estimado

## Paso 5: Resultados
Al finalizar, el sistema proporciona:
- Resumen de importación (total, exitosos, errores, duplicados)
- Lista de errores detallados (si los hay)
- Logs completos del proceso

**Características especiales:**
- Descarga automática de fotos desde Google Drive si se proporciona `foto_url`
- Procesamiento en segundo plano (no bloquea la interfaz)
- Manejo de duplicados (detecta cédulas ya existentes)
- Validación robusta de datos antes de importar

**Recomendaciones:**
- Verifica el formato del archivo antes de importar
- Asegúrate de que las cédulas sean únicas
- Si hay muchas fotos, el proceso puede tardar más tiempo
- Los archivos grandes se procesan en lotes para optimizar memoria

**Solución de problemas comunes:**
- Si el archivo no se valida: revisa las columnas requeridas y formatos
- Si hay errores de fotos: verifica que las URLs de Google Drive sean públicas
- Si la importación es lenta: es normal para archivos grandes, el sistema procesa en segundo plano
MARKDOWN;

        $userMessage = 'Necesito importar conductores desde un archivo Excel/CSV. Guíame paso a paso en el proceso.';

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }

    public function arguments(): array
    {
        return [];
    }
}
