<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas del health check.
 *
 * Este archivo se registra FUERA del grupo 'web' (ver bootstrap/app.php,
 * closure `using:`), a proposito:
 *
 *   - El grupo 'web' aplica StartSession, que hace que el health check
 *     dependa de la tabla `sessions`. Si la base de datos se cae, /up daria
 *     500 con un error de sesion — justo cuando mas lo hace falta el
 *     diagnostico.
 *   - Un monitor externo (UptimeRobot, el health check de Hostinger, un
 *     cron) consulta por HTTP: no manda cookies ni token CSRF.
 *   - Sin sesion, el health check responde en milisegundos y no genera
 *     filas en `sessions` (que es una tabla append-only).
 *
 * La ruta /up es publica a proposito. Por eso el controller NO expone datos
 * internos del servidor: ni nombre de base, ni rutas absolutas, ni versiones.
 * El detalle completo se obtiene por SSH con `php artisan health:check`.
 */

// Readiness + liveness. Devuelve JSON con el detalle de los checks.
Route::get('/up', HealthController::class)->name('health.up');

// Liveness probe: no toca la base de datos. Para que un orquestador NO
// reinicie la app cuando lo que se cayo es MySQL (reiniciar no arregla una
// base caída y genera un loop de reinicios).
Route::get('/up/live', [HealthController::class, 'live'])->name('health.live');
