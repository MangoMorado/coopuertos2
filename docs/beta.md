# Próximas Características para la etapa Beta de Coopuertos

## Dashboard de Reportes con Gráficos

Dashboard mejorado con visualizaciones interactivas y exportación de reportes.

**Características:**
- Gráficos de conductores por estado (activo/inactivo)
- Mapa de calor de asignaciones conductor-vehículo
- Exportación de reportes a PDF
- Botones de descarga de Carnets y QRs

**Tecnología:** Chart.js o ApexCharts integrado con Alpine.js

## Sistema de Notificaciones por Vencimientos

Alertas automáticas para vencimientos y eventos importantes del sistema.

**Características:**
- Vencimiento de revisión técnica (30, 15, 7 días antes)
- Vencimiento de SOAT y Licencia de Conducción
- Vehículos sin conductor asignado por más de X días
- Notificaciones por email y panel en dashboard
- Campos de fecha de vencimiento en vehículos y conductores

**Implementación:** Modelo `Notification` o driver `database` de Laravel, Job programado con `schedule:run`, cola de correos para envío masivo

## Auditoría y Historial de Cambios

Registro completo de todas las modificaciones en el sistema con trazabilidad.

**Características:**
- Registro de quién modificó, cuándo y qué cambió
- Vista de historial por entidad en `/auditoria` (solo rol Mango)
- Filtros por usuario, fecha y tipo de acción
- Exportación de logs de auditoría

**Implementación:** Paquete `spatie/laravel-activitylog` (compatible con spatie/permission)

## Generación de Carnets por Filtros

Sistema de generación de carnets con múltiples criterios de filtrado y vista previa.

**Características:**
- Filtrar por estado, tipo de conductor, rango de fechas y vehículo asignado
- Regenerar solo carnets modificados desde última generación
- Previsualización de cantidad antes de generar

**Ubicación:** `app/Http/Controllers/CarnetController.php`

## Portal Público de Verificación de Carnet

Página pública para validar carnets mediante UUID o QR.

**Características:**
- Validación por UUID o escaneo de QR
- Mostrar estado básico (activo/inactivo) con información limitada

## Importación Incremental con Vista Previa

Mejora del sistema de importación con previsualización antes de procesar.

**Características:**
- Previsualización de filas a importar
- Validaciones antes de encolar la importación masiva
- Reducción de errores y retroalimentación temprana

## PWA de la Aplicación

Convertir la aplicación en Progressive Web App para acceso móvil.

**Características:**
- Instalable en dispositivos móviles
- Funcionalidad offline básica
- Experiencia nativa mejorada

---

## Refactorización

### Eliminación de Campo Legacy `vehiculo` en Conductor

Eliminar campo redundante `vehiculo` del modelo Conductor ya que existe relación `vehicles()` con tabla pivot.

**Archivo:** `app/Models/Conductor.php`

**Pasos:**
- Verificar código dependiente del campo
- Migrar datos existentes a tabla pivot `conductor_vehicle`
- Eliminar columna mediante migración
- Actualizar seeders y factories

### Relación Propietario-Vehículo

Reemplazar campo texto `propietario_nombre` por relación formal `propietario_id` en modelo Vehicle.

**Archivo:** `app/Models/Vehicle.php`

**Cambio:** `propietario_nombre` (string) → `propietario_id` (FK)

**Justificación:** Normalización de BD y soporte para múltiples vehículos por propietario.

### Migración de Lógica de Controladores a Servicios

Extraer lógica de negocio de controladores a servicios especializados manteniendo controladores delgados.

**Archivos:** `app/Http/Controllers/CarnetController.php`, `app/Http/Controllers/ConductorController.php`

**Propuesta:** Crear servicios orquestadores (ej. `CarnetOrchestrator`) y Form Requests para validación.

### Validaciones Web a Form Requests

Migrar validaciones inline de controladores web a clases Form Request en `app/Http/Requests` para consistencia con API.

**Beneficio:** Validación centralizada y reutilizable.

### Servicio Unificado de Asignación Conductor-Vehículo

Extraer lógica de asignación a servicio `VehicleAssignmentService` reutilizable en web y API.

**Beneficio:** Reducción de duplicación y lógica centralizada.

### Centralización de Manejo de Fotos Base64

Crear servicio compartido para conversión y normalización de imágenes base64.

**Beneficio:** Lógica única de procesamiento de imágenes.

### Factorización de Exportadores

Unificar y reducir duplicación en exportadores y controladores para conductores, vehículos y propietarios.

**Beneficio:** Código más mantenible y reutilizable.

### Normalización de Estados con Enums

Unificar valores de estado en DB y modelos usando Enums de PHP (ej. `activo/inactivo` vs `Activo/Inactivo`).

**Beneficio:** Consistencia y type-safety.

### Patrón Strategy en CarnetGeneratorService

Implementar clases de estrategia para renderizado de variables (TextRenderer, QrRenderer, PhotoRenderer) en lugar de condicionales.

**Archivo:** `app/Services/CarnetGeneratorService.php`

**Beneficio:** Principio de Responsabilidad Única y facilidad para agregar nuevos tipos.

### Trait HasUuid Reutilizable

Centralizar generación de UUID en trait `HasUuid` para reutilizar en todos los modelos.

**Beneficio:** Eliminación de código duplicado en métodos `booted`.

### Estandarización de Respuestas de API

Crear trait `ApiResponser` para unificar formato de respuestas (éxito, error, validación) en todos los endpoints.

**Beneficio:** Consistencia en API y documentación Swagger.

### Autorización de Permisos en Rutas de API

Agregar middleware de permisos granulares a todas las rutas de API para Vehículos y Propietarios.

**Archivo:** `routes/api.php`

**Problema:** Las rutas de API para Vehículos y Propietarios solo requieren `auth:sanctum` pero carecen del middleware de permisos (`permission:crear vehiculos`, etc.) que sí tienen las rutas de Conductores.

**Riesgo:** Usuarios con rol 'User' podrían usar la API para crear, editar o eliminar vehículos y propietarios saltándose las restricciones de la interfaz web.

**Solución:** Agregar middleware `permission:` a todas las rutas CRUD de API para Vehículos y Propietarios, manteniendo consistencia con Conductores.

### Autenticación en Búsqueda Pública de Vehículos

Proteger ruta de búsqueda de vehículos que actualmente está abierta al público.

**Archivo:** `routes/web.php`

**Problema:** La ruta `/api/vehiculos/search` no tiene middleware de autenticación, permitiendo que cualquier persona busque información de la flota.

**Riesgo:** Exposición pública de información sensible de vehículos sin control de acceso.

**Solución:** Agregar middleware `auth:sanctum` y permisos apropiados a la ruta de búsqueda.

### Unificación de Permisos entre API y Web

Asegurar que los permisos sean consistentes entre la interfaz web y la API.

**Problema:** Existe discrepancia de seguridad: en web un usuario 'User' no puede borrar recursos, pero en API podría hacerlo debido a falta de validación de permisos granulares.

**Riesgo:** Violación de seguridad por inconsistencia entre interfaces.

**Solución:** Implementar validación de permisos granulares idéntica en API y Web para mantener paridad de seguridad.

---

## Nuevos Tests

### Test de Estrés de Importación Masiva

Validación de rendimiento y manejo de memoria para importación de grandes volúmenes de datos.

**Características:**
- Simular carga de Excel con más de 2000 conductores
- Validar manejo de memoria en servidor
- Verificar tiempos de ejecución de Jobs
- Confirmar actualización correcta del progreso

**Nota:** Este test debe estar en la vista `/test` y no ejecutarse con `php artisan test`, ni validarse en GitHub Actions.

### Test de Mocking para Google Drive

Pruebas de integración con Google Drive usando mocks para evitar peticiones reales a la API.

**Archivo:** `GoogleDriveImageDownloader`

**Características:**
- Utilizar sistema de Mocks de Laravel
- Validar lógica de descarga e integración
- Evitar peticiones reales a API de Google en tests

### Test de Permisos Dinámicos

Validación de que los permisos se reflejen inmediatamente en rutas y navbar al revocarlos.

**Archivo:** `ConfiguracionController`

**Características:**
- Verificar bloqueo inmediato de acceso a rutas al revocar permiso
- Confirmar ocultación en Navbar para usuarios afectados

### Test de Generación de Carnets sin Foto

Validar manejo correcto de carnets cuando un conductor no tiene foto cargada.

**Archivo:** `CarnetGeneratorService`

**Características:**
- Renderizar imagen por defecto o omitir campo
- Asegurar que la generación del PDF no falle

### Test Feature: Generación Masiva de Carnets

Verificar que la generación masiva de carnets funciona correctamente con jobs y logs.

**Características:**
- Verificar que `ProcesarGeneracionCarnets` encola jobs correctamente
- Confirmar creación de log con estado correcto

### Test Feature: Descarga de Carnet Individual

Validar generación y guardado de carnet individual en PDF.

**Características:**
- Generar PDF del carnet individual
- Verificar que la ruta del carnet se guarda en el conductor

### Test Feature: Importación con Errores

Validar manejo correcto de errores durante importación masiva.

**Características:**
- Importar archivo con filas inválidas
- Validar conteo correcto de errores en `ImportLog`

### Test API: Permisos y Sanctum

Verificar autenticación y autorización en endpoints protegidos de la API.

**Características:**
- Validar que endpoints protegidos requieren token Sanctum
- Verificar que se validan permisos correctos

### Tests de Herramientas MCP Individuales

Suite de tests específicos para cada herramienta MCP implementada.

**Archivo:** `tests/Feature/Mcp/Tools/McpToolsIndividualTest.php`

**Características:**
- Tests de búsqueda (conductor por nombre parcial, cédula, vehículo por placa)
- Tests CRUD (validación de campos requeridos, preservación de UUID)
- Tests de carnets (generación de imagen válida, exportación de QR en SVG)
- Tests de monitoreo (salud del sistema, métricas de colas)
