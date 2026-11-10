<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de ejecución de una tarea del scheduler (Fase 0.3).
 *
 * Heartbeat: cada tarea del scheduler escribe una fila al terminar, para
 * que el panel pueda saber si el cron del server está corriendo.
 *
 * @see \App\Services\SchedulerRunService
 */
class SchedulerRun extends Model
{
    protected $table = 'scheduler_runs';

    protected $fillable = [
        'task',
        'status',
        'duration_ms',
        'error',
        'params',
        'started_at',
        'finished_at',
        // `created_at` se puede setear a mano para poder simular
        // ejecuciones viejas en los tests de purga (la purga filtra por
        // `created_at`, no por `finished_at`).
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'duration_ms' => 'float',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_OK = 'ok';

    public const STATUS_FAIL = 'fail';

    /**
     * Última ejecución de cada tarea, indexada por nombre.
     *
     * @return array<string, self>
     */
    public static function ultimasPorTarea(): array
    {
        $filas = self::orderByDesc('id')->get();

        $ultimas = [];

        foreach ($filas as $fila) {
            if (! isset($ultimas[$fila->task])) {
                $ultimas[$fila->task] = $fila;
            }
        }

        return $ultimas;
    }
}
