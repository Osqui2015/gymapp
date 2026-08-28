<?php

use App\Http\Controllers\AdminUserApiController;
use App\Http\Controllers\BodyMapController;
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
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\TrainerCommentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/ejercicios', [EjercicioController::class, 'index']);
Route::get('/ejercicios/grupos-musculares', [EjercicioController::class, 'gruposMusculares']);
Route::get('/ejercicios/equipamientos', [EjercicioController::class, 'equipamientos']);
Route::get('/musculos', [EjercicioController::class, 'musculos']);

// Favoritos: requiere auth (devuelve is_favorite del user actual)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/ejercicios/{id}/favorite', [EjercicioController::class, 'toggleFavorite']);
    Route::post('/ejercicios/{id}/quick-log', [EjercicioController::class, 'quickLog']);
});
Route::middleware(['web', 'auth', 'role:administrador'])->group(function () {
    Route::post('/ejercicios', [EjercicioController::class, 'store']);
    Route::delete('/ejercicios/{id}', [EjercicioController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'role:comun,trainer,administrador'])->group(function () {
    Route::post('/rutinas', [RutinaController::class, 'store']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/rutinas', [RutinaController::class, 'index']);
    Route::get('/rutinas/sugeridas', [RutinaController::class, 'sugeridas']);
    Route::delete('/rutinas', [RutinaController::class, 'destroy']);
    Route::post('/user-rutina', [UserRutinaController::class, 'store']);
    Route::get('/user-rutina', [UserRutinaController::class, 'show']);
    Route::post('/user-rutina/dia', [UserRutinaController::class, 'updateDia']);
    Route::post('/user-rutina/reschedule', [UserRutinaController::class, 'reschedule']);
    Route::get('/user-rutina/available-days', [UserRutinaController::class, 'availableDays']);

    Route::middleware('role:administrador,trainer')->group(function () {
        Route::get('/trainer/mis-rutinas', [UserRutinaController::class, 'misRutinas']);
        Route::get('/trainer/mis-alumnos', [UserRutinaController::class, 'misAlumnos']);
        Route::post('/trainer/asignar-rutina', [UserRutinaController::class, 'asignarRutina']);
    });

    Route::get('/historial', [HistorialController::class, 'index']);
    Route::post('/historial/guardar', [HistorialController::class, 'guardar']);
    Route::post('/historial/completar', [HistorialController::class, 'marcarCompletado']);
    Route::get('/historial/progreso', [HistorialController::class, 'obtenerProgreso']);
    Route::post('/historial/finalizar-rutina', [HistorialController::class, 'finalizarRutina']);
    Route::get('/historial/calendar', [HistorialController::class, 'calendar']);
    Route::get('/historial/week-summary', [HistorialController::class, 'weekSummary']);

    Route::get('/progreso', [ProgresoController::class, 'obtener']);
    Route::post('/progreso', [ProgresoController::class, 'guardar']);
    Route::get('/progreso/detalle', [ProgresoController::class, 'obtenerDetalle']);
    Route::get('/progreso/weight-chart', [ProgresoController::class, 'weightChart']);
    Route::patch('/progreso/goal', [ProgresoController::class, 'updateGoal']);

    // Body map (mapa corporal de musculatura) — usado por BodyMap.vue
    Route::get('/body-map/data', [BodyMapController::class, 'index']);
    Route::get('/body-map/muscle-recency', [BodyMapController::class, 'muscleRecency']);
    Route::get('/body-map/muscle/{slug}/exercises', [BodyMapController::class, 'ejerciciosPorMusculo']);

    // Stats (racha + heatmap de actividad) — estilo openGym
    Route::get('/stats/resumen', [StatsController::class, 'resumen']);
    Route::get('/stats/heatmap', [StatsController::class, 'heatmap']);
    Route::get('/stats/esfuerzo', [StatsController::class, 'esfuerzo']);
    Route::get('/stats/estimated-1rm', [StatsController::class, 'estimated1rm']);
    Route::get('/dashboard/today', [StatsController::class, 'dashboardToday']);

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

    Route::middleware('role:administrador,trainer')->group(function () {
        Route::get('/trainer/alumnos', [TrainerAlumnoController::class, 'index']);
    });

    // Rutas del Dashboard del Trainer
    Route::middleware('role:administrador,trainer')->group(function () {
        Route::get('/trainer/dashboard', [TrainerDashboardController::class, 'index']);
        Route::get('/trainer/alumno/{alumno}', [TrainerDashboardController::class, 'verAlumno']);
        Route::post('/trainer/alumno/{alumno}/comentario', [TrainerDashboardController::class, 'agregarComentario']);
        Route::get('/trainer/alumnos/{alumno}/timeline', [\App\Http\Controllers\TrainerTimelineController::class, 'show']);
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
        Route::post('/admin/estadisticas/invalidate', [AdminStatsController::class, 'invalidateCache']);
        Route::get('/admin/miembros-activos', [AdminStatsController::class, 'miembrosActivos']);
        Route::get('/admin/reportes', [AdminStatsController::class, 'reportes']);
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

    // Búsqueda global
    Route::get('/search', [SearchController::class, 'index']);

    // === Mejora 8.7: Web Push subscriptions ===
    Route::post('/push/subscription', [PushSubscriptionController::class, 'store']);
    Route::delete('/push/subscription', [PushSubscriptionController::class, 'destroy']);

    // === Mejora 8.1: Comentarios realtime trainer↔alumno ===
    Route::get('/trainer-comments', [TrainerCommentController::class, 'index']);
    Route::post('/trainer-comments', [TrainerCommentController::class, 'store']);
    Route::post('/trainer-comments/{comment}/read', [TrainerCommentController::class, 'markRead']);

    // === Notificaciones in-app persistentes ===
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // === Mensajería 1-a-1 ===
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/messages/with/{user}', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/messages/{message}/read', [MessageController::class, 'markRead']);
    Route::post('/messages/with/{user}/read-all', [MessageController::class, 'markAllRead']);
});

// VAPID public key (pública)
Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'publicKey']);

// Vista pública de rutina compartida (sin auth)
Route::get('/rutinas/publica/{token}', [RutinaController::class, 'verPublica']);

// Stats de comunidad (sin auth, cacheadas)
Route::get('/comunidad/stats', [SearchController::class, 'comunidadStats']);

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