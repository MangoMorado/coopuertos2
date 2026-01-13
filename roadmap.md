# 🗺️ Roadmap de Desarrollo - Coopuertos

Roadmap de mejoras y nuevas funcionalidades para el sistema Coopuertos.

---

Fase 1: CRUDS Basicos
  - ✅ Conductores
  - ✅ Propietarios
  - ✅ Vehiculos
  - ✅ Módulo CRUD de usuarios con gestión de roles (Admin: solo User, Mango: User/Admin/Mango), integrado en navbar y configuración
  - ✅ Sistema de roles y permisos (Mango/Admin/User) con permisos granulares por módulo, vista de configuración para Mango y navbar dinámico según permisos
  - ✅ Importación masiva de conductores desde Excel/CSV con procesamiento en segundo plano, validación de datos, manejo de errores/duplicados, descarga de fotos desde Google Drive y seguimiento de progreso en tiempo real

Fase 2: Carnets
  - ✅ Generador de QR
  - ✅ Generador de Carnet Masivos
  - ✅ Diseñador web de Carnets
  - ✅ Si placa es No Asignado mostrar Relevo
  - ✅ Implementar Jobs en cola para generación de carnets masivos
  - ✅ Laravel Boost configurado e integrado con Cursor
  - ✅ Supervisor para gestión automatica de workers en producción
  - ✅ Configuración de instalaccion de Imagick en producción

Fase 3: UI/UX
  - ✅ UI: Problemas con la barra lateral
  - ✅ UI: Mejorar el tema oscuro - Refactorizado para usar dark: de Tailwind, cambio sin recargar, toggle en sidebar
  - ✅ UI: Logo del navbar se recarga mucho, "usar alguina tecnica para optimizar"
  - ✅ Mejoras de UI/UX

Fase 4: Dashboard
  - ✅ Nuevos Widgets / Estadisticas de los CRUDs (numero de vehiculos, conductores) - Parcialmente implementado (solo conductores)
  - ✅ Acciones rapidas (Descargar Carnets)
  - ✅ Exportación de datos

Fase 5: API
  - API REST completa
    - ✅ Documentación con Swagger/OpenAPI
    - ✅ Autenticación por tokens (Sanctum)
    - ✅ Endpoints CRUD para todos los recursos
    - ✅ Rate limiting y throttling
    - ✅ Versionado de API
    - ✅ Colección de postman

Fase 6: SuperAdmin / Mango
  - ✅ Paneles de confgiuración global de permisos
  - ✅ Paneles de salud de la App
  - ✅ Resultados de los test

Fase 7: Tests y Performance
  - Optimizar consultas a base de datos
    - Implementar Eager Loading en relaciones para evitar N+1 queries
    - Revisar y optimizar consultas en controladores principales
    - Agregar índices en campos de búsqueda frecuentes
  - ✅ Ampliar suite de tests
    - Tests funcionales para controladores principales
    - Tests de integración para generación de carnets
  - Documentación de código
    - Agregar PHPDoc a métodos complejos
    - Documentar endpoints API existentes
    - Crear documentación técnica del sistema
  - Caché y optimización
    - Implementar caché para consultas frecuentes
    - Caché de vistas y queries pesadas
    - Optimización de assets (CSS/JS)

Fase 8: MCP y Herramientas de IA
  - ✅ Servidor MCP configurado (CoopuertosServer) con rutas en `/mcp/coopuertos`
  - ✅ Herramientas MCP: BuscarConductor, BuscarVehiculo, BuscarPropietario, ObtenerEstadisticas, ListarRutas
  - ✅ Recursos MCP: DocumentacionProyecto, RoadmapProyecto
  - Autenticación OAuth para proteger el servidor MCP
  - Herramientas CRUD (crear, editar, eliminar) mediante MCP
  - Herramientas para gestión de carnets
  - Prompts MCP para tareas comunes y automatizables
  - Integración con sistemas externos mediante herramientas MCP
  - Herramientas de monitoreo y salud del sistema
  - UX: Tutorial y documentación de uso de la App

---

* Corregir exportación de excel de relevos