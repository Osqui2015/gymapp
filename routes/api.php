<?php

use App\Http\Controllers\AdminUserApiController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ProgresoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\TrainerAlumnoController;
use App\Http\Controllers\TrainerDashboardController;
use App\Http\Controllers\UserRutinaController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\MedallaController;
use App\Http\Controllers\DiarioNutricionController;
use App\Http\Controllers\EjercicioClaveController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\AdminStatsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AdminImportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::get('/ejercicios/grupos-musculares', [EjercicioController::class, 'gruposMusculares']);
Route::get('/ejercicios/equipamientos', [EjercicioController::class, 'equipamientos']);
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

    // Metas & Logros (Gamificación)
    Route::get('/metas', [MetaController::class, 'index']);
    Route::post('/metas', [MetaController::class, 'store']);
    Route::post('/metas/{id}/completar', [MetaController::class, 'toggleCompletada']);
    Route::delete('/metas/{id}', [MetaController::class, 'destroy']);
    Route::get('/logros', [MedallaController::class, 'index']);

    // Rutinas Compartidas
    Route::post('/rutinas/compartir', [RutinaController::class, 'compartir']);
    Route::post('/rutinas/importar', [RutinaController::class, 'importar']);

    // Diario de Nutricion (Oculto)
    Route::get('/nutricion', [DiarioNutricionController::class, 'show']);
    Route::post('/nutricion', [DiarioNutricionController::class, 'update']);
    Route::post('/nutricion/agua', [DiarioNutricionController::class, 'updateAgua']);

    // Ejercicios Clave
    Route::get('/ejercicios-clave', [EjercicioClaveController::class, 'index']);
    Route::post('/ejercicios-clave', [EjercicioClaveController::class, 'store']);
    Route::delete('/ejercicios-clave/{id}', [EjercicioClaveController::class, 'destroy']);

    Route::middleware('role:trainer,administrador')->group(function () {
        Route::get('/trainer/alumnos', [TrainerAlumnoController::class, 'index']);
        Route::post('/trainer/alumnos/{alumno}/rutina', [TrainerAlumnoController::class, 'asignarRutina']);
    });

    // Rutas del Dashboard del Trainer
    Route::middleware('role:trainer,administrador')->group(function () {
        Route::get('/trainer/dashboard', [TrainerDashboardController::class, 'index']);
        Route::get('/trainer/alumno/{alumno}', [TrainerDashboardController::class, 'verAlumno']);
        Route::post('/trainer/alumno/{alumno}/comentario', [TrainerDashboardController::class, 'agregarComentario']);
        Route::post('/trainer/duplicar-rutina', [TrainerDashboardController::class, 'duplicarRutina']);
        Route::get('/trainer/ejercicios-privados', [TrainerDashboardController::class, 'ejerciciosPrivados']);
        Route::post('/trainer/ejercicios-privados', [TrainerDashboardController::class, 'crearEjercicioPrivado']);
        Route::delete('/trainer/ejercicios-privados/{id}', [TrainerDashboardController::class, 'eliminarEjercicioPrivado']);
        Route::get('/trainer/mis-rutinas-complete', [TrainerDashboardController::class, 'obtenerTodasRutinas']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/users', [AdminUserApiController::class, 'index']);
        Route::put('/admin/users/{id}', [AdminUserApiController::class, 'update']);
        Route::patch('/admin/users/{id}/toggle-suspend', [AdminUserApiController::class, 'toggleSuspend']);
        Route::delete('/admin/users/{id}', [AdminUserApiController::class, 'destroy']);
        Route::get('/admin/trainers-alumnos', [AdminUserApiController::class, 'getTrainersAndAlumnos']);
        Route::post('/admin/trainers/{trainerId}/assign-alumnos', [AdminUserApiController::class, 'assignAlumnosToTrainer']);
    });

    // Rutas de Membresías (Administrador)
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/membresias', [MembresiaController::class, 'index']);
        Route::post('/admin/membresias', [MembresiaController::class, 'store']);
        Route::put('/admin/membresias/{membresia}', [MembresiaController::class, 'update']);
        Route::post('/admin/membresias/{membresia}/renew', [MembresiaController::class, 'renew']);
        Route::get('/admin/usuarios-sin-membresia', [MembresiaController::class, 'usuariosSinMembresia']);
        Route::get('/admin/membresias-por-vencer', [MembresiaController::class, 'porVencer']);
    });

    // Rutas de Estadísticas (Administrador)
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/estadisticas', [AdminStatsController::class, 'estadisticas']);
        Route::get('/admin/miembros-activos', [AdminStatsController::class, 'miembrosActivos']);
    });

    // Rutas de Audit Logs (Administrador y Coordinador)
    Route::middleware('role:administrador,coordinador')->group(function () {
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/admin/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        Route::get('/admin/audit-logs/model/{modelType}/{modelId}', [AuditLogController::class, 'getModelHistory']);
    });

    // Rutas de Import/Export (Administrador)
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/export/users', [AdminImportExportController::class, 'exportUsers']);
        Route::get('/admin/export/ejercicios', [AdminImportExportController::class, 'exportEjercicios']);
        Route::post('/admin/import/users', [AdminImportExportController::class, 'importUsers']);
        Route::post('/admin/import/ejercicios', [AdminImportExportController::class, 'importEjercicios']);
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user-info', function () {
        $user = auth()->user();
        return response()->json([
            'role' => $user->normalizedRole(),
            'trainer_id' => $user->trainer_id,
            'has_trainer' => !empty($user->trainer_id),
        ]);
    });
});