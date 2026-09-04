<?php

namespace App\Services;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Servicio de stats del alumno.
 *
 * Concentra todas las queries y cálculos de la sección "Stats" que antes
 * vivían como métodos private en StatsController. Los métodos públicos
 * retornan arrays puros (no responses), así el controller maneja cache
 * y serialización.
 *
 * Cache: NO cachea internamente. El controller decide TTL por endpoint.
 * Esto permite invalidar el cache desde fuera (al guardar un set) sin
 * tener que conocer detalles de cada query.
 */
class StatsService
{
    /**
     * Lista de fechas (yyyy-mm-dd, ordenadas desc) en que el user tuvo
     * sets completados, dentro de los últimos 2 años.
     */
    public function getDiasConActividad(int $userId): array
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

    /**
     * Racha actual: días consecutivos con al menos 1 set, terminando
     * en hoy o ayer. Si no entrenó hoy, todavía cuenta si entrenó ayer.
     *
     * Entrada: array de fechas ordenadas desc (formato yyyy-mm-dd).
     */
    public function calcCurrentStreak(array $diasOrdenadosDesc): int
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

    /**
     * Racha más larga: máximo de días consecutivos en todo el histórico.
     */
    public function calcLongestStreak(array $diasOrdenadosDesc): int
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

    /**
     * Cantidad de fechas que caen en el rango [$from, $to] (inclusivo).
     */
    public function calcCountInRange(array $dias, $from, $to): int
    {
        $count = 0;
        $fromStr = Carbon::parse($from)->toDateString();
        $toStr = Carbon::parse($to)->toDateString();
        foreach ($dias as $d) {
            if ($d >= $fromStr && $d <= $toStr) $count++;
        }
        return $count;
    }

    public function countSets(int $userId): int
    {
        return Historial::where('user_id', $userId)
            ->where('completado', true)
            ->count();
    }

    /**
     * Heatmap: sets y volumen agrupados por día en las últimas N semanas.
     */
    public function buildHeatmap(int $userId, int $weeks): array
    {
        $desde = now()->subWeeks($weeks)->startOfWeek();
        $hasta = now()->endOfWeek();

        $rows = Historial::where('user_id', $userId)
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

        return [
            'from' => $desde->toDateString(),
            'to' => $hasta->toDateString(),
            'weeks' => $weeks,
            'days' => array_values($byDate),
            'total_sets' => (int) $rows->sum('sets'),
        ];
    }

    /**
     * Esfuerzo: avg RIR/RPE, distribución, top ejercicios y tendencia semanal.
     *
     * Si no hay sets con esfuerzo en la ventana, devuelve payload vacío
     * con la misma forma que el caso normal (los buckets vienen en cero).
     */
    public function buildEsfuerzo(int $userId, string $window): array
    {
        $since = $window === 'all' ? null : now()->subDays((int) $window);

        $base = Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereNotNull('esfuerzo_tipo')
            ->whereNotNull('esfuerzo_valor');
        if ($since) {
            $base->whereDate('fecha', '>=', $since->toDateString());
        }

        $total = (clone $base)->count();
        if ($total === 0) {
            return [
                'window' => $this->windowInfo($window),
                'total_sets' => 0,
                'sets_with_esfuerzo' => 0,
                'avg_por_tipo' => ['rir' => null, 'rpe' => null],
                'avg_hard' => 0,
                'distribucion' => $this->emptyDistribucion(),
                'por_ejercicio' => [],
                'tendencia' => [],
            ];
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

        $dist = $this->emptyDistribucion();
        foreach ($distRows as $r) {
            $tipo = $r->esfuerzo_tipo;
            $val = (int) $r->esfuerzo_valor;
            // RIR: keys 0..5. RPE: keys 0..4 (acceso por val-6).
            if ($tipo === 'rir' && $val >= 0 && $val <= 5) {
                $dist['rir'][$val]['count'] = (int) $r->n;
            } elseif ($tipo === 'rpe' && $val >= 6 && $val <= 10) {
                $dist['rpe'][$val - 6]['count'] = (int) $r->n;
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
        $tendencia = $this->buildWeeklyTrend($userId, $since);

        // Total de sets completados en el período (para % de cobertura)
        $totalSetsQuery = Historial::where('user_id', $userId)
            ->where('completado', true);
        if ($since) {
            $totalSetsQuery->whereDate('fecha', '>=', $since->toDateString());
        }
        $totalSets = (int) $totalSetsQuery->count();

        return [
            'window' => $this->windowInfo($window),
            'total_sets' => $totalSets,
            'sets_with_esfuerzo' => $total,
            'avg_por_tipo' => $avgPorTipo,
            'avg_hard' => $avgHard,
            'distribucion' => $dist,
            'por_ejercicio' => $porEj,
            'tendencia' => $tendencia,
        ];
    }

    public function normalizeWindow($raw): string
    {
        $raw = (string) $raw;
        if (in_array($raw, ['30', '90', '365', 'all'], true)) return $raw;
        return '30';
    }

    public function windowInfo(string $window): array
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
    public function buildWeeklyTrend(int $userId, $since): array
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

    public function shortWeekLabel(Carbon $date): string
    {
        // Ej: "25 ago" o "1 sep"
        $months = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
        return $date->day . ' ' . $months[(int) $date->month];
    }

    /**
     * Estimación de 1RM a lo largo del tiempo.
     *
     * @param  string  $formula  'epley' (default) o 'lander'
     * @param  int  $months  ventana hacia atrás (1-24, default 6)
     */
    public function buildEstimated1rm(int $userId, string $ejercicioNombre, string $formula, int $months): array
    {
        $desde = now()->subMonths($months);

        $calc = function ($w, $r) use ($formula) {
            if ($formula === 'lander') {
                return (100 * $w) / (101.3 - 2.6712 * $r);
            }
            return $w * (1 + $r / 30);
        };

        $rows = Historial::where('user_id', $userId)
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

        return [
            'ejercicio' => $ejercicioNombre,
            'formula' => $formula,
            'months' => $months,
            'best_1rm' => $best1rm,
            'estimated_1rm' => $estimatedLast,
            'timeline' => $timeline,
            'pr_count' => $prCount,
            'total_sets' => count($timeline),
        ];
    }

    /**
     * Detecta "mesetas" (plateaus) en el progreso del usuario.
     *
     * Lógica:
     *   - Para cada ejercicio con al menos N sets en las últimas 6 semanas,
     *     comparamos el PESO MÁXIMO en las últimas 4 semanas contra el de
     *     las 2 semanas anteriores.
     *   - Si el peso máximo NO subió (o bajó) → hay plateau.
     *
     * Devuelve array de detecciones:
     *   [
     *     'ejercicio' => 'Press banca',
     *     'peso_reciente' => 60.0,
     *     'peso_anterior' => 60.0,
     *     'semanas_estancado' => 4,
     *     'sugerencia' => 'deload'|'cambio_ejercicio'|'aumentar_volumen',
     *   ]
     *
     * El command DetectPlateaus corre esto por todos los users activos
     * y manda una notificación por cada plateau detectado.
     */
    public function detectPlateaus(int $userId, int $minSets = 6, int $windowWeeks = 4): array
    {
        $now = now();
        $corteReciente = $now->copy()->subWeeks($windowWeeks);
        $corteAnterior = $now->copy()->subWeeks($windowWeeks + 2);

        // Traer todos los sets con peso del usuario en las últimas 6+ semanas
        $rows = \App\Models\Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->whereDate('fecha', '>=', $corteAnterior->toDateString())
            ->selectRaw('ejercicio_nombre, peso, fecha')
            ->orderBy('fecha')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Agrupar por ejercicio
        $porEjercicio = $rows->groupBy('ejercicio_nombre');
        $plateaus = [];

        foreach ($porEjercicio as $ejNombre => $sets) {
            if ($sets->count() < $minSets) {
                continue;
            }

            $pesoReciente = $sets->filter(fn($s) => $s->fecha >= $corteReciente->toDateString())
                ->max('peso');
            $pesoAnterior = $sets->filter(fn($s) => $s->fecha < $corteReciente->toDateString() && $s->fecha >= $corteAnterior->toDateString())
                ->max('peso');

            // Si no hay datos en ambas ventanas, no podemos comparar
            if ($pesoReciente === null || $pesoAnterior === null) {
                continue;
            }

            // Plateau: peso máximo NO subió
            if ((float) $pesoReciente <= (float) $pesoAnterior) {
                $plateaus[] = [
                    'ejercicio' => $ejNombre,
                    'peso_reciente' => (float) $pesoReciente,
                    'peso_anterior' => (float) $pesoAnterior,
                    'semanas_estancado' => $windowWeeks,
                    'sugerencia' => $this->plateauSuggestion($sets, $windowWeeks),
                ];
            }
        }

        return $plateaus;
    }

    /**
     * Sugerencia para salir del plateau según el patrón reciente.
     *
     *   - deload: volumen reciente cayó mucho → fatiga acumulada
     *   - cambio_ejercicio: estable pero estancado → variante
     *   - aumentar_volumen: mantiene peso/reps pero pocas series → más volumen
     */
    protected function plateauSuggestion($sets, int $windowWeeks): string
    {
        $mitad = $windowWeeks / 2;
        $corteMedio = now()->copy()->subWeeks((int) ceil($mitad));

        $recientes = $sets->filter(fn($s) => $s->fecha >= $corteMedio->toDateString());
        $anteriores = $sets->filter(fn($s) => $s->fecha < $corteMedio->toDateString());

        $volumenReciente = $recientes->sum(fn($s) => (float) $s->peso * (int) ($s->reps_realizadas ?? 0));
        $volumenAnterior = $anteriores->sum(fn($s) => (float) $s->peso * (int) ($s->reps_realizadas ?? 0));

        // Fatiga acumulada: volumen cayó >= 30% → deload
        if ($volumenAnterior > 0 && ($volumenReciente / $volumenAnterior) < 0.7) {
            return 'deload';
        }

        // Pocas series por semana: aumentar volumen
        $semanasRecientes = max(1, $mitad);
        $setsPorSemana = $recientes->count() / $semanasRecientes;
        if ($setsPorSemana < 6) {
            return 'aumentar_volumen';
        }

        // Default: ya hace volumen, probar variante
        return 'cambio_ejercicio';
    }

    /**
     * Resumen del día para el HomeHero del dashboard.
     *
     * Devuelve:
     *  - rutina: { id, nombre, nivel, modalidad, dia_actual }
     *  - hoy: { fecha, dia_semana_es, saludo, nombre }
     *  - stats: { streak, last_workout_at, days_since_last_workout, total_sets_30d }
     *  - quick: { suggested_action: 'empezar'|'continuar'|'descanso'|'nueva_rutina' }
     */
    public function buildDashboardToday(User $user): array
    {
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
        $diasConActividad = $this->getDiasConActividad($user->id);
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

        return [
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
        ];
    }

    /**
     * Distribución vacía inicializada con todos los buckets en 0.
     *
     * Shape: cada "bucket" es un objeto {valor, count}, NO un entero suelto.
     * Esto es importante porque:
     *   - Mantiene el shape estable entre el caso "sin datos" y "con datos"
     *   - Permite que el frontend itere con `forEach` (siempre array JSON)
     *   - Separa el "valor semántico" (6, 7, 8... para RPE) del índice de array
     *     (siempre 0..N) — fundamental porque json_encode en PHP
     *     no preserva keys numéricas que no arrancan en 0, y eso causaba
     *     que `distribucion.rpe` llegara al frontend como objeto en vez de
     *     array, reventando el `forEach`.
     */
    private function emptyDistribucion(): array
    {
        $make = fn($v) => ['valor' => $v, 'count' => 0];
        return [
            'rir' => array_map($make, range(0, 5)),
            'rpe' => array_map($make, range(6, 10)),
        ];
    }
}
