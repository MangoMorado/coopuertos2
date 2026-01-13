<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

/**
 * Recurso MCP con documentación completa del servidor MCP
 */
class DocumentacionMcpServer extends Resource
{
    protected string $uri = 'coopuertos://mcp/documentacion';

    protected string $mimeType = 'text/markdown';

    public function name(): string
    {
        return 'Documentación del Servidor MCP';
    }

    public function title(): string
    {
        return 'Documentación del Servidor MCP Coopuertos';
    }

    public function description(): string
    {
        return 'Documentación completa del servidor MCP de Coopuertos, incluyendo todas las herramientas, prompts, recursos y guías de uso.';
    }

    public function handle(Request $request): Response
    {
        $documentacion = <<<'MARKDOWN'
# Documentación del Servidor MCP Coopuertos

## Introducción

El servidor MCP (Model Context Protocol) de Coopuertos proporciona acceso completo al sistema mediante herramientas, prompts y recursos que permiten a clientes MCP interactuar con la aplicación de forma programática.

## Endpoint del Servidor

- **URL**: `/mcp/coopuertos`
- **Método**: POST (JSON-RPC 2.0)
- **Autenticación**: Laravel Sanctum (Bearer Token)

## Autenticación

### Iniciar Sesión

Antes de usar cualquier herramienta (excepto `iniciar_sesion`), debes autenticarte:

```json
{
  "method": "tools/call",
  "params": {
    "name": "iniciar_sesion",
    "arguments": {
      "email": "usuario@ejemplo.com",
      "password": "contraseña"
    }
  }
}
```

**Respuesta**:
```json
{
  "content": [
    {
      "type": "text",
      "text": {
        "success": true,
        "token": "1|token_aqui...",
        "user": {
          "id": 1,
          "name": "Usuario",
          "email": "usuario@ejemplo.com"
        }
      }
    }
  ]
}
```

### Usar el Token

Guarda el token y úsalo en todas las requests posteriores:

```
Authorization: Bearer 1|token_aqui...
```

## Herramientas Disponibles

### Autenticación
- **iniciar_sesion**: Inicia sesión y obtiene token de acceso

### Búsqueda
- **buscar_conductor**: Buscar conductores por cédula, nombre, apellido, número interno
- **buscar_vehiculo**: Buscar vehículos por placa, marca, modelo, tipo
- **buscar_propietario**: Buscar propietarios por nombre o identificación

### Creación
- **crear_conductor**: Crear nuevo conductor con validación
- **crear_vehiculo**: Crear nuevo vehículo con validación
- **crear_propietario**: Crear nuevo propietario con validación

### Edición
- **editar_conductor**: Actualizar información de conductor
- **editar_vehiculo**: Actualizar información de vehículo
- **editar_propietario**: Actualizar información de propietario
- **asignar_vehiculo_conductor**: Asignar/desasignar vehículo a conductor

### Eliminación
- **eliminar_conductor**: Eliminar conductor (requiere permisos)
- **eliminar_vehiculo**: Eliminar vehículo (requiere permisos)
- **eliminar_propietario**: Eliminar propietario (requiere permisos)

### Gestión de Carnets
- **generar_carnet**: Generar carnet individual para un conductor
- **generar_carnets_masivos**: Iniciar generación masiva (retorna session_id)
- **obtener_estado_generacion**: Consultar progreso de generación masiva
- **exportar_qrs**: Exportar códigos QR en formato ZIP (base64)
- **obtener_plantilla_activa**: Consultar plantilla de carnet activa
- **personalizar_plantilla**: Actualizar configuración de plantilla
- **descargar_carnet**: Obtener URL o archivo de carnet generado

### Utilidades
- **obtener_estadisticas**: Obtener estadísticas generales del sistema
- **listar_rutas**: Listar todas las rutas disponibles en la aplicación

### Monitoreo y Salud
- **obtener_salud_sistema**: Estado completo del sistema (BD, colas, almacenamiento, versiones)
- **obtener_metricas_colas**: Métricas detalladas de jobs en cola
- **obtener_logs_importacion**: Consultar logs de importaciones masivas
- **obtener_logs_generacion_carnets**: Consultar logs de generación de carnets
- **obtener_logs_laravel**: Consultar logs de Laravel con filtros

## Prompts Disponibles

Los prompts son guías interactivas que ayudan a realizar tareas complejas:

- **generar-reporte**: Guía para generar reportes de conductores/vehículos con filtros
- **importar-conductores**: Guía paso a paso para importación masiva de conductores
- **configurar-permisos**: Asistencia para configurar permisos de usuarios
- **troubleshooting**: Ayuda para resolver problemas comunes del sistema
- **tutorial-interactivo-app**: Tutorial interactivo de uso de la aplicación

## Recursos Disponibles

Los recursos proporcionan acceso a documentación y información del proyecto:

- **coopuertos://documentacion**: Documentación completa del proyecto (README)
- **coopuertos://roadmap**: Roadmap del proyecto con todas las fases
- **coopuertos://mcp/documentacion**: Esta documentación del servidor MCP
- **coopuertos://mcp/guia-integracion**: Guía de integración para clientes MCP
- **coopuertos://mcp/ejemplos**: Ejemplos de uso de todas las herramientas

## Permisos

Todas las operaciones CRUD requieren permisos específicos según el módulo:

- `crear {modulo}`: Crear elementos del módulo
- `editar {modulo}`: Editar elementos del módulo
- `eliminar {modulo}`: Eliminar elementos del módulo
- `ver {modulo}`: Ver elementos del módulo

**Módulos disponibles**: conductores, vehiculos, propietarios, carnets, usuarios, dashboard, configuracion

**Ejemplo**: Para crear un conductor, se requiere el permiso `crear conductores`.

## Manejo de Errores

Todas las herramientas retornan errores estructurados:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje de error descriptivo",
    "hint": "Sugerencia para resolver el problema"
  }
}
```

**Códigos de error comunes**:
- `PERMISSION_DENIED`: No tienes permisos para realizar esta acción
- `VALIDATION_ERROR`: Error de validación en los datos proporcionados
- `NOT_FOUND`: Recurso no encontrado
- `UNAUTHORIZED`: No autenticado o token inválido
- `INVALID_CREDENTIALS`: Credenciales incorrectas

## Flujo Recomendado

1. **Autenticación**: Usar `iniciar_sesion` para obtener token
2. **Guardar token**: Almacenar el token de forma segura
3. **Verificar permisos**: Antes de operaciones CRUD, verificar permisos del usuario
4. **Usar herramientas**: Llamar a las herramientas necesarias con el token
5. **Manejar errores**: Revisar códigos de error y mensajes para debugging

## Ejemplos de Uso

### Ejemplo 1: Buscar y crear un conductor

```json
// 1. Buscar conductor
{
  "method": "tools/call",
  "params": {
    "name": "buscar_conductor",
    "arguments": {
      "query": "1234567890"
    }
  }
}

// 2. Si no existe, crear
{
  "method": "tools/call",
  "params": {
    "name": "crear_conductor",
    "arguments": {
      "cedula": "1234567890",
      "nombres": "Juan",
      "apellidos": "Pérez",
      "conductor_tipo": "A",
      "rh": "O+"
    }
  }
}
```

### Ejemplo 2: Generar carnet masivo

```json
// 1. Iniciar generación
{
  "method": "tools/call",
  "params": {
    "name": "generar_carnets_masivos",
    "arguments": {
      "conductor_ids": [1, 2, 3]
    }
  }
}

// 2. Consultar progreso (usar session_id retornado)
{
  "method": "tools/call",
  "params": {
    "name": "obtener_estado_generacion",
    "arguments": {
      "session_id": "uuid-aqui"
    }
  }
}

// 3. Descargar cuando esté completado
{
  "method": "tools/call",
  "params": {
    "name": "descargar_carnet",
    "arguments": {
      "session_id": "uuid-aqui"
    }
  }
}
```

## Versión

- **Versión del servidor**: 1.0.0
- **Laravel**: 12.47.0
- **Laravel MCP**: 0.5.2

## Soporte

Para más información, consulta:
- Recurso `coopuertos://mcp/guia-integracion`: Guía de integración detallada
- Recurso `coopuertos://mcp/ejemplos`: Ejemplos de uso de todas las herramientas
- Prompt `troubleshooting`: Ayuda para resolver problemas comunes
MARKDOWN;

        return Response::text($documentacion);
    }
}
