<?php

use App\Http\Controllers\CarnetController;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\ConductorImportController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PqrController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Mcp\Servers\CoopuertosServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Ruta pública principal
Route::get('/', function () {
    return view('welcome');
});

// Rutas públicas (sin autenticación)
Route::get('/api/vehiculos/search', [VehicleController::class, 'search'])->name('api.vehiculos.search');
Route::get('/pqrs/servicio', [PqrController::class, 'publicForm'])->name('pqrs.form.public');
Route::post('/pqrs/enviar', [PqrController::class, 'store'])->name('pqrs.store.public');
Route::get('/pqrs/taquilla', [PqrController::class, 'publicFormTaquilla'])->name('pqrs.form.taquilla');
Route::post('/pqrs/taquilla/enviar', [PqrController::class, 'storeTaquilla'])->name('pqrs.taquilla.store');

// Ruta pública para mostrar un conductor específico por UUID
Route::get('/conductor/{uuid}', [ConductorController::class, 'show'])->name('conductor.public');

// Dashboard protegido por autenticación y verificación de email
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth'])
        ->name('dashboard');

    // Conductores
    Route::middleware('permission:ver conductores')->group(function () {
        Route::get('/conductores/{conductor}/info', [ConductorController::class, 'info'])->name('conductores.info');
        Route::get('/conductores/{uuid}/carnet/descargar', [ConductorController::class, 'descargarCarnet'])->name('conductores.carnet.descargar');
        Route::resource('conductores', ConductorController::class)->except('show');
    });

    // Importación de conductores (requiere permiso de crear)
    Route::middleware('permission:crear conductores')->group(function () {
        Route::get('/conductores/importar', [ConductorImportController::class, 'showImportForm'])->name('conductores.import');
        Route::post('/conductores/importar', [ConductorImportController::class, 'import'])->name('conductores.import.store');
        Route::get('/conductores/import/progreso/{sessionId}', [ConductorImportController::class, 'obtenerProgreso'])->name('conductores.import.progreso');
    });

    // Vehículos
    Route::resource('vehiculos', VehicleController::class);

    // Propietarios
    Route::resource('propietarios', PropietarioController::class);
    Route::get('/api/propietarios/search', [PropietarioController::class, 'search'])->name('api.propietarios.search');
    Route::get('/api/conductores/search', [ConductorController::class, 'search'])->name('api.conductores.search');

    // PQRS (Rutas protegidas)
    Route::get('/pqrs-qr', [PqrController::class, 'generateQR'])->name('pqrs.qr');
    Route::get('/pqrs/formulario/editar', [PqrController::class, 'editFormTemplate'])->name('pqrs.edit-template');
    Route::post('/pqrs/formulario/editar', [PqrController::class, 'updateFormTemplate'])->name('pqrs.update-template');
    Route::get('/pqrs/taquilla/editar', [PqrController::class, 'editFormTemplateTaquilla'])->name('pqrs.edit-template-taquilla');
    Route::post('/pqrs/taquilla/editar', [PqrController::class, 'updateFormTemplateTaquilla'])->name('pqrs.update-template-taquilla');
    Route::get('/pqrs/taquilla/qr', [PqrController::class, 'generateQRTaquilla'])->name('pqrs.taquilla.qr');
    Route::get('/pqrs-taquilla/create', [PqrController::class, 'createTaquilla'])->name('pqrs-taquilla.create');
    Route::get('/pqrs-taquilla/{pqrTaquilla}', [PqrController::class, 'showTaquilla'])->name('pqrs-taquilla.show');
    Route::get('/pqrs-taquilla/{pqrTaquilla}/edit', [PqrController::class, 'editTaquilla'])->name('pqrs-taquilla.edit');
    Route::put('/pqrs-taquilla/{pqrTaquilla}', [PqrController::class, 'updateTaquilla'])->name('pqrs-taquilla.update');
    Route::delete('/pqrs-taquilla/{pqrTaquilla}', [PqrController::class, 'destroyTaquilla'])->name('pqrs-taquilla.destroy');
    Route::delete('/pqrs/{pqr}/adjunto/{index}', [PqrController::class, 'deleteAttachment'])->name('pqrs.adjunto.delete');
    Route::resource('pqrs', PqrController::class);

    // Carnets
    Route::get('/carnets', [CarnetController::class, 'index'])->name('carnets.index');
    Route::get('/carnets/exportar', [CarnetController::class, 'exportar'])->name('carnets.exportar');
    Route::post('/carnets/generar', [CarnetController::class, 'generar'])->name('carnets.generar');
    Route::get('/carnets/personalizar', [CarnetController::class, 'personalizar'])->name('carnets.personalizar');
    Route::post('/carnets/guardar-plantilla', [CarnetController::class, 'guardarPlantilla'])->name('carnets.guardar-plantilla');
    Route::get('/carnets/progreso/{sessionId}', [CarnetController::class, 'obtenerProgreso'])->name('carnets.progreso');
    Route::get('/carnets/descargar/{sessionId}', [CarnetController::class, 'descargarZip'])->name('carnets.descargar-zip');
    Route::get('/carnets/descargar-ultimo-zip', [CarnetController::class, 'descargarUltimoZip'])->name('carnets.descargar-ultimo-zip');

    // Usuarios
    Route::middleware('permission:ver usuarios')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    });
    Route::middleware('permission:crear usuarios')->group(function () {
        Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    });
    Route::middleware('permission:editar usuarios')->group(function () {
        Route::get('/usuarios/{user}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    });
    Route::middleware('permission:eliminar usuarios')->group(function () {
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    });

    // Configuración (solo para rol Mango)
    Route::middleware('permission:gestionar configuracion')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    });
});

// Rutas de autenticación generadas por Laravel Breeze/Jetstream
require __DIR__.'/auth.php';

// Servidor MCP para Coopuertos
Mcp::web('/mcp/coopuertos', CoopuertosServer::class);
