<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConductorController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PropietarioController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Endpoint público de salud (sin autenticación)
    Route::get('/health', [HealthController::class, 'index'])->middleware('throttle:60,1');
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
        });
    });

    // Endpoint público de conductor por UUID (sin autenticación)
    Route::get('/conductores/{uuid}/public', [ConductorController::class, 'publicShow'])
        ->middleware('throttle:60,1');

    // Rutas protegidas por autenticación
    Route::middleware('auth:sanctum')->group(function () {
        // Conductores
        Route::prefix('conductores')->group(function () {
            Route::get('/', [ConductorController::class, 'index'])->middleware('permission:ver conductores');
            Route::post('/', [ConductorController::class, 'store'])->middleware('permission:crear conductores');
            Route::get('/search', [ConductorController::class, 'search'])->middleware('permission:ver conductores');
            Route::get('/{conductor}', [ConductorController::class, 'show'])->middleware('permission:ver conductores');
            Route::put('/{conductor}', [ConductorController::class, 'update'])->middleware('permission:editar conductores');
            Route::delete('/{conductor}', [ConductorController::class, 'destroy'])->middleware('permission:eliminar conductores');
        });

        // Vehículos
        Route::prefix('vehiculos')->group(function () {
            Route::get('/', [VehicleController::class, 'index'])->middleware('throttle:120,1');
            Route::post('/', [VehicleController::class, 'store'])->middleware('throttle:120,1');
            Route::get('/search', [VehicleController::class, 'search'])->middleware('throttle:120,1');
            Route::get('/{vehicle}', [VehicleController::class, 'show'])->middleware('throttle:120,1');
            Route::put('/{vehicle}', [VehicleController::class, 'update'])->middleware('throttle:120,1');
            Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->middleware('throttle:120,1');
        });

        // Propietarios
        Route::prefix('propietarios')->group(function () {
            Route::get('/', [PropietarioController::class, 'index'])->middleware('throttle:120,1');
            Route::post('/', [PropietarioController::class, 'store'])->middleware('throttle:120,1');
            Route::get('/search', [PropietarioController::class, 'search'])->middleware('throttle:120,1');
            Route::get('/{propietario}', [PropietarioController::class, 'show'])->middleware('throttle:120,1');
            Route::put('/{propietario}', [PropietarioController::class, 'update'])->middleware('throttle:120,1');
            Route::delete('/{propietario}', [PropietarioController::class, 'destroy'])->middleware('throttle:120,1');
        });

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
            ->middleware('throttle:120,1');
    });
});
