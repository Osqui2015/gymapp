<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Stats agregadas del alumno para la sección "Stats" (estilo openGym):
 *   - Streak (racha actual de días consecutivos con al menos 1 set completado)
 *   - Heatmap (año de actividad, agrupado por día)
 *   - Resumen rápido (total workouts, días esta semana, etc.)
 */
class StatsController extends Controller
{
    public function resumen(Request $request)
    {
        $user = $request->user();
        $cacheKey = "stats:resumen:user:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $dias = $this->getDiasConActividad($user->id);

            return response()->json([
                'current_streak' => $this->calcCurrentStreak($dias),
                'longest_streak' => $this->calcLongestStreak($dias),
                'this_week' => $this->calcCountInRange($dias, now()->startOfWeek(), now()->endOfWeek()),
                'this_month' => $this->calcCountInRange($dias, now()->startOfMonth(), now()->endOfMonth()),
                'last_30_days' => $this->calcCountInRange($dias, now()->subDays(30), now()),
                'total_workouts' => count($dias),
                'total_sets' => $this->countSets($user->id),
            ]);
        });
    }

    public function heatmap(Request $request)
    {
        $user = $request->user();
        $weeks = (int) $request->input('weeks', 53);
        $cacheKey = "stats:heatmap:user:{$user->id}:w{$weeks}";

        return Cache::remember($cacheKey, 600, function () use ($user, $weeks) {
            $desde = now()->subWeeks($weeks)->startOfWeek();
            $hasta = now()->endOfWeek();

            // Agrupar por fecha y contar sets
            $rows = Historial::where('user_id', $user->id)
                ->where('completado', true)
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->selectRaw('fecha, count(*) as sets, sum(peso) as volumen')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get();

            $byDate = [];
            foreach ($rows as $r) {
                $key = Carbon::parse($r->fecha)->toDateString();
                $byDate[$key] = [
                    'fecha' => $key,
                    'sets' => (int) $r->sets,
                    'volumen' => (float) $r->volumen,
                ];
            }

            return response()->json([
                'from' => $desde->toDateString(),
                'to' => $hasta->toDateString(),
                'weeks' => $weeks,
                'days' => array_values($byDate),
                'total_sets' => (int) $rows->sum('sets'),
            ]);
        });
    }

    private function getDiasConActividad(int $userId): array
    {
        return Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', now()->subYears(2))
            ->selectRaw('DISTINCT fecha')
            ->orderBy('fecha', 'desc')
            ->pluck('fecha')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    private function calcCurrentStreak(array $diasOrdenadosDesc): int
    {
        if (empty($diasOrdenadosDesc)) return 0;
        $streak = 0;
        $expected = now()->toDateString();
        $diasSet = array_flip($diasOrdenadosDesc);

        // Si no entrenó hoy, empezar desde ayer (la racha todavía no se rompió)
        if (!isset($diasSet[$expected])) {
            $expected = now()->subDay()->toDateString();
            if (!isset($diasSet[$expected])) return 0;
        }

        while (isset($diasSet[$expected])) {
            $streak++;
            $expected = Carbon::parse($expected)->subDay()->toDateString();
        }
        return $streak;
    }

    private function calcLongestStreak(array $diasOrdenadosDesc): int
    {
        if (empty($diasOrdenadosDesc)) return 0;
        // Convertir a ascendente para walk forward
        $dias = $diasOrdenadosDesc;
        rsort($dias);  // ascendente ahora

        $longest = 1;
        $current = 1;
        for ($i = 1; $i < count($dias); $i++) {
            $prev = Carbon::parse($dias[$i - 1]);
            $curr = Carbon::parse($dias[$i]);
            if ($prev->diffInDays($curr) === 1) {
                $current++;
                $longest = max($longest, $current);
            } else {
                $current = 1;
            }
        }
        return $longest;
    }

    private function calcCountInRange(array $dias, $from, $to): int
    {
        $count = 0;
        $fromStr = Carbon::parse($from)->toDateString();
        $toStr = Carbon::parse($to)->toDateString();
        foreach ($dias as $d) {
            if ($d >= $fromStr && $d <= $toStr) $count++;
        }
        return $count;
    }

    private function countSets(int $userId): int
    {
        return Historial::where('user_id', $userId)
            ->where('completado', true)
            ->count();
    }

    /**
     * Fase 3/8 — esfuerzo promedio, distribución RIR/RPE y tendencia semanal.
     *
     * Query params:
     *   - window: '30' | '90' | '365' | 'all' (default '30')
     *   - user_id: opcional (trainer/admin)
     *
     * Devuelve:
     *  - window: { days, label }
     *  - total_sets, sets_with_esfuerzo
     *  - avg_por_tipo: { rir: 1.5, rpe: 8.3 } o null si no hay data
     *  - avg_hard: % de sets en RIR ≤ 2 o RPE ≥ 8 (esfuerzo alto)
     *  - distribucion: array con buckets por tipo (0..5 RIR / 6..10 RPE)
     *  - por_ejercicio: top 5 ejercicios por frecuencia
     *  - tendencia: array de buckets semanales { week_start, week_label, rir, rpe, sets, rir_sets, rpe_sets }
     */
    public function esfuerzo(Request $request)
    {
        $user = $request->user();
        $window = $this->normalizeWindow($request->input('window', '30'));
        $cacheKey = "stats:esfuerzo:user:{$user->id}:w{$window}";

        return Cache::remember($cacheKey, 300, function () use ($user, $window) {
            $since = $window === 'all' ? null : now()->subDays((int) $window);

            $base = Historial::where('user_id', $user->id)
                ->where('completado', true)
                ->whereNotNull('esfuerzo_tipo')
                ->whereNotNull('esfuerzo_valor');
            if ($since) {
                $base->whereDate('fecha', '>=', $since->toDateString());
            }

            $total = (clone $base)->count();
            if ($total === 0) {
                return response()->json([
                    'window' => $this->windowInfo($window),
                    'total_sets' => 0,
                    'sets_with_esfuerzo' => 0,
                    'avg_por_tipo' => ['rir' => null, 'rpe' => null],
                    'avg_hard' => 0,
                    'distribucion' => [
                        'rir' => array_fill(0, 6, 0),
                        'rpe' => array_fill(6, 5, 0),
                    ],
                    'por_ejercicio' => [],
                    'tendencia' => [],
                ]);
            }

            // Promedio por tipo
            $avgs = (clone $base)
                ->selectRaw('esfuerzo_tipo, AVG(esfuerzo_valor) as avg_val, COUNT(*) as n')
                ->groupBy('esfuerzo_tipo')
                ->get()
                ->pluck('avg_val', 'esfuerzo_tipo')
                ->map(fn($v) => round((float) $v, 2))
                ->all();

            $avgPorTipo = [
                'rir' => $avgs['rir'] ?? null,
                'rpe' => $avgs['rpe'] ?? null,
            ];

            // % de sets "duros" (RIR ≤ 2 o RPE ≥ 8) — coherente con la métrica de openGym "RIR 3 or harder"
            $hardCount = (clone $base)
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('esfuerzo_tipo', 'rir')->where('esfuerzo_valor', '<=', 2);
                    })->orWhere(function ($q2) {
                        $q2->where('esfuerzo_tipo', 'rpe')->where('esfuerzo_valor', '>=', 8);
                    });
                })
                ->count();
            $avgHard = $total > 0 ? (int) round(($hardCount / $total) * 100) : 0;

            // Distribución (buckets por valor exacto)
            $distRows = (clone $base)
                ->selectRaw('esfuerzo_tipo, esfuerzo_valor, COUNT(*) as n')
                ->groupBy('esfuerzo_tipo', 'esfuerzo_valor')
                ->get();

            $dist = [
                'rir' => array_fill(0, 6, 0),  // 0..5
                'rpe' => array_fill(6, 5, 0),  // 6..10 (índices 6,7,8,9,10)
            ];
            foreach ($distRows as $r) {
                $tipo = $r->esfuerzo_tipo;
                $val = (int) $r->esfuerzo_valor;
                if ($tipo === 'rir' && $val >= 0 && $val <= 5) {
                    $dist['rir'][$val] = (int) $r->n;
                } elseif ($tipo === 'rpe' && $val >= 6 && $val <= 10) {
                    $dist['rpe'][$val] = (int) $r->n;
                }
            }

            // Top ejercicios más trackeados
            $porEj = (clone $base)
                ->selectRaw('ejercicio_nombre, COUNT(*) as n, AVG(esfuerzo_valor) as avg_val')
                ->groupBy('ejercicio_nombre')
                ->orderByDesc('n')
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    'ejercicio' => $r->ejercicio_nombre,
                    'sets' => (int) $r->n,
                    'avg' => round((float) $r->avg_val, 2),
                ])
                ->all();

            // Tendencia semanal (compatible con MySQL y SQLite)
            $tendencia = $this->buildWeeklyTrend($user->id, $since);

            // Total de sets completados en el período (para % de cobertura)
            $totalSetsQuery = Historial::where('user_id', $user->id)
                ->where('completado', true);
            if ($since) {
                $totalSetsQuery->whereDate('fecha', '>=', $since->toDateString());
            }
            $totalSets = (int) $totalSetsQuery->count();

            return response()->json([
                'window' => $this->windowInfo($window),
                'total_sets' => $totalSets,
                'sets_with_esfuerzo' => $total,
                'avg_por_tipo' => $avgPorTipo,
                'avg_hard' => $avgHard,
                'distribucion' => $dist,
                'por_ejercicio' => $porEj,
                'tendencia' => $tendencia,
            ]);
        });
    }

    private function normalizeWindow($raw): string
    {
        $raw = (string) $raw;
        if (in_array($raw, ['30', '90', '365', 'all'], true)) return $raw;
        return '30';
    }

    private function windowInfo(string $window): array
    {
        $map = [
            '30' => ['key' => '30', 'days' => 30, 'label' => '30 días'],
            '90' => ['key' => '90', 'days' => 90, 'label' => '90 días'],
            '365' => ['key' => '365', 'days' => 365, 'label' => '1 año'],
            'all' => ['key' => 'all', 'days' => null, 'label' => 'Todo'],
        ];
        return $map[$window];
    }

    /**
     * Construye buckets semanales con avg de RIR / RPE / sets.
     * Una semana = lunes a domingo.
     *
     * Devuelve array de { week_start, week_label, rir, rpe, sets, rir_sets, rpe_sets }
     * donde rir/rpe es null si no hubo sets de ese tipo en esa semana.
     */
    private function buildWeeklyTrend(int $userId, $since): array
    {
        // Traer sets con esfuerzo (sin filtrar por tipo)
        $query = Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereNotNull('esfuerzo_tipo')
            ->whereNotNull('esfuerzo_valor');
        if ($since) {
            $query->whereDate('fecha', '>=', $since->toDateString());
        }
        $rows = $query->orderBy('fecha')->get(['fecha', 'esfuerzo_tipo', 'esfuerzo_valor']);

        if ($rows->isEmpty()) return [];

        // Agrupar por semana (lunes)
        $byWeek = [];
        foreach ($rows as $r) {
            $fecha = Carbon::parse($r->fecha);
            $weekStart = $fecha->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            if (!isset($byWeek[$weekStart])) {
                $byWeek[$weekStart] = [
                    'rir_sum' => 0, 'rir_n' => 0,
                    'rpe_sum' => 0, 'rpe_n' => 0,
                    'sets' => 0,
                ];
            }
            $byWeek[$weekStart]['sets']++;
            if ($r->esfuerzo_tipo === 'rir') {
                $byWeek[$weekStart]['rir_sum'] += (int) $r->esfuerzo_valor;
                $byWeek[$weekStart]['rir_n']++;
            } elseif ($r->esfuerzo_tipo === 'rpe') {
                $byWeek[$weekStart]['rpe_sum'] += (int) $r->esfuerzo_valor;
                $byWeek[$weekStart]['rpe_n']++;
            }
        }

        ksort($byWeek);  // orden cronológico ascendente

        $out = [];
        foreach ($byWeek as $weekStart => $bucket) {
            $out[] = [
                'week_start' => $weekStart,
                'week_label' => $this->shortWeekLabel(Carbon::parse($weekStart)),
                'rir' => $bucket['rir_n'] > 0 ? round($bucket['rir_sum'] / $bucket['rir_n'], 2) : null,
                'rpe' => $bucket['rpe_n'] > 0 ? round($bucket['rpe_sum'] / $bucket['rpe_n'], 2) : null,
                'rir_sets' => $bucket['rir_n'],
                'rpe_sets' => $bucket['rpe_n'],
                'sets' => $bucket['sets'],
            ];
        }
        return $out;
    }

    private function shortWeekLabel(Carbon $date): string
    {
        // Ej: "25 ago" o "1 sep"
        $months = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
        return $date->day . ' ' . $months[(int) $date->month];
    }

    /**
     * Fase 4 — Estimación de 1RM a lo largo del tiempo para un ejercicio.
     *
     * Query params:
     *   - ejercicio_nombre (string, requerido) — matchea por nombre
     *   - user_id (int, opcional, trainer/admin) — ver el de un alumno
     *   - formula ('epley' | 'lander', default 'epley')
     *   - months (int, default 6) — ventana hacia atrás
     *
     * Response:
     *   - ejercicio: nombre
     *   - formula: fórmula usada
     *   - best_1rm: { value, weight, reps, fecha, dia }
     *   - estimated_1rm: { value, weight, reps, fecha, dia } (último cálculo)
     *   - timeline: array de { fecha, estimated_1rm, weight, reps, dia }
     *   - pr_count: cuántos PRs batidos en el período
     *   - total_sets: sets con peso+reps en el período
     */
    public function estimated1rm(Request $request)
    {
        $user = $request->user();
        $ejercicioNombre = trim((string) $request->input('ejercicio_nombre', ''));
        if ($ejercicioNombre === '') {
            return response()->json(['error' => 'ejercicio_nombre requerido'], 422);
        }
        $formula = $request->input('formula', 'epley');
        if (!in_array($formula, ['epley', 'lander'], true)) {
            $formula = 'epley';
        }
        $months = max(1, min(24, (int) $request->input('months', 6)));

        $targetUserId = (int) $request->integer('user_id', $user->id);
        if ($targetUserId !== $user->id) {
            if (! $user->hasRole([\App\Models\User::ROLE_TRAINER, \App\Models\User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            if ($user->hasRole(\App\Models\User::ROLE_TRAINER)) {
                $target = \App\Models\User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
        }

        $cacheKey = "stats:1rm:user:{$targetUserId}:ej:" . md5($ejercicioNombre) . ":{$formula}:m{$months}";

        return Cache::remember($cacheKey, 600, function () use ($targetUserId, $ejercicioNombre, $formula, $months) {
            $desde = now()->subMonths($months);

            $calc = function ($w, $r) use ($formula) {
                if ($formula === 'lander') {
                    return (100 * $w) / (101.3 - 2.6712 * $r);
                }
                return $w * (1 + $r / 30);
            };

            $rows = Historial::where('user_id', $targetUserId)
                ->where('ejercicio_nombre', $ejercicioNombre)
                ->where('completado', true)
                ->whereNotNull('peso')
                ->whereNotNull('reps_realizadas')
                ->whereDate('fecha', '>=', $desde->toDateString())
                ->orderBy('fecha')
                ->orderBy('id')
                ->get(['fecha', 'peso', 'reps_realizadas', 'dia']);

            $timeline = [];
            $best1rm = null;
            $prCount = 0;

            foreach ($rows as $row) {
                $w = (float) $row->peso;
                $r = (int) $row->reps_realizadas;
                if ($w <= 0 || $r <= 0) continue;
                $est = round($calc($w, $r), 1);

                $timeline[] = [
                    'fecha' => Carbon::parse($row->fecha)->toDateString(),
                    'estimated_1rm' => $est,
                    'weight' => $w,
                    'reps' => $r,
                    'dia' => $row->dia,
                ];

                if ($best1rm === null || $est > $best1rm['value']) {
                    if ($best1rm !== null && $est > $best1rm['value']) {
                        $prCount++;
                    }
                    $best1rm = [
                        'value' => $est,
                        'weight' => $w,
                        'reps' => $r,
                        'fecha' => Carbon::parse($row->fecha)->toDateString(),
                        'dia' => $row->dia,
                    ];
                }
            }

            $estimatedLast = end($timeline) ?: null;

            return response()->json([
                'ejercicio' => $ejercicioNombre,
                'formula' => $formula,
                'months' => $months,
                'best_1rm' => $best1rm,
                'estimated_1rm' => $estimatedLast,
                'timeline' => $timeline,
                'pr_count' => $prCount,
                'total_sets' => count($timeline),
            ]);
        });
    }

    /**
     * Fase 6 — Resumen del día para el HomeHero del dashboard.
     *
     * Cache corto (60s) para no golpear la DB en cada refresh.
     * Devuelve:
     *  - rutina: { id, nombre, nivel, modalidad, dia_actual }
     *  - hoy: { fecha, dia_semana_es, saludo }
     *  - stats: { streak, last_workout_at, days_since_last_workout, total_sets_30d }
     *  - quick: { suggested_action: 'empezar'|'continuar'|'descanso'|'nueva_rutina' }
     */
    public function dashboardToday(Request $request)
    {
        $user = $request->user();
        $cacheKey = "dashboard:today:user:{$user->id}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            $userRutina = $user->rutinaSeleccionada()->with('rutina')->first();
            $rutina = $userRutina?->rutina;

            $ultimoHistorial = Historial::where('user_id', $user->id)
                ->where('completado', true)
                ->orderBy('fecha', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $diasDesdeUltimo = $ultimoHistorial
                ? (int) Carbon::parse($ultimoHistorial->fecha)->startOfDay()->diffInDays(now()->startOfDay())
                : null;

            $sets30d = (int) Historial::where('user_id', $user->id)
                ->where('completado', true)
                ->whereDate('fecha', '>=', now()->subDays(30)->toDateString())
                ->count();

            // Resumen rápido de racha (reusar lógica)
            $diasConActividad = Historial::where('user_id', $user->id)
                ->where('completado', true)
                ->whereDate('fecha', '>=', now()->subYears(2)->toDateString())
                ->selectRaw('DISTINCT fecha')
                ->orderBy('fecha', 'desc')
                ->pluck('fecha')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->all();

            $streak = $this->calcCurrentStreak($diasConActividad);

            // Sugerir acción
            $quick = 'empezar';
            if (!$rutina) {
                $quick = 'nueva_rutina';
            } elseif ($diasDesdeUltimo === null) {
                $quick = 'empezar'; // primera vez
            } elseif ($diasDesdeUltimo === 0) {
                $quick = 'continuar'; // entrenó hoy
            } elseif ($diasDesdeUltimo === 1) {
                $quick = 'empezar';
            } elseif ($diasDesdeUltimo >= 2 && $diasDesdeUltimo <= 3) {
                $quick = 'continuar';
            } elseif ($diasDesdeUltimo >= 4) {
                $quick = 'empezar';
            }

            $nombreRutina = $rutina
                ? trim("{$rutina->nivel} {$rutina->modalidad}")
                : null;

            $hora = (int) now()->format('H');
            $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

            return response()->json([
                'rutina' => $rutina ? [
                    'id' => $rutina->id,
                    'nombre' => $nombreRutina,
                    'nivel' => $rutina->nivel,
                    'modalidad' => $rutina->modalidad,
                    'dia_actual' => $userRutina->dia_actual ?? 'Día 1',
                ] : null,
                'hoy' => [
                    'fecha' => now()->toDateString(),
                    'dia_semana_es' => ucfirst(Carbon::now()->locale('es')->dayName),
                    'saludo' => $saludo,
                    'nombre' => $user->name,
                ],
                'stats' => [
                    'streak' => $streak,
                    'last_workout_at' => $ultimoHistorial?->fecha?->toDateString(),
                    'days_since_last_workout' => $diasDesdeUltimo,
                    'total_sets_30d' => $sets30d,
                ],
                'quick' => $quick,
            ]);
        });
    }
}
