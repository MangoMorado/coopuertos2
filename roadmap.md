# 🗺️ Roadmap de Desarrollo - Coopuertos

Roadmap de mejoras y nuevas funcionalidades para el sistema Coopuertos.

---
## v.0.2.x - Alpha tecnica

Se implementaron los módulos CRUD para conductores, propietarios y vehículos, junto con el módulo CRUD de usuarios con gestión de roles donde Admin puede crear solo usuarios tipo User, mientras que Mango puede crear User, Admin y Mango, integrado en navbar y configuración. Se implementó un sistema de roles y permisos con tres niveles (Mango, Admin, User) con permisos granulares por módulo, incluyendo vista de configuración para Mango y navbar dinámico según permisos del usuario.

Se agregó importación masiva de conductores desde Excel/CSV con procesamiento en segundo plano, validación de datos, manejo de errores y duplicados, descarga de fotos desde Google Drive y seguimiento de progreso en tiempo real.

Se implementó generador de códigos QR, generador de carnets masivos, diseñador web de carnets con capacidad de personalización visual, y funcionalidad para mostrar Relevo cuando la placa es No Asignado. Se implementaron Jobs en cola para generación de carnets masivos, se configuró Laravel Boost e integró con Cursor, Supervisor para gestión automática de workers en producción y configuración para instalación de Imagick en producción.

Se resolvieron problemas con la barra lateral, se mejoró el tema oscuro refactorizando para usar dark: de Tailwind con cambio sin recargar y toggle en sidebar, se optimizó el logo del navbar para evitar recargas excesivas y se realizaron mejoras generales de UI/UX. Se agregaron nuevos widgets y estadísticas de los CRUDs mostrando número de vehículos y conductores, parcialmente implementado con solo conductores, acciones rápidas como descargar carnets y funcionalidad de exportación de datos.

Se desarrolló API REST completa con documentación usando Swagger/OpenAPI, autenticación por tokens mediante Sanctum, endpoints CRUD para todos los recursos, rate limiting y throttling, versionado de API en v1, y colección de Postman para pruebas.

Se implementaron paneles de configuración global de permisos, paneles de salud de la aplicación y visualización de resultados de los tests. Se optimizaron consultas a base de datos para mejorar rendimiento, se desarrolló suite completa de tests, se refactorizaron archivos muy grandes con más de mil líneas, se documentó el código agregando PHPDoc a métodos complejos y se creó documentación técnica del sistema.

Se configuró servidor MCP (CoopuertosServer) en ruta /mcp/coopuertos con autenticación Sanctum, se implementaron 28 herramientas incluyendo búsqueda, CRUD completo para conductores, vehículos y propietarios, gestión de carnets individual y masivo, utilidades, monitoreo y funciones avanzadas, se crearon 5 prompts con guías interactivas para reportes, importación, permisos, troubleshooting y tutorial de la aplicación, y se configuraron 5 recursos incluyendo documentación del proyecto, roadmap, documentación MCP, guía de integración y ejemplos de uso, totalizando 37 capacidades MCP implementadas.

## v.0.3.x - Beta

Se corrigieron errores críticos de permisos en carnets mejorando la creación automática de directorios y manejo de errores. Se configuró sistema de correos con Poste.io mediante SMTP, creando notificación personalizada `ResetPasswordNotification` con mensajes en español y logo. Se restringió acceso a documentación API solo para rol Mango con tests de verificación. Se implementaron traducciones completas al español para autenticación y permisos. Se actualizó plantilla de carnets con campo RH activado. Se corrigió configuración de CI para usar SQLite. Se mejoraron validaciones: conductores permiten cambio de estado sin correo obligatorio; vehículos con límite de capacidad (80), validación de fechas y año configurable (1990-actual); propietarios con campos numéricos restringidos. Se realizaron mejoras de UI/UX: carnet proporcional en vista pública, navbar condicional, botón PDF solo para autenticados, columna "Estado" con badges de colores y soporte para estructura extendida en importación.

Roadmap Fase Beta:

### 🔧 Fase 1: Base Sólida - Refactorizaciones y Seguridad
**Objetivo:** Normalizar la base de datos, corregir vulnerabilidades de seguridad, mejorar la arquitectura y establecer patrones sólidos.

#### 1.1 Normalización de Base de Datos
- **Eliminación de Campo Legacy `vehiculo` en Conductor**
  - Verificar código dependiente del campo
  - Crear migración para migrar datos existentes a tabla pivot `conductor_vehicle`
  - Eliminar columna mediante migración
  - Actualizar seeders y factories
  - Actualizar controladores y servicios

- **Relación Propietario-Vehículo**
  - Crear migración para agregar `propietario_id` (FK) a tabla `vehicles`
  - Migrar datos existentes de `propietario_nombre` a relación formal
  - Actualizar modelo Vehicle con relación `belongsTo(Propietario::class)`
  - Actualizar formularios y controladores
  - Eliminar columna `propietario_nombre` mediante migración

- **Normalización de Estados con Enums**
  - Crear Enums PHP: `EstadoConductor`, `EstadoVehiculo`, `EstadoPropietario`
  - Actualizar modelos para usar Enums
  - Actualizar migraciones para usar Enums en lugar de strings
  - Actualizar controladores, servicios y vistas

#### 1.2 Seguridad y Permisos
- **Autorización de Permisos en Rutas de API**
  - Agregar middleware `permission:` a todas las rutas CRUD de Vehículos en `routes/api.php`
  - Agregar middleware `permission:` a todas las rutas CRUD de Propietarios en `routes/api.php`
  - Mantener consistencia con rutas de Conductores
  - Actualizar documentación Swagger

- **Autenticación en Búsqueda Pública de Vehículos**
  - Agregar middleware `auth:sanctum` a ruta `/api/vehiculos/search`
  - Agregar middleware `permission:ver vehiculos`
  - Actualizar documentación

- **Unificación de Permisos entre API y Web**
  - Auditar todas las rutas de API para verificar permisos granulares
  - Asegurar paridad de seguridad entre API y Web
  - Crear tests de verificación de permisos

#### 1.3 Refactorización de Arquitectura
- **Validaciones Web a Form Requests**
  - Crear Form Requests para validación de Conductores (`app/Http/Requests/`)
  - Crear Form Requests para validación de Vehículos
  - Crear Form Requests para validación de Propietarios
  - Migrar validaciones inline de controladores a Form Requests
  - Mantener consistencia con validaciones de API

- **Servicio Unificado de Asignación Conductor-Vehículo**
  - Crear servicio `VehicleAssignmentService` en `app/Services/`
  - Extraer lógica de asignación de controladores web y API
  - Centralizar lógica de desasignación y validaciones

- **Centralización de Manejo de Fotos Base64**
  - Crear servicio `ImageBase64Service` en `app/Services/`
  - Unificar conversión y normalización de imágenes base64
  - Refactorizar controladores para usar el servicio unificado

- **Factorización de Exportadores**
  - Identificar duplicación en exportadores de Conductores, Vehículos y Propietarios
  - Crear clase base `BaseExport` o trait compartido
  - Refactorizar exportadores para reducir duplicación

- **Estandarización de Respuestas de API**
  - Crear trait `ApiResponser` en `app/Http/Traits/`
  - Implementar métodos: `successResponse()`, `errorResponse()`, `validationResponse()`
  - Refactorizar controladores API para usar el trait
  - Actualizar documentación Swagger con formato estándar

#### 1.4 Mejoras de Código
- **Trait HasUuid Reutilizable**
  - Crear trait `HasUuid` en `app/Models/Traits/`
  - Mover lógica de generación UUID del modelo Conductor al trait
  - Aplicar trait a modelos que necesiten UUID (futuro)

- **Patrón Strategy en CarnetGeneratorService**
  - Crear interfaces para renderizadores: `VariableRendererInterface`
  - Crear clases: `TextRenderer`, `QrRenderer`, `PhotoRenderer`
  - Refactorizar `CarnetGeneratorService` para usar estrategias
  - Facilitar agregar nuevos tipos de variables

#### 1.5 Tests de Seguridad y Refactorización
- **Test API: Permisos y Sanctum**
  - Validar que endpoints protegidos requieren token Sanctum
  - Verificar que se validan permisos correctos en cada endpoint
  - Tests de acceso denegado para usuarios sin permisos

- **Test de Permisos Dinámicos**
  - Verificar bloqueo inmediato de acceso a rutas al revocar permiso
  - Confirmar ocultación en Navbar para usuarios afectados

---

### 🚀 Fase 2: Funcionalidades Principales - Nuevas Features
**Objetivo:** Implementar las funcionalidades principales solicitadas por los usuarios para mejorar la experiencia y utilidad del sistema.

#### 2.1 Auditoría y Trazabilidad
- **Instalación y Configuración de Activity Log**
  - Instalar paquete `spatie/laravel-activitylog`
  - Configurar modelo `Activity` y migraciones
  - Integrar con sistema de permisos existente

- **Registro de Actividades**
  - Registrar todas las modificaciones en Conductores, Vehículos y Propietarios
  - Registrar cambios en Usuarios y Permisos (solo Mango)
  - Registrar generación masiva de carnets e importaciones

- **Vista de Auditoría**
  - Crear controlador `AuditController` con ruta `/auditoria` (solo Mango)
  - Crear vista de listado con filtros por usuario, fecha y tipo de acción
  - Implementar paginación y búsqueda
  - Agregar exportación de logs de auditoría a CSV/Excel

#### 2.2 Sistema de Notificaciones
- **Modelo de Notificaciones**
  - Crear migración para tabla `notifications` (driver database de Laravel)
  - Agregar campos de fecha de vencimiento a vehículos (SOAT, revisión técnica)
  - Agregar campos de fecha de vencimiento a conductores (licencia de conducción)

- **Job Programado de Verificación**
  - Crear Job `VerificarVencimientos` para ejecución diaria
  - Configurar en `routes/console.php` con `schedule:run`
  - Verificar vencimientos: 30, 15 y 7 días antes
  - Detectar vehículos sin conductor asignado por más de X días

- **Notificaciones al Usuario**
  - Crear notificaciones en base de datos para alertas
  - Crear panel de notificaciones en dashboard
  - Implementar notificaciones por email (cola de correos)
  - Agregar contador de notificaciones no leídas en navbar

#### 2.3 Mejoras al Dashboard
- **Dashboard de Reportes con Gráficos**
  - Instalar Chart.js o ApexCharts (integración con Alpine.js)
  - Crear gráfico de conductores por estado (activo/inactivo)
  - Crear mapa de calor de asignaciones conductor-vehículo
  - Agregar botones de descarga de Carnets y QRs desde dashboard
  - Implementar exportación de reportes a PDF

#### 2.4 Generación de Carnets Avanzada
- **Generación de Carnets por Filtros**
  - Actualizar vista `/carnets` con formulario de filtros
  - Filtros: estado, tipo de conductor, rango de fechas, vehículo asignado
  - Implementar previsualización de cantidad antes de generar
  - Funcionalidad para regenerar solo carnets modificados desde última generación
  - Actualizar `CarnetController` y servicios relacionados

#### 2.5 Importación Mejorada
- **Importación Incremental con Vista Previa**
  - Modificar formulario de importación para mostrar vista previa
  - Leer y validar archivo antes de encolar importación masiva
  - Mostrar tabla con filas a importar y resultados de validación
  - Permitir editar/corregir datos antes de confirmar importación
  - Reducir errores y mejorar retroalimentación temprana

#### 2.6 Portal Público de Verificación
- **Página de Verificación de Carnet**
  - Crear ruta pública `/verificar-carnet`
  - Implementar búsqueda por UUID del conductor
  - Implementar escaneo de QR para validación
  - Mostrar estado básico (activo/inactivo) con información limitada
  - Diseño público sin navbar (similar a vista de conductor público)

#### 2.7 Tests de Funcionalidades
- **Test de Generación Masiva de Carnets**
  - Verificar que `ProcesarGeneracionCarnets` encola jobs correctamente
  - Confirmar creación de log con estado correcto

- **Test de Generación de Carnets sin Foto**
  - Renderizar imagen por defecto o omitir campo
  - Asegurar que la generación del PDF no falle

- **Test Feature: Descarga de Carnet Individual**
  - Generar PDF del carnet individual
  - Verificar que la ruta del carnet se guarda en el conductor

- **Test Feature: Importación con Errores**
  - Importar archivo con filas inválidas
  - Validar conteo correcto de errores en `ImportLog`

---

### 🌟 Fase 3: Optimizaciones Avanzadas - Experiencia y Calidad

**Objetivo:** Mejorar la experiencia de usuario, optimizar rendimiento y agregar capacidades avanzadas como PWA.

#### 3.1 Progressive Web App (PWA)
- **Manifest y Service Worker**
  - Crear archivo `manifest.json` con configuración de PWA
  - Crear Service Worker para funcionalidad offline básica
  - Configurar íconos para instalación en dispositivos móviles

- **Funcionalidad Offline**
  - Cachear assets estáticos (CSS, JS, imágenes)
  - Implementar estrategia de caché para vistas principales
  - Manejar modo offline con mensajes apropiados

- **Experiencia Nativa**
  - Configurar splash screen para instalación
  - Implementar notificaciones push (opcional)
  - Optimizar para dispositivos móviles

#### 3.2 Optimizaciones de Rendimiento
- **Migración de Lógica de Controladores a Servicios** (restante)
  - Extraer lógica de negocio de `CarnetController` a servicios orquestadores
  - Extraer lógica de negocio de `ConductorController` a servicios
  - Crear `CarnetOrchestrator` para orquestar flujos complejos
  - Mantener controladores delgados

- **Optimización de Consultas**
  - Revisar y optimizar consultas N+1 restantes
  - Agregar índices adicionales si es necesario
  - Implementar caché para consultas frecuentes

#### 3.3 Tests Avanzados y Calidad
- **Test de Estrés de Importación Masiva**
  - Crear vista de test en `/test` (no ejecutar con `php artisan test`)
  - Simular carga de Excel con más de 2000 conductores
  - Validar manejo de memoria en servidor
  - Verificar tiempos de ejecución de Jobs
  - Confirmar actualización correcta del progreso

- **Test de Mocking para Google Drive**
  - Crear mocks para `GoogleDriveImageDownloader`
  - Utilizar sistema de Mocks de Laravel
  - Validar lógica de descarga e integración sin peticiones reales

- **Tests de Herramientas MCP Individuales**
  - Crear suite de tests específicos para cada herramienta MCP
  - Tests de búsqueda (conductor por nombre parcial, cédula, vehículo por placa)
  - Tests CRUD (validación de campos requeridos, preservación de UUID)
  - Tests de carnets (generación de imagen válida, exportación de QR en SVG)
  - Tests de monitoreo (salud del sistema, métricas de colas)

#### 3.4 Mejoras de UI/UX Finales
- **Pulido de Interfaz**
  - Revisar y mejorar consistencia visual en todas las vistas
  - Optimizar responsividad en dispositivos móviles
  - Mejorar feedback visual en acciones del usuario

- **Documentación de Usuario**
  - Crear guías de usuario para funcionalidades principales
  - Agregar tooltips y ayuda contextual donde sea necesario

#### 3.5 Preparación para Producción
- **Optimizaciones Finales**
  - Revisar y optimizar código según análisis estático
  - Ejecutar suite completa de tests
  - Verificar compatibilidad con versiones de dependencias

- **Documentación Técnica**
  - Actualizar documentación PHPDoc donde sea necesario
  - Actualizar README con nuevas funcionalidades
  - Actualizar changelog y roadmap

---
