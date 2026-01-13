# Coopuertos App

## *v. 0.1.7.1*
- Comando php artisan new-mango ahora registra usuarios

## *v. 0.1.7*
- Tests nuevos a la suite de test de PHPUnit: Dashboard (web/API), Usuarios, Configuración, API REST (health, rate limiting, validación, respuestas), Servicios (HealthCheckService, StorageHelper), Modelos (Conductor, Vehicle, User, Propietario). Consolidación y resumen del plan de tests.
- GitHub Actions CI: workflow automatizado para ejecutar tests PHPUnit y Laravel Pint en push/PR

## *v. 0.1.6*
- Suite de tests PHPUnit (296 test): tests web y API para Conductores, Vehículos, Propietarios y Carnets. Tests unitarios para servicios y jobs de generación de carnets. Corrección de cast de fecha en modelo Vehicle.
- Eliminada la card Resultados de los Tests de /configuracion

## *v. 0.1.5*
- Paneles de salud de la app en /configuracion: estado de BD, colas, almacenamiento, extensiones PHP y versiones
- Resultados de tests en /configuracion: estadísticas de tests (total, feature, unit)
- Nuevo endpoint API `/api/v1/health`: retorna información completa de salud del sistema (público, sin autenticación)
- Servicio HealthCheckService: centraliza verificaciones de salud del sistema

## *v. 0.1.4*
- Fix path duplicado en cURL generado por sawgger

## *v. 0.1.3*
- API REST completa: Sanctum auth, CRUD conductores/vehículos/propietarios, dashboard stats, Swagger docs, colección Postman (23 endpoints)

## *v. 0.1.2*
- Sistema de exportación de datos (Excel y CSV) para conductores, vehículos y propietarios
- Clases Export creadas (ConductoresExport, VehiculosExport, PropietariosExport) con encabezados en español y formateo de datos

## *v. 0.1.1.1*
- Mejoras al script de deploy start.sh

## *v. 0.1.1*
- Ajustes visuales del navbar
- Se elimino todo lo relacionado con PQRS

## *v. 0.1*
- Sistema de notificaciones Toast con auto-cierre y barra de progreso visual
- Skeleton loaders para tablas, tarjetas y formularios con animaciones optimizadas
- Reemplazo de mensajes de sesión antiguos por toasts automáticos en todas las vistas
- Búsqueda automática en tiempo real (sin botones) para vehículos, propietarios y usuarios
- Coherencia visual unificada entre todas las tablas de listado (max-w-8xl)
- Skeleton loaders en búsquedas AJAX y formularios con autocomplete
- Vehículos ahora también guarda imágenes como base64 (igual que conductores)

## *v. 0.0.9*
- Correcciones de errores
- Implementadas mejoras de UI/UX

## *v. 0.0.8*
- Nuevo comando Artisan `new-mango` para asignar el rol Mango a usuarios por email
- Cambios generales en el navbar
- Implementado script inline + cookies para evitar flash visual del navbar al cambiar de vista. Ajustado contenido principal para evitar superposición.
- Cambiada la ruta de /users a /usuarios
- Mejoras de coherencia visual en la vista /usuarios

## *v. 0.0.7.1*
- Corrección de permisos en producción: actualizado script `start.sh` para crear directorios `storage/logs` y `public/storage/carnet_previews` antes de establecer permisos
- Actualizado comando Artisan `storage:setup-directories` para incluir todos los directorios necesarios de storage y framework
- Mejorado manejo de errores en `ConductorController` al crear directorios de previsualización de carnets

## *v. 0.0.7*
- Cambio arquitectónico mayor: fotos de conductores y vehículos ahora se guardan como base64 en la base de datos en lugar de archivos locales
- Migraciones modificadas: columna `foto` en tablas `conductors` y `vehicles` cambiada de `string` a `longText` para soportar base64
- Actualizados controladores, servicios e importaciones para convertir y manejar fotos en base64
- Corrección crítica de error "Call to a member function format() on null" en producción: agregada validación null-safe para fechas (created_at, updated_at, fecha, hora) en vistas users/index, propietarios/show, pqrs/show-taquilla, pqrs/show, pqrs/index y conductores/info, ahora muestra 'N/A' o 'No registrada' cuando las fechas son null

## *v. 0.0.6.5.5 | 0.0.6.4.2*
- Nixpacks.toml editado

## *v. 0.0.6.4.1*
- Actualización del script de creación de directorios

## *v. 0.0.6.4*
- Solución de error de permisos en producción: script automático de creación de directorios (scripts/setup-storage.php), comando Artisan `storage:setup-directories`, helper StorageHelper para manejo de errores, integrado en composer post-install-cmd/post-update-cmd

## *v. 0.0.6.3*
- Migración de Nixpacks a Railway Buildpacks: actualizado script de supervisor con detección automática de Railway Buildpacks, referencias genéricas para compatibilidad con múltiples buildpacks, actualización de documentación en README para Railway Buildpacks

## *v. 0.0.6.2*
- Mejoras al script de supervisor: detección automática de contenedores (Nixpacks/Docker), soporte para instalación de supervisor vía paquetes APT, actualización de documentación en README para despliegues en contenedores

## *v. 0.0.6.1*
- Script de configuración automática de Supervisor para workers de colas en producción (scripts/setup-supervisor.php), integrado en composer post-install-cmd para ejecución automática durante deploy, documentación completa de gestión de colas en README

## *v. 0.0.6*
- Sistema de generación de carnets con jobs en cola (ProcesarGeneracionCarnets, GenerarCarnetJob, FinalizarGeneracionCarnets), modelo CarnetGenerationLog para seguimiento, eliminado código legacy (GenerarCarnetsMasivo, GenerarCarnetPDF, CarnetBatchProcessor), mejoras en vista de exportación

## *v. 0.0.5.9*
- Servidor MCP de Coopuertos configurado con herramientas y recursos
- Laravel Boost instalado e integrado con Cursor para asistencia de IA
- Refactorizado CarnetController: dividido en servicios especializados (FontManager, ImageProcessorService, CarnetPdfConverter, CarnetGeneratorService, CarnetBatchProcessor, CarnetTemplateService)
- Corregida variable "vehiculo" en diseñador de carnets y vista pública del conductor
- Creado seeder CarnetTemplateSeeder con plantilla predeterminada "Coopuertos" y configuración de variables 
- Ahora el worker de importación de conductores trabaja cada nuevo registro de forma individual
- Se elimino generación individual basica de carnets para centralizar todo en el modelo del diseñador de carnets
- Nueva vista /carnets/exportar para centralizar la exportación de carnets

## *v. 0.0.5.8*
- Sistema de importación masiva de conductores desde Excel/CSV con procesamiento en segundo plano
- Implementado Job worker para procesar importaciones de forma asíncrona (ProcesarImportacionConductores)
- Nueva tabla `import_logs` para almacenar progreso y logs de importaciones de forma persistente
- Vista de importación en `/conductores/importar` con seguimiento de progreso en tiempo real
- Muestra tiempo transcurrido y tiempo estimado restante durante la importación
- Logs persistentes que permiten salir y regresar a la página para ver el progreso guardado
- Validación automática de columnas CSV con detección de delimitadores (coma o punto y coma)
- Manejo de duplicados, errores y reportes detallados de importación
- Descarga automática de fotos desde URLs de Google Drive durante la importación

## *v. 0.0.5.7*
- Se establecieron 7 fases de desarrollo para la app
- Sistema de roles y permisos con Spatie Permission (Mango, Admin, User)
- Módulo CRUD de usuarios con gestión de roles
- Vista de configuración de permisos por módulo (solo Mango)
- Mejoras en la vista de conductores

## *v. 0.0.5.6*
- Se creo el documento Roadmap para definir nuevas caracteristicas y requerimiento de desarrollo
- Instalada Dependencia usada via CDN CropperJS, ahora funciona en local
- Se actualizaron las dependencias

## *v. 0.0.5.5*
- Eliminado el archivo de configuración de nixpacks

## *v. 0.0.5.4*
- Ultima oportunidad al Nixpacks xd

## *v. 0.0.5.3*
- Correciones al archivo Nixpacks

## *v. 0.0.5.2*
- Correciones al archivo Nixpacks

## *v. 0.0.5.1*
- Creado el archivo Nixpacks

## *v. 0.0.5*
- Nuevo sistema Carnets
- Nuevo diseñador de Carnets
- Nueva función Descarga de carnets

## *v. 0.0.4*
- Se elimina las placas asociadas a conductores, ahora todo se administra desde la pestaña de vehiculos
- Ahora los PQRS tienen estados
- Nuevo PQRS Taquilla

## *v. 0.0.3*
- Actualizado a heroicons v2
- Nueva función PQRS
- Nueva función editor visual de PQRS

## *v. 0.0.2*
- Nuevas funciones CRUD: Vehiculos
- Nuevas funciones CRUD: Propietarios

## *v. 0.0.1.4*
- Busqueda ampliada y en tiempo real de conductores
- Tema oscuro implementado

## *v. 0.0.1.3*
- Nuevo Sidebar
- Nuevos temas claro/oscuro
- Nuevo sistema de recorte de imágenes 1:1
- Corregido, ahora las placas en mayúsculas automáticas.

## *v. 0.0.1.2*
- Force scheme HTTPS en AppServiceProvider.

## *v. 0.0.1.1*
- Trust proxies y URLs para servir login en HTTPS.

## *v. 0.0.1*
- UI en español (auth, dashboard, navegación, perfil, conductores).
- Logo e imágenes sirven desde assets/ uploads.
- CRUD de conductores con edición, carnet, QR y fotos públicas.

# Mejoras Pendientes:

- 📝 Reportes y estadísticas
- 📝 Exportación de datos (Excel, CSV)
- 📝 Notificaciones por email
- 📝 Dashboard con más métricas
- 📝 API REST para integraciones
- ✅ Sistema de roles y permisos de usuario
- 📝 Historial de auditoría y logs de cambios
- ✅ Importación masiva de datos desde Excel/CSV
- 📝 Sistema de notificaciones push en tiempo real
- 📝 App móvil para consulta de información