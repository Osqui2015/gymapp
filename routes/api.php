<?php

use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\UserRutinaController;
use Illuminate\Support\Facades\Route;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::post('/ejercicios', [EjercicioController::class, 'store']);
Route::delete('/ejercicios/{id}', [EjercicioController::class, 'destroy']);

Route::get('/rutinas', [RutinaController::class, 'index']);
Route::post('/rutinas', [RutinaController::class, 'store']);

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/user-rutina', [UserRutinaController::class, 'store']);
    Route::get('/user-rutina', [UserRutinaController::class, 'show']);
    Route::post('/user-rutina/dia', [UserRutinaController::class, 'updateDia']);

    Route::get('/historial', [HistorialController::class, 'index']);
    Route::post('/historial/guardar', [HistorialController::class, 'guardar']);
    Route::post('/historial/completar', [HistorialController::class, 'marcarCompletado']);
    Route::get('/historial/progreso', [HistorialController::class, 'obtenerProgreso']);
    Route::post('/historial/finalizar-rutina', [HistorialController::class, 'finalizarRutina']);
});