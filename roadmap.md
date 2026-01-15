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

---
