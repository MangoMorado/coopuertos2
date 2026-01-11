# Propuesta de Implementación: API REST Completa - Fase 5

## 📋 Análisis de la Situación Actual

### Stack Tecnológico Actual
- **Laravel 12** (Framework principal)
- **Laravel Breeze 2.3** (Autenticación web con sesiones)
- **Spatie Laravel Permission 6.24** (Roles y permisos granulares)
- **PHP 8.4**
- Controladores tradicionales que devuelven vistas Blade
- Algunos endpoints JSON existentes (búsquedas: `/api/vehiculos/search`, `/api/conductores/search`, `/api/propietarios/search`)

### Recursos Principales
1. **Conductores** - CRUD completo con relaciones (vehículos, asignaciones)
2. **Vehículos** - CRUD completo con relaciones (conductores, propietarios)
3. **Propietarios** - CRUD completo
4. **Usuarios** - Gestión de usuarios con roles
5. **Carnets** - Sistema de generación de carnets
6. **Dashboard** - Estadísticas y métricas

### Sistema de Permisos Actual
- Roles: Mango (SuperAdmin), Admin, User
- Permisos granulares por módulo: `ver {modulo}`, `crear {modulo}`, `editar {modulo}`, `eliminar {modulo}`
- Middleware de permisos ya implementado (`permission:ver conductores`, etc.)

---

## 🎯 Propuesta de Arquitectura API

### 1. Estructura de Rutas

```
/api/v1/
  ├── auth/
  │   ├── POST   /login          - Autenticación (email + password)
  │   ├── POST   /logout         - Revocar token actual
  │   ├── GET    /user           - Obtener usuario autenticado
  │   └── POST   /refresh        - Renovar token (opcional)
  │
  ├── conductores/
  │   ├── GET    /               - Listar (paginated, filtros)
  │   ├── POST   /               - Crear
  │   ├── GET    /{id}           - Mostrar
  │   ├── PUT    /{id}           - Actualizar
  │   ├── DELETE /{id}           - Eliminar
  │   ├── GET    /{uuid}/public  - Vista pública (sin auth)
  │   └── GET    /search         - Búsqueda
  │
  ├── vehiculos/
  │   ├── GET    /               - Listar
  │   ├── POST   /               - Crear
  │   ├── GET    /{id}           - Mostrar
  │   ├── PUT    /{id}           - Actualizar
  │   ├── DELETE /{id}           - Eliminar
  │   └── GET    /search         - Búsqueda
  │
  ├── propietarios/
  │   ├── GET    /               - Listar
  │   ├── POST   /               - Crear
  │   ├── GET    /{id}           - Mostrar
  │   ├── PUT    /{id}           - Actualizar
  │   ├── DELETE /{id}           - Eliminar
  │   └── GET    /search         - Búsqueda
  │
  ├── usuarios/
  │   ├── GET    /               - Listar (solo Mango/Admin)
  │   ├── POST   /               - Crear
  │   ├── GET    /{id}           - Mostrar
  │   ├── PUT    /{id}           - Actualizar
  │   └── DELETE /{id}           - Eliminar
  │
  └── dashboard/
      └── GET    /stats          - Estadísticas
```

### 2. Versionado de API

**Estrategia: URL Versioning**
- `/api/v1/` - Versión actual (v1)
- Facilita migración futura a v2 sin romper v1
- Recomendado por Laravel y la comunidad

### 3. Respuestas JSON Estándar

```json
// Éxito
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}

// Error de validación
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": ["El email es requerido"]
  }
}

// Error de autenticación/autorización
{
  "success": false,
  "message": "No autorizado",
  "error": "Unauthenticated"
}

// Paginación
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": { ... }
}
```

### 4. Autenticación con Sanctum

**Flujo de autenticación:**
1. Cliente envía credenciales a `POST /api/v1/auth/login`
2. Servidor valida credenciales
3. Servidor crea token con Sanctum: `$user->createToken('api-token', ['*'])->plainTextToken`
4. Cliente recibe token y lo incluye en header: `Authorization: Bearer {token}`
5. Cliente usa token en todas las peticiones subsecuentes
6. Cliente puede revocar con `POST /api/v1/auth/logout`

**Scopes/Abilities (opcional):**
- Por ahora usar `['*']` (todos los permisos)
- En el futuro, mapear permisos de Spatie a scopes de Sanctum

### 5. Integración con Permisos Existentes

**Estrategia: Reutilizar middleware de Spatie Permission**
- Los controladores API pueden usar los mismos middlewares:
  - `permission:ver conductores`
  - `permission:crear conductores`
  - etc.
- El modelo User ya tiene `HasRoles` trait, funciona igual en API

### 6. Rate Limiting

**Estrategia por tipo de endpoint:**
- **Autenticación**: `throttle:5,1` (5 intentos por minuto)
- **Endpoints públicos**: `throttle:60,1` (60 por minuto)
- **Endpoints autenticados**: `throttle:120,1` (120 por minuto)
- **Endpoints pesados** (generación carnets): `throttle:10,1` (10 por minuto)

### 7. CORS y Configuración

- Configurar CORS en `config/cors.php` (Laravel 12 incluye esto)
- Permitir dominios específicos para producción
- Headers necesarios: `Authorization`, `Content-Type`, `Accept`

---

## 📦 Cambios Necesarios en el Stack

### Instalación Requerida

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Cambios Mínimos Necesarios

1. **Modelo User** - Agregar trait `HasApiTokens`
   ```php
   use Laravel\Sanctum\HasApiTokens;
   
   class User extends Authenticatable
   {
       use HasApiTokens, HasFactory, Notifiable, HasRoles;
       // ...
   }
   ```

2. **Configuración** - Archivo `config/sanctum.php` (se publica automáticamente)
   - Configurar expiración de tokens
   - Configurar middlewares

3. **Bootstrap** - Agregar middleware de Sanctum (ya viene configurado)

4. **Nuevo archivo de rutas** - `routes/api.php`
   - Configurar en `bootstrap/app.php`

### Sin Cambios en:
- ✅ Controladores web existentes
- ✅ Rutas web existentes
- ✅ Autenticación web (Breeze)
- ✅ Vistas Blade
- ✅ Sistema de permisos (Spatie)
- ✅ Middleware de permisos

---

## 🏗️ Arquitectura de Controladores API

### Opción 1: Controladores API Separados (RECOMENDADO)

**Estructura:**
```
app/Http/Controllers/Api/
  └── V1/
      ├── AuthController.php
      ├── ConductorController.php
      ├── VehicleController.php
      ├── PropietarioController.php
      ├── UserController.php
      └── DashboardController.php
```

**Ventajas:**
- Separación clara entre web y API
- Fácil mantenimiento
- Puede evolucionar independientemente
- Reutiliza lógica común (Form Requests, Services)

**Desventajas:**
- Duplicación potencial de lógica
- Más archivos

### Opción 2: Mismos Controladores con Formato Condicional

**Estrategia:** Controladores actuales detectan si es API y devuelven JSON

**Ventajas:**
- Menos duplicación
- Menos archivos

**Desventajas:**
- Controladores más complejos
- Mezcla responsabilidades
- Difícil de mantener

**Recomendación: Opción 1 (Controladores API separados)**

---

## 📚 Recursos API (API Resources)

**Usar Eloquent API Resources de Laravel**

Ejemplo:
```php
// app/Http/Resources/ConductorResource.php
class ConductorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            // ... más campos
            'vehiculo' => new VehicleResource($this->whenLoaded('asignacionActiva.vehicle')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

**Ventajas:**
- Formato consistente
- Control sobre qué campos exponer
- Fácil de extender
- Transformación de relaciones

---

## 📖 Documentación API: Swagger/OpenAPI

### Opción 1: L5-Swagger (laravel-swagger)

**Paquete:** `darkaonline/l5-swagger`

**Ventajas:**
- Integración fácil con Laravel
- Genera documentación desde anotaciones PHPDoc
- Interfaz Swagger UI integrada
- Compatible con OpenAPI 3.0

**Implementación:**
- Agregar anotaciones a controladores
- Generar documentación: `php artisan l5-swagger:generate`
- Acceso: `/api/documentation`

### Opción 2: API Blueprint + Aglio

**Ventajas:**
- Más control sobre documentación
- Separado del código

**Desventajas:**
- Mantenimiento manual
- Más trabajo inicial

**Recomendación: L5-Swagger** (más rápido y mantenible)

---

## 🗺️ Plan de Implementación (Fases)

### Fase 1: Setup Básico (1-2 días)
- [x] Instalar Laravel Sanctum
- [x] Configurar Sanctum
- [x] Crear estructura de rutas API (`routes/api.php`)
- [x] Configurar CORS
- [x] Agregar trait `HasApiTokens` al modelo User
- [x] Crear `AuthController` básico (login, logout, user)

### Fase 2: Endpoints de Autenticación (1 día)
- [x] Implementar `POST /api/v1/auth/login`
- [x] Implementar `POST /api/v1/auth/logout`
- [x] Implementar `GET /api/v1/auth/user`
- [x] Agregar rate limiting a endpoints de auth

### Fase 3: Recursos CRUD Básicos (3-4 días)
- [x] Crear API Resources para cada modelo
- [x] Implementar endpoints de Conductores (CRUD)
- [x] Implementar endpoints de Vehículos (CRUD)
- [x] Implementar endpoints de Propietarios (CRUD)
- [x] Integrar middleware de permisos

### Fase 4: Endpoints Adicionales (2-3 días)
- [x] Implementar endpoint de Dashboard (stats)
- [x] Implementar endpoints de búsqueda
- [x] Endpoint público de conductor por UUID -> disponible sin auth

### Fase 5: Documentación y Mejoras (2-3 días)
- [x] Instalar L5-Swagger
- [x] Agregar anotaciones PHPDoc a controladores
- [x] Generar documentación Swagger
- [x] Configurar rate limiting avanzado
- [x] Optimización de queries (eager loading)
- [x] Validación de errores y mensajes

---

## 📝 Notas Adicionales

### Seguridad
- Todos los tokens deben transmitirse por HTTPS en producción
- Considerar expiración de tokens (configurable en Sanctum)
- Implementar revocación de tokens por usuario
- Logs de acceso API (opcional, para auditoría)

### Performance
- Implementar caché en endpoints de lectura frecuente (opcional)
- Eager loading en relaciones para evitar N+1
- Paginación estándar (15-50 items por página)

---
