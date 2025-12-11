<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConductorController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\PqrController;
// Ruta pública principal
Route::get('/', function () {
    return view('welcome');
});

// Rutas públicas (sin autenticación)
Route::get('/api/vehiculos/search', [VehicleController::class, 'search'])->name('api.vehiculos.search');
Route::get('/pqrs/formulario', [PqrController::class, 'publicForm'])->name('pqrs.form.public');
Route::post('/pqrs/enviar', [PqrController::class, 'store'])->name('pqrs.store.public');

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
	Route::get('/conductores/{conductor}/carnet', [ConductorController::class, 'generarCarnet'])
    ->name('conductores.carnet');


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


    // CRUD completo de conductores (excepto show)
    Route::resource('conductores', ConductorController::class)->except('show');

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
    Route::delete('/pqrs/{pqr}/adjunto/{index}', [PqrController::class, 'deleteAttachment'])->name('pqrs.adjunto.delete');
    Route::resource('pqrs', PqrController::class);
});

// Rutas de autenticación generadas por Laravel Breeze/Jetstream
require __DIR__.'/auth.php';
