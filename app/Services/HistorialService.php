<?php

namespace App\Services;

use App\Models\Historial;
use App\Models\Rutina;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de historial: progresos, finalización de rutinas, calendar y week summary.
 *
 * Encapsula queries y cálculos que antes vivían en HistorialController.
 * El controller queda como HTTP handler thin (validación, autorización, response).
 *
 * Reglas de progreso (D1):
 *   - nivel y modalidad vienen de la relación `rutina`, NO de columnas denormalizadas
 *   - "día actual" = primer día de la rutina que NO esté completado
 *   - si todos los días están completados, vuelve a Día 1 (ciclo)
 */
class HistorialService
{
    /**
     * Resuelve el día actual del usuario dentro de su rutina seleccionada.
     *
     * @return array{ dia_actual: string }
     */
    public function obtenerProgreso(int $userId, string $rutinaNombre): array
    {
        $ultimo = Historial::where('user_id', $userId)
            ->where('rutina_nombre', $rutinaNombre)
            ->where('completado', true)
            ->orderBy('fecha', 'desc')
            ->first();

        if (!$ultimo) {
            return ['dia_actual' => 'Día 1'];
        }

        $diasCompletados = Historial::where('user_id', $userId)
            ->where('rutina_nombre', $rutinaNombre)
            ->where('completado', true)
            ->selectRaw('DISTINCT dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        // D1: nivel/modalidad vienen de la relación `rutina`, no de columnas denormalizadas.
        $userRutina = \App\Models\User::find($userId)?->rutinaSeleccionada()->with('rutina')->first();
        $nivel = $userRutina?->rutina?->nivel ?? Rutina::query()->value('nivel');
        $modalidad = $userRutina?->rutina?->modalidad ?? Rutina::query()->value('modalidad');

        if (! $nivel || ! $modalidad) {
            return ['dia_actual' => 'Día 1'];
        }

        $todosLosDias = Rutina::where('nivel', $nivel)
            ->where('modalidad', $modalidad)
            ->selectRaw('DISTINCT dia')
            ->orderBy('dia')
            ->get()
            ->pluck('dia')
            ->toArray();

        $diaActual = $todosLosDias[0] ?? 'Día 1';
        foreach ($todosLosDias as $index => $dia) {
            if (!in_array($dia, $diasCompletados)) {
                $diaActual = $dia;
                break;
            }
            if ($index === count($todosLosDias) - 1) {
                $diaActual = $todosLosDias[0];
            }
        }

        return ['dia_actual' => $diaActual];
    }

    /**
     * Marca todos los sets del día actual como completados y avanza al día siguiente.
     *
     * @return array{ dia_actual: string, rutina_nombre: string }|array{ error: string }
     */
    public function finalizarRutinaDia(\App\Models\User $user): array
    {
        // D1: nivel/modalidad vienen de la relación `rutina`, no de columnas denormalizadas.
        $userRutina = $user->rutinaSeleccionada()->with('rutina')->first();

        if (!$userRutina || !$userRutina->rutina) {
            return ['error' => 'No hay rutina seleccionada'];
        }

        $rutina = $userRutina->rutina;
        $rutinaNombre = $rutina->nivel.' '.$rutina->modalidad;
        $diaActual = $userRutina->dia_actual ?: 'Día 1';

        $rutinasDelDia = Rutina::where('nivel', $rutina->nivel)
            ->where('modalidad', $rutina->modalidad)
            ->where('dia', $diaActual)
            ->orderBy('orden')
            ->get();

        foreach ($rutinasDelDia as $rutinaDelDia) {
            $totalSeries = max(1, (int) $rutinaDelDia->series);

            for ($serie = 1; $serie <= $totalSeries; $serie++) {
                $historial = Historial::firstOrNew([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutinaDelDia->ejercicio_nombre,
                    'series_numero' => $serie,
                ]);

                $historial->fill([
                    'user_id' => $user->id,
                    'rutina_nombre' => $rutinaNombre,
                    'dia' => $diaActual,
                    'ejercicio_nombre' => $rutinaDelDia->ejercicio_nombre,
                    'series_numero' => $serie,
                    'series_completadas' => $historial->exists ? $historial->series_completadas : 1,
                    'reps_min' => $rutinaDelDia->reps_min,
                    'reps_max' => $rutinaDelDia->reps_max,
                    'reps_realizadas' => $historial->exists ? $historial->reps_realizadas : null,
                    'descanso_min' => $rutinaDelDia->descanso_min,
                    'completado' => true,
                    'fecha' => Carbon::now()->toDateString(),
                    'superserie_grupo' => $rutinaDelDia->superserie_grupo,
                ]);

                $historial->save();
            }
        }

        $diasDisponibles = Rutina::where('nivel', $rutina->nivel)
            ->where('modalidad', $rutina->modalidad)
            ->selectRaw('DISTINCT dia')
            ->orderBy('dia')
            ->pluck('dia')
            ->toArray();

        $diaSiguiente = 'Día 1';
        $indiceActual = array_search($diaActual, $diasDisponibles, true);

        if ($indiceActual !== false && isset($diasDisponibles[$indiceActual + 1])) {
            $diaSiguiente = $diasDisponibles[$indiceActual + 1];
        }

        $userRutina->update([
            'dia_actual' => $diaSiguiente,
        ]);

        return [
            'dia_actual' => $diaSiguiente,
            'rutina_nombre' => $rutinaNombre,
        ];
    }

    /**
     * Devuelve las fechas con sesiones en un mes/año.
     *
     * @return array{ year: int, month: int, dates: string[], counts: array<string,int>, series: array<string,int> }
     */
    public function buildCalendar(int $userId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $sesiones = Historial::where('user_id', $userId)
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('fecha, COUNT(*) as total, SUM(series_completadas) as series')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return [
            'year' => $year,
            'month' => $month,
            'dates' => $sesiones->pluck('fecha')->toArray(),
            'counts' => $sesiones->pluck('total', 'fecha')->toArray(),
            'series' => $sesiones->pluck('series', 'fecha')->toArray(),
        ];
    }

    /**
     * Compara el rendimiento de un ejercicio entre dos fechas.
     *
     * Toma los sets completados de un ejercicio en la ventana [desde, hasta]
     * y calcula diffs numéricos entre el "cuarto inicial" y el "cuarto final"
     * del período (cada uno = 25% del total). Esto evita comparar outliers
     * únicos y da una señal más estable que "primer set vs último set".
     *
     * @return array{
     *   ejercicio: string, desde: string, hasta: string, dias: int,
     *   desde_stats: array, hasta_stats: array, diff: array
     * }
     */
    public function compararEjercicio(int $userId, string $ejercicioNombre, string $desde, string $hasta): array
    {
        $rows = \App\Models\Historial::where('user_id', $userId)
            ->where('ejercicio_nombre', $ejercicioNombre)
            ->where('completado', true)
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->get(['fecha', 'peso', 'reps_realizadas', 'series_completadas']);

        $statsVacio = [
            'peso_max' => 0,
            'reps_promedio' => 0,
            'volumen_total' => 0,
            'sets' => 0,
        ];

        if ($rows->isEmpty()) {
            return [
                'ejercicio' => $ejercicioNombre,
                'desde' => $desde,
                'hasta' => $hasta,
                'dias' => (int) \Carbon\Carbon::parse($desde)->diffInDays($hasta),
                'desde_stats' => $statsVacio,
                'hasta_stats' => $statsVacio,
                'diff' => ['peso_max' => 0, 'peso_max_pct' => 0, 'reps_promedio' => 0, 'volumen_total' => 0, 'volumen_pct' => 0, 'sets' => 0],
            ];
        }

        // Partimos el set en 2 mitades: la primera (entrada al período) y la segunda (salida).
        $mitad = (int) ceil($rows->count() / 2);
        $primeras = $rows->take($mitad);
        $ultimas = $rows->slice($mitad)->values();

        $calc = function ($sub) {
            $sets = $sub->count();
            $pesoMax = (float) ($sub->max('peso') ?? 0);
            $repsTotal = (int) $sub->sum('reps_realizadas');
            $repsProm = $sets > 0 ? round($repsTotal / $sets, 1) : 0;
            $volumen = (float) $sub->sum(fn($r) => (float) ($r->peso ?? 0) * (int) ($r->reps_realizadas ?? 0));
            return [
                'peso_max' => $pesoMax,
                'reps_promedio' => $repsProm,
                'volumen_total' => round($volumen, 0),
                'sets' => $sets,
            ];
        };

        $desde_stats = $calc($primeras);
        $hasta_stats = $calc($ultimas);

        // Diffs
        $diff = [];
        foreach (['peso_max', 'reps_promedio', 'volumen_total', 'sets'] as $k) {
            $delta = $hasta_stats[$k] - $desde_stats[$k];
            $pct = $desde_stats[$k] > 0 ? round(($delta / $desde_stats[$k]) * 100, 1) : 0;
            $diff[$k] = $delta;
            $diff["{$k}_pct"] = $pct;
        }

        return [
            'ejercicio' => $ejercicioNombre,
            'desde' => $desde,
            'hasta' => $hasta,
            'dias' => (int) \Carbon\Carbon::parse($desde)->diffInDays($hasta),
            'desde_stats' => $desde_stats,
            'hasta_stats' => $hasta_stats,
            'diff' => $diff,
        ];
    }

    /**
     * Resumen de la semana con dots para WeekCalendar.
     *
     * Por defecto la semana actual (lunes a domingo). Se le puede pasar un
     * week_start (lunes) para ver otra semana.
     *
     * @return array{ week_start: string, week_end: string, days: array, totals: array, streak: int }
     */
    public function buildWeekSummary(int $userId, ?string $weekStartIso, StatsService $stats): array
    {
        $weekStart = $weekStartIso
            ? Carbon::parse($weekStartIso)->startOfDay()
            : now()->startOfWeek(Carbon::MONDAY);
        $weekStart = $weekStart->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $sesiones = Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $weekStart->toDateString())
            ->whereDate('fecha', '<=', $weekEnd->toDateString())
            ->selectRaw('DATE(fecha) as fecha_key, COUNT(*) as sets, SUM(peso * COALESCE(reps_realizadas, 0)) as volumen')
            ->groupBy('fecha_key')
            ->orderBy('fecha_key')
            ->get()
            ->keyBy('fecha_key');

        // Ejercicios por día (segunda query — compatible SQLite y MySQL)
        $ejerciciosPorFecha = Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $weekStart->toDateString())
            ->whereDate('fecha', '<=', $weekEnd->toDateString())
            ->select('fecha', 'ejercicio_nombre')
            ->distinct()
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn($r) => Carbon::parse($r->fecha)->toDateString())
            ->map(fn($rows) => $rows->pluck('ejercicio_nombre')->unique()->values()->take(5)->all());

        $days = [];
        $totalSets = 0;
        $totalVolumen = 0;
        $diasEntrenados = 0;
        $diasDescanso = 0;

        $diasSemana = [
            1 => ['lunes', 'Lun'],
            2 => ['martes', 'Mar'],
            3 => ['miércoles', 'Mié'],
            4 => ['jueves', 'Jue'],
            5 => ['viernes', 'Vie'],
            6 => ['sábado', 'Sáb'],
            7 => ['domingo', 'Dom'],
        ];

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $weekStart->copy()->addDays($i);
            $date = $currentDay->toDateString();
            $sesion = $sesiones->get($date);
            $esHoy = $date === now()->toDateString();
            $diaNum = (int) $currentDay->dayOfWeek ?: 7;  // 0=Dom → 7

            $ejercicios = [];
            $sets = 0;
            $volumen = 0;
            $completado = false;

            if ($sesion) {
                $completado = true;
                $sets = (int) $sesion->sets;
                $volumen = (float) $sesion->volumen;
                $ejercicios = $ejerciciosPorFecha->get($date, []);
                $totalSets += $sets;
                $totalVolumen += $volumen;
                $diasEntrenados++;
            } else {
                $diasDescanso++;
            }

            $days[] = [
                'date' => $date,
                'dia_semana_es' => ucfirst($diasSemana[$diaNum][0]),
                'dia_semana_corto' => $diasSemana[$diaNum][1],
                'es_hoy' => $esHoy,
                'sets' => $sets,
                'volumen' => round($volumen, 1),
                'ejercicios' => $ejercicios,
                'completado' => $completado,
            ];
        }

        // Streak: reusa StatsService (antes era calcCurrentStreakInline duplicado)
        $diasConActividad = $stats->getDiasConActividad($userId);
        $streak = $stats->calcCurrentStreak($diasConActividad);

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'days' => $days,
            'totals' => [
                'sets' => $totalSets,
                'volumen' => round($totalVolumen, 1),
                'dias_entrenados' => $diasEntrenados,
                'dias_descanso' => $diasDescanso,
            ],
            'streak' => $streak,
        ];
    }
}
