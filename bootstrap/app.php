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
        health: '/up',
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return redirect()->guest('/login');
        });
    })->create();
