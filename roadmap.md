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

Correcciones según informe de testing organizadas en fases:

### Fase 1: Errores críticos y seguridad (Prioridad Alta)

**Carnets - Error crítico de permisos**
- ✅ Corregir error de escritura en directorio `/app/public/uploads/carnets` al subir fondos de carnet. Verificar permisos de escritura y configuración de rutas.

**Autenticación - Correo de restablecimiento**
- Corregir envío de correos de recuperación de contraseña que no llegan al usuario. Revisar configuración de mail y cola de trabajos.

**Seguridad - Control de acceso a documentación API**
- ✅ Restringir acceso a `/api/documentation` exclusivamente para usuarios con rol Superadmin (Mango). Actualmente permite acceso a Admin y User.

**Plantilla  Carnet - Actualizar la plantilla de carnets**
- ✅ Revisar espaciado, actualizar el seeder, agregar tipo de sangre al carnet.

### Fase 2: Validaciones y mejoras funcionales

**Autenticación - Traducciones**
- ✅ Traducir al español mensajes de autenticación: "These credentials do not match our records" y "Please wait before retrying" en restablecimiento de contraseña.

**Conductores - Cambio de estado**
- ✅ Permitir cambiar estado de conductor (Activo/Inactivo) sin requerir correo electrónico obligatorio cuando el conductor no tiene correo.

**Vehículos - Validaciones**
- ✅ Implementar límite máximo de capacidad de pasajeros (sugerencia: 80 pasajeros).
- ✅ Validar que fecha de revisión técnica no permita fechas futuras.
- ✅ Mejorar validación de año de fabricación con rango configurable o límites dinámicos mínimo/máximo.
- ✅ Corregir reflejo de cambio de estado de vehículos en el dashboard.

**Propietarios - Validaciones**
- ✅ Restringir campo teléfono para aceptar solo números.
- ✅ Restringir campo identificación para aceptar solo números.

### Fase 3: Mejoras de UI/UX

**Carnets - Ajuste de diseño**
- ✅ Ajustar tamaño del carnet en vista pública para que sea proporcional al tamaño de la página.
- ✅ En caso de no estar loggueado el usuario debe no aparecer el navbar

---
