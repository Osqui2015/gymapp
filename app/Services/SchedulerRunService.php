<?php

namespace App\Services;

use App\Models\SchedulerRun;
use Illuminate\Support\Facades\Log;

/**
 * Heartbeat del scheduler (Fase 0.3 / plan §12.6).
 *
 * PROBLEMA QUE RESUELVE
 * ----------------------
 * `bootstrap/app.php` define 5 tareas diarias, pero NO hay forma de saber
 * si el cron del server (Hostinger) está configurado y corriendo. Si no
 * lo está, no se actualizan los estados de membresía, no se manda ningún
 * recordatorio, no se detectan mesetas y — lo más grave — NO SE HACE EL
 * BACKUP DIARIO DE LA BASE.
 *
 * El plan lo trata como "producción sin cron" en la tabla de riesgos, con
 * la mitigación de "mantener alerta visible en el Hub si la última
 * ejecución del scheduler tiene más de 24 horas de antigüedad".
 *
 * CÓMO SE USA
 * -----------
 * Cada tarea del scheduler se envuelve en `run()`, que registra el
 * resultado (ok/fail + duración + error) y deja que la excepción siga
 * propagating: una tarea que falla tiene que seguir fallando (y verse en
 * el log), no tragarse el error.
 *
 * En `bootstrap/app.php`:
 *
 *     $schedule->call(fn () => app(SchedulerRunService::class)->run(
 *         'reminders:send',
 *         fn () => Artisan::call('reminders:send')
 *     ))->dailyAt('09:00');
 */
class SchedulerRunService
{
    /**
     * Retención del historial de ejecuciones (días).
     * Las filas viejas no aportan: lo que importa es la última corrida
     * y la tendencia reciente.
     */
    public const RETENCION_DIAS = 30;

    /**
     * Ejecuta una tarea registrando su resultado en `scheduler_runs`.
     *
     * Atajo estático para usar desde `bootstrap/app.php`, donde el
     * contenedor todavía no está handy dentro de los closures del
     * scheduler.
     *
     * @param  callable  $callback
     */
    public static function run(string $task, callable $callback, ?string $params = null): mixed
    {
        return app(self::class)->registrarEjecucion($task, $callback, $params);
    }

    /**
     * Ejecuta una tarea registrando su resultado en `scheduler_runs`.
     *
     * @param  callable  $callback  La tarea a ejecutar.
     * @param  string  $params  Parámetros con los que corrió (para auditoría).
     *
     * @return mixed El valor devuelto por $callback.
     *
     * @throws \Throwable Re-lanza la excepción original después de registrar el fallo.
     */
    public function registrarEjecucion(string $task, callable $callback, ?string $params = null): mixed
    {
        $inicio = microtime(true);
        $startedAt = now();

        try {
            $resultado = $callback();

            $this->registrar(
                $task,
                SchedulerRun::STATUS_OK,
                (microtime(true) - $inicio) * 1000,
                null,
                $params,
                $startedAt
            );

            return $resultado;
        } catch (\Throwable $e) {
            $this->registrar(
                $task,
                SchedulerRun::STATUS_FAIL,
                (microtime(true) - $inicio) * 1000,
                $this->describirError($e),
                $params,
                $startedAt
            );

            // La excepción sigue hacia arriba: la tarea tiene que verse
            // como fallida en el log del server, no quedar silenciosa.
            throw $e;
        }
    }

    /**
     * Registra una ejecución.
     */
    public function registrar(
        string $task,
        string $status,
        float $durationMs,
        ?string $error = null,
        ?string $params = null,
        ?\DateTimeInterface $startedAt = null
    ): SchedulerRun {
        try {
            return SchedulerRun::create([
                'task' => $task,
                'status' => $status,
                'duration_ms' => round($durationMs, 2),
                'error' => $error,
                'params' => $params,
                'started_at' => $startedAt ?? now(),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Si ni siquiera podemos registrar el fallo (DB caída, tabla
            // sin migrar), no queremos que la tarea quede sin ejecutar.
            Log::error('[scheduler] no se pudo registrar el heartbeat', [
                'task' => $task,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Estado de salud del scheduler para el panel.
     *
     * Devuelve, por tarea: la última ejecución, su estado, y la cantidad
     * de horas desde que corrió. El panel usa esto para la tira de
     * "estado del sistema" (plan §12.6 / Fase 1.5).
     *
     * @param  array<int, string>  $tareas
     * @return array<int, array{task: string, status: string, ultima_ejecucion: ?string, horas: ?float, ok: bool, detalle: string}>
     */
    public function estado(array $tareas): array
    {
        $ultimas = SchedulerRun::ultimasPorTarea();

        $estado = [];

        foreach ($tareas as $tarea) {
            $run = $ultimas[$tarea] ?? null;

            if (! $run) {
                $estado[] = [
                    'task' => $tarea,
                    'status' => 'nunca',
                    'ultima_ejecucion' => null,
                    'horas' => null,
                    'ok' => false,
                    'detalle' => 'Nunca se ejecutó. Verificá el cron del servidor.',
                ];

                continue;
            }

            $horas = $run->finished_at
                ? round(abs(now()->diffInHours($run->finished_at, false)), 2)
                : null;

            // Una tarea diaria está "ok" si corrió en las últimas 26 horas
            // (26 = 24 de margen + 2 de holgura por el arranque del cron).
            $ok = $run->status === SchedulerRun::STATUS_OK
                && $horas !== null
                && $horas <= 26;

            $estado[] = [
                'task' => $tarea,
                'status' => $run->status,
                'ultima_ejecucion' => $run->finished_at?->toIso8601String(),
                'horas' => $horas,
                'ok' => $ok,
                'detalle' => $ok
                    ? 'OK'
                    : ($run->status === SchedulerRun::STATUS_FAIL
                        ? 'Última ejecución falló: '.$this->resumir($run->error)
                        : "Sin correr hace {$horas} h"),
            ];
        }

        return $estado;
    }

    /**
     * Purga las ejecuciones viejas.
     *
     * @return int filas eliminadas
     */
    public function purgar(int $dias = self::RETENCION_DIAS): int
    {
        return SchedulerRun::where('created_at', '<', now()->subDays($dias))->delete();
    }

    /**
     * Descripción corta del error, sin volcar la traza entera.
     */
    private function describirError(\Throwable $e): string
    {
        return get_class($e).': '.$e->getMessage();
    }

    private function resumir(?string $texto, int $max = 200): string
    {
        if ($texto === null || $texto === '') {
            return 'sin detalle';
        }

        return mb_strlen($texto) > $max
            ? mb_substr($texto, 0, $max).'…'
            : $texto;
    }
}
