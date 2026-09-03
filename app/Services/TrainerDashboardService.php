<?php

namespace App\Services;

use App\Models\Historial;
use App\Models\Progreso;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Carbon\Carbon;

/**
 * Servicio del dashboard de trainer.
 *
 * Encapsula las queries multi-paso del panel del trainer. La vista del alumno
 * (verAlumno) junta 5 queries en una sola respuesta; el dashboard del trainer
 * (index) hace 4 queries con anti-N+1 ya optimizadas (comentadas en el
 * controller original).
 */
class TrainerDashboardService
{
    /**
     * Dashboard del trainer: lista de alumnos, activos/inactivos, últimos
     * entrenamientos, alertas de inactividad 7+ días.
     *
     * @return array{
     *   alumnos_activos: int, alumnos_inactivos: int,
     *   alumnos_inactivos_7dias: array, ultimos_entrenamientos: array,
     *   total_alumnos: int, alumnos: array
     * }
     */
    public function buildDashboardIndex(int $trainerId): array
    {
        // Obtener todos los alumnos del trainer
        $alumnos = User::where('trainer_id', $trainerId)
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'email']);

        $alumnoIds = $alumnos->pluck('id')->toArray();

        if (empty($alumnoIds)) {
            return [
                'alumnos_activos' => 0,
                'alumnos_inactivos' => 0,
                'alumnos_inactivos_7dias' => [],
                'ultimos_entrenamientos' => [],
                'total_alumnos' => 0,
                'alumnos' => [],
            ];
        }

        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        // === FIX N+1: Una sola query para obtener el último entrenamiento por alumno ===
        $ultimosPorAlumno = Historial::whereIn('user_id', $alumnoIds)
            ->where('completado', true)
            ->select('user_id', 'fecha')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(fn($items) => $items->first());

        // === FIX N+1: Una sola query para alumnos activos esta semana ===
        $alumnosActivosIds = Historial::whereIn('user_id', $alumnoIds)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->distinct()
            ->pluck('user_id')
            ->flip()
            ->toArray(); // flip para usar isset() más rápido

        $alumnosActivos = count($alumnosActivosIds);
        $alumnosInactivos = count($alumnoIds) - $alumnosActivos;

        // Alertas de inactividad (más de 7 días sin entrenar) - ahora sin N+1
        $alumnosInactivos7Dias = [];
        $hoy = Carbon::now();
        foreach ($alumnos as $alumno) {
            $ultimo = $ultimosPorAlumno->get($alumno->id);

            if (!$ultimo || !$ultimo->fecha) {
                $diasSinEntrenar = 999; // nunca entrenó
            } else {
                $ultimoFecha = Carbon::parse($ultimo->fecha)->startOfDay();
                $diasSinEntrenar = abs($hoy->copy()->startOfDay()->diffInDays($ultimoFecha));
            }

            if ($diasSinEntrenar >= 7) {
                $alumnosInactivos7Dias[] = [
                    'id' => $alumno->id,
                    'name' => $alumno->name,
                    'nick' => $alumno->nick,
                    'dias_inactividad' => $diasSinEntrenar,
                    'ultimo_entrenamiento' => $ultimo?->fecha ? Carbon::parse($ultimo->fecha)->format('d/m/Y') : null,
                ];
            }
        }

        // Últimos entrenamientos completados
        $ultimosEntrenamientos = Historial::whereIn('user_id', $alumnoIds)
            ->where('completado', true)
            ->with('user:id,name,nick')
            ->select('user_id', 'rutina_nombre', 'dia', 'fecha')
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name ?? 'Usuario',
                    'user_nick' => $item->user->nick ?? '',
                    'rutina' => $item->rutina_nombre,
                    'dia' => $item->dia,
                    'fecha' => $item->fecha->format('d/m/Y'),
                ];
            });

        // === FIX N+1: Traer todas las user_rutinas de una ===
        $rutinasPorAlumno = UserRutina::whereIn('user_id', $alumnoIds)
            ->get()
            ->keyBy('user_id');

        $alumnosConInfo = $alumnos->map(function ($alumno) use ($alumnosActivosIds, $rutinasPorAlumno) {
            $rutina = $rutinasPorAlumno->get($alumno->id);

            return [
                'id' => $alumno->id,
                'name' => $alumno->name,
                'nick' => $alumno->nick,
                'email' => $alumno->email,
                'activo_semana' => isset($alumnosActivosIds[$alumno->id]),
                'rutina' => $rutina ? "{$rutina->nivel} {$rutina->modalidad}" : null,
                'dia_actual' => $rutina?->dia_actual,
            ];
        });

        return [
            'alumnos_activos' => $alumnosActivos,
            'alumnos_inactivos' => $alumnosInactivos,
            'alumnos_inactivos_7dias' => $alumnosInactivos7Dias,
            'ultimos_entrenamientos' => $ultimosEntrenamientos,
            'total_alumnos' => count($alumnoIds),
            'alumnos' => $alumnosConInfo,
        ];
    }

    /**
     * Detalle de un alumno: historial de pesos por ejercicio, tonelaje por
     * sesión, medidas corporales, rutina actual y últimos entrenamientos.
     *
     * @return array{ alumno: array, rutina_actual: ?UserRutina, historial_pesos: array, tonelaje_sesiones: array, medidas_corporales: array, historial_completo: array }
     */
    public function buildAlumnoDetalle(User $alumno): array
    {
        // Historial de pesos por ejercicio
        $historialPesos = Historial::where('user_id', $alumno->id)
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->orderBy('fecha', 'asc')
            ->get()
            ->groupBy('ejercicio_nombre')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'fecha' => $item->fecha->format('d/m/Y'),
                        'dia' => $item->dia,
                        'peso' => (float) $item->peso,
                        'reps' => $item->reps_realizadas,
                        'completado' => $item->completado,
                    ];
                });
            });

        // Tonelaje total por sesión (agrupado por fecha)
        $tonelajeSesiones = Historial::where('user_id', $alumno->id)
            ->whereNotNull('peso')
            ->where('completado', true)
            ->selectRaw('fecha, SUM(peso * reps_realizadas) as volumen_total, COUNT(*) as ejercicios_completados')
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                return [
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'volumen_total' => round($item->volumen_total, 1),
                    'ejercicios_completados' => $item->ejercicios_completados,
                ];
            });

        // Registro de medidas corporales
        $medidasCorporales = Progreso::where('user_id', $alumno->id)
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'peso' => $item->peso,
                    'altura' => $item->altura,
                    'cuello' => $item->cuello,
                    'hombros' => $item->hombros,
                    'pecho' => $item->pecho,
                    'brazos' => $item->brazos,
                    'cintura' => $item->cintura,
                    'cadera' => $item->cadera,
                    'muslos' => $item->muslos,
                    'pantorrillas' => $item->pantorrillas,
                ];
            });

        // Rutina actual del alumno
        $rutinaActual = UserRutina::where('user_id', $alumno->id)->first();

        // Historial de entradas (para poder dejar comentarios)
        $historialCompleto = Historial::where('user_id', $alumno->id)
            ->with('trainer:id,name')
            ->orderBy('fecha', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'rutina_nombre' => $item->rutina_nombre,
                    'dia' => $item->dia,
                    'ejercicio_nombre' => $item->ejercicio_nombre,
                    'series_numero' => $item->series_numero,
                    'peso' => $item->peso,
                    'reps_realizadas' => $item->reps_realizadas,
                    'completado' => $item->completado,
                    'comentario_trainer' => $item->comentario_trainer,
                    'trainer_nombre' => $item->trainer?->name,
                ];
            });

        return [
            'alumno' => [
                'id' => $alumno->id,
                'name' => $alumno->name,
                'nick' => $alumno->nick,
                'email' => $alumno->email,
            ],
            'rutina_actual' => $rutinaActual,
            'historial_pesos' => $historialPesos,
            'tonelaje_sesiones' => $tonelajeSesiones,
            'medidas_corporales' => $medidasCorporales,
            'historial_completo' => $historialCompleto,
        ];
    }

    /**
     * Duplica una rutina (todos sus ejercicios) con un nuevo nombre (nivel).
     * El trainer queda registrado como `created_by` en los nuevos registros.
     */
    public function duplicarRutina(int $trainerId, int $rutinaId, string $nombreNuevo): string
    {
        $rutinaOriginal = Rutina::find($rutinaId);

        $rutinaOriginal->each(function ($ejercicio) use ($trainerId, $nombreNuevo) {
            Rutina::create([
                'nivel' => $nombreNuevo,
                'modalidad' => $ejercicio->modalidad,
                'dia' => $ejercicio->dia,
                'created_by' => $trainerId,
                'series' => $ejercicio->series,
                'reps_min' => $ejercicio->reps_min,
                'reps_max' => $ejercicio->reps_max,
                'descanso_min' => $ejercicio->descanso_min,
                'ejercicio_nombre' => $ejercicio->ejercicio_nombre,
                'orden' => $ejercicio->orden,
            ]);
        });

        return $nombreNuevo;
    }
}
