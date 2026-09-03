<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Auth\Exceptions\AuthenticationException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\CheckMembership;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }
            return redirect()->guest('/login');
        });
    })->create();