<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\PromptConfigurarPermisos;
use App\Mcp\Prompts\PromptGenerarReporte;
use App\Mcp\Prompts\PromptImportarConductores;
use App\Mcp\Prompts\PromptTroubleshooting;
use App\Mcp\Prompts\TutorialInteractivoApp;
use App\Mcp\Resources\DocumentacionMcpServer;
use App\Mcp\Resources\DocumentacionProyecto;
use App\Mcp\Resources\EjemplosUsoHerramientasMcp;
use App\Mcp\Resources\GuiaIntegracionMcp;
use App\Mcp\Resources\RoadmapProyecto;
use App\Mcp\Tools\AsignarVehiculoConductor;
use App\Mcp\Tools\BuscarConductor;
use App\Mcp\Tools\BuscarPropietario;
use App\Mcp\Tools\BuscarVehiculo;
use App\Mcp\Tools\CrearConductor;
use App\Mcp\Tools\CrearPropietario;
use App\Mcp\Tools\CrearVehiculo;
use App\Mcp\Tools\DescargarCarnet;
use App\Mcp\Tools\EditarConductor;
use App\Mcp\Tools\EditarPropietario;
use App\Mcp\Tools\EditarVehiculo;
use App\Mcp\Tools\EliminarConductor;
use App\Mcp\Tools\EliminarJobsFallidos;
use App\Mcp\Tools\EliminarPropietario;
use App\Mcp\Tools\EliminarVehiculo;
use App\Mcp\Tools\ExportarQRs;
use App\Mcp\Tools\GenerarCarnet;
use App\Mcp\Tools\GenerarCarnetsMasivos;
use App\Mcp\Tools\IniciarSesion;
use App\Mcp\Tools\ListarRutas;
use App\Mcp\Tools\ObtenerEstadisticas;
use App\Mcp\Tools\ObtenerEstadoGeneracion;
use App\Mcp\Tools\ObtenerLogsGeneracionCarnets;
use App\Mcp\Tools\ObtenerLogsImportacion;
use App\Mcp\Tools\ObtenerLogsLaravel;
use App\Mcp\Tools\ObtenerMetricasColas;
use App\Mcp\Tools\ObtenerPlantillaActiva;
use App\Mcp\Tools\ObtenerSaludSistema;
use App\Mcp\Tools\PersonalizarPlantilla;
use Laravel\Mcp\Server;

class CoopuertosServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Coopuertos MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Este servidor MCP proporciona acceso completo al sistema Coopuertos para gestión de conductores, vehículos, propietarios y carnets.
        
        **Autenticación**: Este servidor requiere autenticación mediante Laravel Sanctum.
        
        **Para comenzar**: Si el usuario no está autenticado, primero debes usar la herramienta `iniciar_sesion` 
        para solicitarle sus credenciales (email y contraseña). Una vez que obtengas el token, guárdalo y 
        úsalo en el header `Authorization: Bearer <token>` para todas las consultas posteriores.
        
        **Herramientas disponibles**:
        - **Autenticación**: iniciar_sesion
        - **Búsqueda**: buscar_conductor, buscar_vehiculo, buscar_propietario
        - **Creación**: crear_conductor, crear_vehiculo, crear_propietario
        - **Edición**: editar_conductor, editar_vehiculo, editar_propietario
        - **Eliminación**: eliminar_conductor, eliminar_vehiculo, eliminar_propietario
        - **Asignaciones**: asignar_vehiculo_conductor
        - **Gestión de Carnets**: 
          - generar_carnet (individual)
          - generar_carnets_masivos (masivo, en segundo plano)
          - obtener_estado_generacion (consultar progreso)
          - exportar_qrs (exportar códigos QR)
          - obtener_plantilla_activa (consultar plantilla)
          - personalizar_plantilla (actualizar plantilla)
          - descargar_carnet (obtener URL o archivo)
        - **Utilidades**: obtener_estadisticas, listar_rutas
        - **Monitoreo y Salud**:
          - obtener_salud_sistema (estado completo del sistema)
          - obtener_metricas_colas (métricas de jobs en cola)
          - obtener_logs_importacion (logs de importaciones)
          - obtener_logs_generacion_carnets (logs de generación de carnets)
          - obtener_logs_laravel (logs de Laravel)
        - **Super Poderes** (solo rol Mango):
          - eliminar_jobs_fallidos (eliminar jobs fallidos por ID, UUID o todos)
        
        **Prompts disponibles** (guías interactivas):
        - generar-reporte: Guía para generar reportes de conductores/vehículos con filtros
        - importar-conductores: Guía paso a paso para importación masiva de conductores
        - configurar-permisos: Asistencia para configurar permisos de usuarios
        - troubleshooting: Ayuda para resolver problemas comunes del sistema
        - tutorial-interactivo-app: Tutorial interactivo de uso de la aplicación web
        
        **Recursos disponibles** (documentación e información):
        - coopuertos://documentacion: Documentación completa del proyecto (README)
        - coopuertos://roadmap: Roadmap del proyecto con todas las fases
        - coopuertos://mcp/documentacion: Documentación completa del servidor MCP
        - coopuertos://mcp/guia-integracion: Guía de integración para clientes MCP
        - coopuertos://mcp/ejemplos: Ejemplos de uso de todas las herramientas
        
        **Permisos**: Todas las operaciones CRUD requieren permisos específicos según el módulo:
        - `crear {modulo}`, `editar {modulo}`, `eliminar {modulo}`, `ver {modulo}`
        - Para carnets: `crear carnets`, `editar carnets`
        
        **Flujo recomendado**:
        1. Verificar si el usuario tiene un token guardado
        2. Si no tiene token, usar `iniciar_sesion` para obtenerlo
        3. Guardar el token de forma segura
        4. Usar el token en todas las requests posteriores
        5. Verificar permisos antes de realizar operaciones CRUD
        
        **Generación de Carnets**:
        - Para carnet individual: usar `generar_carnet` con conductor_id
        - Para generación masiva: usar `generar_carnets_masivos` (retorna session_id)
        - Consultar progreso: usar `obtener_estado_generacion` con session_id
        - Descargar resultado: usar `descargar_carnet` con session_id cuando estado sea "completado"
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        // Autenticación
        IniciarSesion::class,
        // Búsqueda
        BuscarConductor::class,
        BuscarVehiculo::class,
        BuscarPropietario::class,
        // Creación
        CrearConductor::class,
        CrearVehiculo::class,
        CrearPropietario::class,
        // Edición
        EditarConductor::class,
        EditarVehiculo::class,
        EditarPropietario::class,
        // Eliminación
        EliminarConductor::class,
        EliminarVehiculo::class,
        EliminarPropietario::class,
        // Asignaciones
        AsignarVehiculoConductor::class,
        // Gestión de Carnets
        GenerarCarnet::class,
        GenerarCarnetsMasivos::class,
        ObtenerEstadoGeneracion::class,
        ExportarQRs::class,
        ObtenerPlantillaActiva::class,
        PersonalizarPlantilla::class,
        DescargarCarnet::class,
        // Utilidades
        ObtenerEstadisticas::class,
        ListarRutas::class,
        // Monitoreo y Salud
        ObtenerSaludSistema::class,
        ObtenerMetricasColas::class,
        ObtenerLogsImportacion::class,
        ObtenerLogsGeneracionCarnets::class,
        ObtenerLogsLaravel::class,
        // Super Poderes
        EliminarJobsFallidos::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        PromptGenerarReporte::class,
        PromptImportarConductores::class,
        PromptConfigurarPermisos::class,
        PromptTroubleshooting::class,
        TutorialInteractivoApp::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        DocumentacionProyecto::class,
        RoadmapProyecto::class,
        DocumentacionMcpServer::class,
        GuiaIntegracionMcp::class,
        EjemplosUsoHerramientasMcp::class,
    ];
}
