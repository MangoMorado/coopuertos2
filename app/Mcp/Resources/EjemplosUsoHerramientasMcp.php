<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

/**
 * Recurso MCP con ejemplos de uso de todas las herramientas
 */
class EjemplosUsoHerramientasMcp extends Resource
{
    protected string $uri = 'coopuertos://mcp/ejemplos';

    protected string $mimeType = 'text/markdown';

    public function name(): string
    {
        return 'Ejemplos de Uso de Herramientas MCP';
    }

    public function title(): string
    {
        return 'Ejemplos de Uso de Herramientas MCP';
    }

    public function description(): string
    {
        return 'Ejemplos prácticos de uso de todas las herramientas MCP del servidor Coopuertos con casos de uso comunes.';
    }

    public function handle(Request $request): Response
    {
        $ejemplos = <<<'MARKDOWN'
# Ejemplos de Uso de Herramientas MCP

Esta guía proporciona ejemplos prácticos de uso de todas las herramientas MCP disponibles.

## Autenticación

### Iniciar Sesión

```json
{
  "method": "tools/call",
  "params": {
    "name": "iniciar_sesion",
    "arguments": {
      "email": "admin@coopuertos.com",
      "password": "contraseña_segura"
    }
  }
}
```

## Búsqueda

### Buscar Conductor por Cédula

```json
{
  "method": "tools/call",
  "params": {
    "name": "buscar_conductor",
    "arguments": {
      "query": "1234567890"
    }
  }
}
```

### Buscar Vehículo por Placa

```json
{
  "method": "tools/call",
  "params": {
    "name": "buscar_vehiculo",
    "arguments": {
      "query": "ABC123"
    }
  }
}
```

### Buscar Propietario

```json
{
  "method": "tools/call",
  "params": {
    "name": "buscar_propietario",
    "arguments": {
      "query": "Juan Pérez"
    }
  }
}
```

## Creación

### Crear Conductor

```json
{
  "method": "tools/call",
  "params": {
    "name": "crear_conductor",
    "arguments": {
      "cedula": "1234567890",
      "nombres": "Juan",
      "apellidos": "Pérez García",
      "conductor_tipo": "A",
      "rh": "O+",
      "numero_interno": "001",
      "celular": "3001234567",
      "correo": "juan.perez@ejemplo.com",
      "fecha_nacimiento": "1990-01-15",
      "nivel_estudios": "Bachillerato",
      "estado": "activo"
    }
  }
}
```

### Crear Vehículo

```json
{
  "method": "tools/call",
  "params": {
    "name": "crear_vehiculo",
    "arguments": {
      "placa": "ABC123",
      "marca": "Toyota",
      "modelo": "Corolla",
      "año": 2020,
      "color": "Blanco",
      "tipo": "Sedán",
      "estado": "activo"
    }
  }
}
```

### Crear Propietario

```json
{
  "method": "tools/call",
  "params": {
    "name": "crear_propietario",
    "arguments": {
      "nombre": "María González",
      "identificacion": "9876543210",
      "telefono": "3009876543",
      "correo": "maria.gonzalez@ejemplo.com"
    }
  }
}
```

## Edición

### Editar Conductor

```json
{
  "method": "tools/call",
  "params": {
    "name": "editar_conductor",
    "arguments": {
      "id": 1,
      "celular": "3001234568",
      "correo": "nuevo.email@ejemplo.com"
    }
  }
}
```

### Asignar Vehículo a Conductor

```json
{
  "method": "tools/call",
  "params": {
    "name": "asignar_vehiculo_conductor",
    "arguments": {
      "conductor_id": 1,
      "vehiculo_id": 5,
      "accion": "asignar"
    }
  }
}
```

## Eliminación

### Eliminar Conductor

```json
{
  "method": "tools/call",
  "params": {
    "name": "eliminar_conductor",
    "arguments": {
      "id": 1
    }
  }
}
```

## Gestión de Carnets

### Generar Carnet Individual

```json
{
  "method": "tools/call",
  "params": {
    "name": "generar_carnet",
    "arguments": {
      "conductor_id": 1
    }
  }
}
```

### Generar Carnets Masivos

```json
{
  "method": "tools/call",
  "params": {
    "name": "generar_carnets_masivos",
    "arguments": {
      "conductor_ids": [1, 2, 3, 4, 5]
    }
  }
}
```

**Respuesta** (guarda el `session_id`):
```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "total": 5,
  "estado": "pendiente"
}
```

### Consultar Progreso de Generación

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_estado_generacion",
    "arguments": {
      "session_id": "550e8400-e29b-41d4-a716-446655440000"
    }
  }
}
```

### Descargar Carnet Generado

```json
{
  "method": "tools/call",
  "params": {
    "name": "descargar_carnet",
    "arguments": {
      "session_id": "550e8400-e29b-41d4-a716-446655440000"
    }
  }
}
```

### Exportar Códigos QR

```json
{
  "method": "tools/call",
  "params": {
    "name": "exportar_qrs",
    "arguments": {
      "conductor_ids": [1, 2, 3],
      "formato": "zip"
    }
  }
}
```

### Obtener Plantilla Activa

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_plantilla_activa"
  }
}
```

### Personalizar Plantilla

```json
{
  "method": "tools/call",
  "params": {
    "name": "personalizar_plantilla",
    "arguments": {
      "nombre_sistema": "Coopuertos",
      "color_principal": "#1e40af",
      "mostrar_foto": true,
      "mostrar_qr": true
    }
  }
}
```

## Utilidades

### Obtener Estadísticas

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_estadisticas"
  }
}
```

### Listar Rutas

```json
{
  "method": "tools/call",
  "params": {
    "name": "listar_rutas"
  }
}
```

## Monitoreo y Salud

### Obtener Salud del Sistema

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_salud_sistema"
  }
}
```

### Obtener Métricas de Colas

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_metricas_colas"
  }
}
```

### Consultar Logs de Importación

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_logs_importacion",
    "arguments": {
      "estado": "completado",
      "limit": 10,
      "include_logs": false
    }
  }
}
```

### Consultar Logs de Generación de Carnets

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_logs_generacion_carnets",
    "arguments": {
      "tipo": "masivo",
      "estado": "completado",
      "limit": 20
    }
  }
}
```

### Consultar Logs de Laravel

```json
{
  "method": "tools/call",
  "params": {
    "name": "obtener_logs_laravel",
    "arguments": {
      "level": "error",
      "search": "Exception",
      "limit": 50
    }
  }
}
```

## Casos de Uso Completos

### Caso 1: Registrar Nuevo Conductor y Generar Carnet

```json
// 1. Crear conductor
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

// 2. Generar carnet (usar el ID del conductor creado)
{
  "method": "tools/call",
  "params": {
    "name": "generar_carnet",
    "arguments": {
      "conductor_id": 1
    }
  }
}
```

### Caso 2: Buscar Conductores Activos y Generar Carnets Masivos

```json
// 1. Buscar conductores (puedes hacer múltiples búsquedas)
{
  "method": "tools/call",
  "params": {
    "name": "buscar_conductor",
    "arguments": {
      "query": ""
    }
  }
}

// 2. Filtrar los activos y obtener sus IDs
// 3. Generar carnets masivos
{
  "method": "tools/call",
  "params": {
    "name": "generar_carnets_masivos",
    "arguments": {
      "conductor_ids": [1, 2, 3, 4, 5]
    }
  }
}

// 4. Consultar progreso periódicamente
{
  "method": "tools/call",
  "params": {
    "name": "obtener_estado_generacion",
    "arguments": {
      "session_id": "session-id-aqui"
    }
  }
}

// 5. Cuando esté completado, descargar
{
  "method": "tools/call",
  "params": {
    "name": "descargar_carnet",
    "arguments": {
      "session_id": "session-id-aqui"
    }
  }
}
```

### Caso 3: Monitoreo del Sistema

```json
// 1. Verificar salud del sistema
{
  "method": "tools/call",
  "params": {
    "name": "obtener_salud_sistema"
  }
}

// 2. Si hay problemas con colas, revisar métricas
{
  "method": "tools/call",
  "params": {
    "name": "obtener_metricas_colas"
  }
}

// 3. Revisar logs de errores
{
  "method": "tools/call",
  "params": {
    "name": "obtener_logs_laravel",
    "arguments": {
      "level": "error",
      "limit": 20
    }
  }
}
```

## Notas Importantes

1. **Todos los ejemplos asumen que ya tienes un token de autenticación** guardado y lo estás usando en el header `Authorization: Bearer <token>`.

2. **Los IDs en los ejemplos son ficticios**. Reemplázalos con IDs reales de tu base de datos.

3. **Las fechas deben estar en formato ISO 8601** (YYYY-MM-DD).

4. **Los archivos retornados en base64** (como ZIP de carnets) deben ser decodificados antes de guardarlos.

5. **Consulta el progreso periódicamente** para generaciones masivas, no hagas polling excesivo (cada 5-10 segundos es suficiente).
MARKDOWN;

        return Response::text($ejemplos);
    }
}
