<?php

use App\Http\Controllers\AdminUserApiController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\TrainerAlumnoController;
use App\Http\Controllers\UserRutinaController;
use Illuminate\Support\Facades\Route;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::middleware(['web', 'auth', 'role:administrador'])->group(function () {
    Route::post('/ejercicios', [EjercicioController::class, 'store']);
    Route::delete('/ejercicios/{id}', [EjercicioController::class, 'destroy']);
});

Route::get('/rutinas', [RutinaController::class, 'index']);
Route::middleware(['web', 'auth', 'role:comun,trainer,administrador'])->group(function () {
    Route::post('/rutinas', [RutinaController::class, 'store']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/user-rutina', [UserRutinaController::class, 'store']);
    Route::get('/user-rutina', [UserRutinaController::class, 'show']);
    Route::post('/user-rutina/dia', [UserRutinaController::class, 'updateDia']);

    Route::get('/historial', [HistorialController::class, 'index']);
    Route::post('/historial/guardar', [HistorialController::class, 'guardar']);
    Route::post('/historial/completar', [HistorialController::class, 'marcarCompletado']);
    Route::get('/historial/progreso', [HistorialController::class, 'obtenerProgreso']);
    Route::post('/historial/finalizar-rutina', [HistorialController::class, 'finalizarRutina']);

    Route::middleware('role:trainer,administrador')->group(function () {
        Route::get('/trainer/alumnos', [TrainerAlumnoController::class, 'index']);
        Route::post('/trainer/alumnos/{alumno}/rutina', [TrainerAlumnoController::class, 'asignarRutina']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/users', [AdminUserApiController::class, 'index']);
        Route::put('/admin/users/{id}', [AdminUserApiController::class, 'update']);
        Route::patch('/admin/users/{id}/toggle-suspend', [AdminUserApiController::class, 'toggleSuspend']);
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user-info', function () {
        return response()->json([
            'role' => auth()->user()->normalizedRole(),
        ]);
    });
});