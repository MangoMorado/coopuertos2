<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConductorController;

use App\Http\Controllers\DashboardController;
// Ruta pública principal
Route::get('/', function () {
    return view('welcome');
});

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
});

// Ruta pública para mostrar un conductor específico por UUID
Route::get('/conductor/{uuid}', [ConductorController::class, 'show'])->name('conductor.public');

// Rutas de autenticación generadas por Laravel Breeze/Jetstream
require __DIR__.'/auth.php';
