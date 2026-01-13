<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

/**
 * Recurso MCP con guía de integración para clientes MCP
 */
class GuiaIntegracionMcp extends Resource
{
    protected string $uri = 'coopuertos://mcp/guia-integracion';

    protected string $mimeType = 'text/markdown';

    public function name(): string
    {
        return 'Guía de Integración MCP';
    }

    public function title(): string
    {
        return 'Guía de Integración para Clientes MCP';
    }

    public function description(): string
    {
        return 'Guía completa para integrar clientes MCP con el servidor Coopuertos, incluyendo configuración, autenticación y ejemplos de código.';
    }

    public function handle(Request $request): Response
    {
        $guia = <<<'MARKDOWN'
# Guía de Integración para Clientes MCP

Esta guía te ayudará a integrar tu cliente MCP con el servidor Coopuertos.

## Configuración del Cliente MCP

### URL del Servidor

```
http://tu-dominio.com/mcp/coopuertos
```

O en desarrollo local:

```
http://localhost:8000/mcp/coopuertos
```

### Protocolo

El servidor utiliza **JSON-RPC 2.0** sobre HTTP POST.

## Autenticación

### Paso 1: Obtener Token

Primero, debes autenticarte usando la herramienta `iniciar_sesion`:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
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

**Respuesta exitosa**:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "content": [
      {
        "type": "text",
        "text": {
          "success": true,
          "token": "1|abc123...",
          "user": {
            "id": 1,
            "name": "Usuario",
            "email": "usuario@ejemplo.com"
          }
        }
      }
    ]
  }
}
```

### Paso 2: Usar el Token

Guarda el token y úsalo en todas las requests posteriores en el header `Authorization`:

```
Authorization: Bearer 1|abc123...
```

## Ejemplos de Código

### Python

```python
import requests
import json

# URL del servidor MCP
MCP_URL = "http://localhost:8000/mcp/coopuertos"

# Paso 1: Autenticación
def login(email, password):
    payload = {
        "jsonrpc": "2.0",
        "id": 1,
        "method": "tools/call",
        "params": {
            "name": "iniciar_sesion",
            "arguments": {
                "email": email,
                "password": password
            }
        }
    }
    
    response = requests.post(MCP_URL, json=payload)
    data = response.json()
    
    if "result" in data:
        token = data["result"]["content"][0]["text"]["token"]
        return token
    else:
        raise Exception("Error de autenticación")
    
    return None

# Paso 2: Llamar herramienta con token
def call_tool(token, tool_name, arguments):
    payload = {
        "jsonrpc": "2.0",
        "id": 1,
        "method": "tools/call",
        "params": {
            "name": tool_name,
            "arguments": arguments
        }
    }
    
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }
    
    response = requests.post(MCP_URL, json=payload, headers=headers)
    return response.json()

# Uso
token = login("usuario@ejemplo.com", "contraseña")
result = call_tool(token, "buscar_conductor", {"query": "1234567890"})
print(result)
```

### JavaScript/Node.js

```javascript
const axios = require('axios');

const MCP_URL = 'http://localhost:8000/mcp/coopuertos';

// Paso 1: Autenticación
async function login(email, password) {
  const payload = {
    jsonrpc: '2.0',
    id: 1,
    method: 'tools/call',
    params: {
      name: 'iniciar_sesion',
      arguments: {
        email: email,
        password: password
      }
    }
  };
  
  const response = await axios.post(MCP_URL, payload);
  const token = response.data.result.content[0].text.token;
  return token;
}

// Paso 2: Llamar herramienta con token
async function callTool(token, toolName, arguments) {
  const payload = {
    jsonrpc: '2.0',
    id: 1,
    method: 'tools/call',
    params: {
      name: toolName,
      arguments: arguments
    }
  };
  
  const headers = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  };
  
  const response = await axios.post(MCP_URL, payload, { headers });
  return response.data;
}

// Uso
(async () => {
  const token = await login('usuario@ejemplo.com', 'contraseña');
  const result = await callTool(token, 'buscar_conductor', { query: '1234567890' });
  console.log(result);
})();
```

### cURL

```bash
# 1. Autenticación
curl -X POST http://localhost:8000/mcp/coopuertos \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "iniciar_sesion",
      "arguments": {
        "email": "usuario@ejemplo.com",
        "password": "contraseña"
      }
    }
  }'

# 2. Usar herramienta con token
TOKEN="1|abc123..."

curl -X POST http://localhost:8000/mcp/coopuertos \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "buscar_conductor",
      "arguments": {
        "query": "1234567890"
      }
    }
  }'
```

## Manejo de Errores

Todas las respuestas de error siguen el formato JSON-RPC 2.0:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "error": {
    "code": -32000,
    "message": "Error descriptivo",
    "data": {
      "code": "PERMISSION_DENIED",
      "message": "No tienes permisos para realizar esta acción",
      "hint": "Se requiere el permiso 'crear conductores'"
    }
  }
}
```

**Códigos de error comunes**:
- `PERMISSION_DENIED`: No tienes permisos
- `VALIDATION_ERROR`: Error de validación
- `NOT_FOUND`: Recurso no encontrado
- `UNAUTHORIZED`: No autenticado
- `INVALID_CREDENTIALS`: Credenciales incorrectas

## Listar Herramientas Disponibles

Puedes listar todas las herramientas disponibles usando el método estándar MCP:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list"
}
```

## Listar Prompts Disponibles

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "prompts/list"
}
```

## Listar Recursos Disponibles

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "resources/list"
}
```

## Mejores Prácticas

1. **Cachear el token**: No hagas login en cada request. Guarda el token y reutilízalo.
2. **Manejar expiración**: Si recibes `UNAUTHORIZED`, vuelve a autenticarte.
3. **Validar permisos**: Antes de operaciones CRUD, verifica que el usuario tenga permisos.
4. **Manejar errores**: Siempre revisa los códigos de error y mensajes.
5. **Rate limiting**: Respeta los límites de rate limiting del servidor.
6. **Logs**: Registra errores y respuestas para debugging.

## Seguridad

- **HTTPS en producción**: Siempre usa HTTPS en producción.
- **Tokens seguros**: Almacena tokens de forma segura (no en código fuente).
- **Permisos mínimos**: Usa usuarios con permisos mínimos necesarios.
- **Validación**: Valida todos los datos antes de enviarlos.

## Soporte

Para más información:
- Consulta el recurso `coopuertos://mcp/documentacion` para documentación completa
- Consulta el recurso `coopuertos://mcp/ejemplos` para ejemplos de uso
- Usa el prompt `troubleshooting` para resolver problemas
MARKDOWN;

        return Response::text($guia);
    }
}
