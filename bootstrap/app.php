<?php

use App\Http\Middleware\CheckMembership;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Models\Membresia;
use Illuminate\Auth\Exceptions\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // NOTA: el health nativo de Laravel (health: '/up') esta DESACTIVADO a
        // proposito. Ese endpoint solo verifica que el framework arranca y
        // devuelve HTML con un CDN de Tailwind: si MySQL se cae sigue
        // respondiendo 200 y el monitor del hosting dice "todo bien" mientras
        // la app esta muerta.
        // Las rutas /up y /up/live se registran en routes/web.php con
        // HealthController, que hace los checks reales (DB, storage, disco,
        // cache, config) y devuelve JSON.
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'membership' => CheckMembership::class,
        ]);

        // Nivel 5: cabeceras de seguridad HTTP en TODAS las respuestas (web y api).
        // Se aplica en ambos stacks para que ningún endpoint devuelva headers
        // inseguros por accidente.
        $middleware->append(SecurityHeadersMiddleware::class);

        // Rate limiting para las 148 rutas de routes/api.php.
        // Laravel 12 solo agrega `throttle:api` al grupo `api` si existe un
        // limiter con ese nombre (AppServiceProvider::registerRateLimiters).
        // Sin esto la API no tenía NINGÚN límite de peticiones.
        $middleware->api(append: ['throttle:api']);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Recordatorios automáticos: 1 vez por día a las 9am hora local.
        // withoutOverlapping previene que se acumulen runs si tardan.
        $schedule->command('reminders:send')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Detección de plateaus: corre 1 vez al día, ~10am. Necesita que
        // reminders:send ya haya corrido (sino los plateaus llegarían antes
        // que los recordatorios de inactividad).
        $schedule->command('plateaus:detect')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Nivel 5: backup diario de la base a las 02:00. Conserva los últimos
        // 7 días por default (configurable con --retention-days).
        $schedule->command('db:backup --retention-days=7')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->name('db:backup-diario');

        // Nivel 5: actualización de estados de membresía todos los días
        // a las 00:05. Detecta vencimientos y marca el estado correctamente
        // para que CheckMembership pueda evaluar el período de gracia.
        $schedule->call(function () {
            Membresia::actualizarEstados();
        })->dailyAt('00:05')->name('membresias:actualizar-estados');

        // Purga de audit logs: la tabla es append-only y crece sin límite.
        // Corre a las 03:30 (después del backup de las 02:00, así el backup
        // diario todavía incluye los logs del día anterior).
        //
        // Retención de 365 días. Los logs de SEGURIDAD (login_failed,
        // role_changed, etc.) NUNCA se purgan: el comando los excluye.
        $schedule->command('audit-logs:purge --days=365 --prune --force')
            ->dailyAt('03:30')
            ->withoutOverlapping()
            ->onOneServer()
            ->name('audit-logs:purga');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return redirect()->guest('/login');
        });
    })->create();
