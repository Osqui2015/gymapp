<?php

use App\Http\Controllers\AdminUserApiController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ProgresoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\TrainerAlumnoController;
use App\Http\Controllers\UserRutinaController;
use Illuminate\Support\Facades\Route;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::get('/ejercicios/grupos-musculares', [EjercicioController::class, 'gruposMusculares']);
Route::middleware(['web', 'auth', 'role:administrador'])->group(function () {
    Route::post('/ejercicios', [EjercicioController::class, 'store']);
    Route::delete('/ejercicios/{id}', [EjercicioController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'role:comun,trainer,administrador'])->group(function () {
    Route::post('/rutinas', [RutinaController::class, 'store']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/rutinas', [RutinaController::class, 'index']);
    Route::delete('/rutinas', [RutinaController::class, 'destroy']);
    Route::post('/user-rutina', [UserRutinaController::class, 'store']);
    Route::get('/user-rutina', [UserRutinaController::class, 'show']);
    Route::post('/user-rutina/dia', [UserRutinaController::class, 'updateDia']);

    Route::middleware('role:trainer,administrador')->group(function () {
        Route::get('/trainer/mis-rutinas', [UserRutinaController::class, 'misRutinas']);
        Route::get('/trainer/mis-alumnos', [UserRutinaController::class, 'misAlumnos']);
        Route::post('/trainer/asignar-rutina', [UserRutinaController::class, 'asignarRutina']);
    });

    Route::get('/historial', [HistorialController::class, 'index']);
    Route::post('/historial/guardar', [HistorialController::class, 'guardar']);
    Route::post('/historial/completar', [HistorialController::class, 'marcarCompletado']);
    Route::get('/historial/progreso', [HistorialController::class, 'obtenerProgreso']);
    Route::post('/historial/finalizar-rutina', [HistorialController::class, 'finalizarRutina']);

    Route::get('/progreso', [ProgresoController::class, 'obtener']);
    Route::post('/progreso', [ProgresoController::class, 'guardar']);
    Route::get('/progreso/detalle', [ProgresoController::class, 'obtenerDetalle']);

    Route::middleware('role:trainer,administrador')->group(function () {
        Route::get('/trainer/alumnos', [TrainerAlumnoController::class, 'index']);
        Route::post('/trainer/alumnos/{alumno}/rutina', [TrainerAlumnoController::class, 'asignarRutina']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/users', [AdminUserApiController::class, 'index']);
        Route::put('/admin/users/{id}', [AdminUserApiController::class, 'update']);
        Route::patch('/admin/users/{id}/toggle-suspend', [AdminUserApiController::class, 'toggleSuspend']);
        Route::delete('/admin/users/{id}', [AdminUserApiController::class, 'destroy']);
        Route::get('/admin/trainers-alumnos', [AdminUserApiController::class, 'getTrainersAndAlumnos']);
        Route::post('/admin/trainers/{trainerId}/assign-alumnos', [AdminUserApiController::class, 'assignAlumnosToTrainer']);
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user-info', function () {
        return response()->json([
            'role' => auth()->user()->normalizedRole(),
        ]);
    });
});