<?php

namespace App\Services;

use App\Models\Historial;
use App\Models\SesionEntrenamiento;
use App\Models\User;
use Carbon\Carbon;

class WorkoutSessionService
{
    /**
     * Inicia o registra una sesión de entrenamiento.
     * Es idempotente: si el UUID ya existe para el usuario, retorna la existente.
     */
    public function iniciar(User $user, array $data): SesionEntrenamiento
    {
        $startedAt = ! empty($data['started_at'])
            ? Carbon::parse($data['started_at'])
            : Carbon::now();

        return SesionEntrenamiento::firstOrCreate(
            [
                'uuid' => $data['uuid'],
            ],
            [
                'user_id' => $user->id,
                'rutina_nombre' => $data['rutina_nombre'],
                'dia' => $data['dia'],
                'started_at' => $startedAt,
                'series_totales' => $data['series_totales'] ?? 0,
            ]
        );
    }

    /**
     * Obtiene la sesión activa (sin finalizar) del usuario, si existe.
     */
    public function obtenerActiva(User $user): ?SesionEntrenamiento
    {
        return SesionEntrenamiento::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();
    }

    /**
     * Finaliza la sesión de entrenamiento calculando duración, volumen total y PRs.
     */
    public function finalizar(User $user, string $uuid, array $data = []): array
    {
        $sesion = SesionEntrenamiento::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $endedAt = ! empty($data['ended_at'])
            ? Carbon::parse($data['ended_at'])
            : Carbon::now();

        $startedAt = $sesion->started_at ?? Carbon::now();
        $duracion = max(0, $startedAt->diffInSeconds($endedAt));

        // Obtener series vinculadas a la sesión
        $sets = Historial::where('user_id', $user->id)
            ->where(function ($q) use ($uuid, $sesion) {
                $q->where('sesion_uuid', $uuid)
                    ->orWhere(function ($q2) use ($sesion) {
                        $q2->where('rutina_nombre', $sesion->rutina_nombre)
                            ->where('dia', $sesion->dia)
                            ->whereDate('fecha', $sesion->started_at->toDateString())
                            ->whereNull('sesion_uuid');
                    });
            })
            ->where('completado', true)
            ->get();

        // Si se encontraron sets sin sesion_uuid asignado, backfill rápido
        foreach ($sets as $set) {
            if (empty($set->sesion_uuid)) {
                $set->update(['sesion_uuid' => $uuid]);
            }
        }

        // Calcular volumen total (tonelaje acumulado en kg)
        $volumenTotal = 0;
        foreach ($sets as $set) {
            $peso = (float) ($set->peso ?? 0);
            $reps = (int) ($set->reps_realizadas ?? 0);
            if ($peso > 0 && $reps > 0) {
                $volumenTotal += ($peso * $reps);
            }
        }

        $seriesCompletadas = $sets->count();
        $seriesTotales = max($sesion->series_totales, $seriesCompletadas);

        // Detectar si hubo récords personales (PRs) en esta sesión
        $prsCount = $this->calcularPrsEnSesion($user, $sets, $sesion->started_at);

        $sesion->update([
            'ended_at' => $endedAt,
            'duracion_segundos' => $duracion,
            'volumen_total' => $volumenTotal,
            'series_completadas' => $seriesCompletadas,
            'series_totales' => $seriesTotales,
            'prs_superados' => $prsCount,
            'notas' => $data['notas'] ?? $sesion->notas,
        ]);

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return [
            'sesion' => $sesion->fresh(),
            'duracion_segundos' => $duracion,
            'volumen_total' => round($volumenTotal, 2),
            'series_completadas' => $seriesCompletadas,
            'series_totales' => $seriesTotales,
            'prs_superados' => $prsCount,
            'new_medals' => $newMedals,
        ];
    }

    /**
     * Descarta / cancela una sesión incompleta.
     */
    public function descartar(User $user, string $uuid): bool
    {
        $sesion = SesionEntrenamiento::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if (! $sesion) {
            return false;
        }

        return (bool) $sesion->delete();
    }

    /**
     * Calcula cuántos ejercicios alcanzaron un nuevo récord personal de peso en esta sesión.
     */
    protected function calcularPrsEnSesion(User $user, $sets, Carbon $sessionStartedAt): int
    {
        $ejerciciosEnSesion = $sets->groupBy('ejercicio_nombre');
        $prs = 0;

        foreach ($ejerciciosEnSesion as $nombreEjercicio => $setsDelEjercicio) {
            $maxPesoSesion = (float) $setsDelEjercicio->max('peso');
            if ($maxPesoSesion <= 0) {
                continue;
            }

            // Buscar el peso máximo histórico anterior a esta fecha/sesión
            $maxHistoricoPrevio = (float) Historial::where('user_id', $user->id)
                ->where('ejercicio_nombre', $nombreEjercicio)
                ->where('completado', true)
                ->whereDate('fecha', '<', $sessionStartedAt->toDateString())
                ->max('peso');

            if ($maxHistoricoPrevio > 0 && $maxPesoSesion > $maxHistoricoPrevio) {
                $prs++;
            }
        }

        return $prs;
    }
}
