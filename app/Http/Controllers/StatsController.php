<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveAlumnoObjetivo;
use App\Services\CacheHistorial;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Endpoints de stats del alumno. Toda la lógica vive en StatsService;
 * este controller solo:
 *  - valida/parsea el request
 *  - cachea
 *  - serializa a JSON
 *  - chequea autorización cuando aplica (estimar 1RM de otro user)
 */
class StatsController extends Controller
{
    use ResuelveAlumnoObjetivo;

    public function __construct(private StatsService $stats) {}

    public function resumen(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);
        $cacheKey = CacheHistorial::key($targetUserId, 'stats:resumen');

        return Cache::remember($cacheKey, 300, function () use ($targetUserId) {
            $dias = $this->stats->getDiasConActividad($targetUserId);

            return response()->json([
                'current_streak' => $this->stats->calcCurrentStreak($dias),
                'longest_streak' => $this->stats->calcLongestStreak($dias),
                'this_week' => $this->stats->calcCountInRange($dias, now()->startOfWeek(), now()->endOfWeek()),
                'this_month' => $this->stats->calcCountInRange($dias, now()->startOfMonth(), now()->endOfMonth()),
                'last_30_days' => $this->stats->calcCountInRange($dias, now()->subDays(30), now()),
                'total_workouts' => count($dias),
                'total_sets' => $this->stats->countSets($targetUserId),
            ]);
        });
    }

    public function heatmap(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);
        $weeks = max(1, min(104, (int) $request->input('weeks', 53)));
        $cacheKey = CacheHistorial::key($targetUserId, "stats:heatmap:w{$weeks}");

        return Cache::remember($cacheKey, 600, fn () => response()->json(
            $this->stats->buildHeatmap($targetUserId, $weeks)
        ));
    }

    /**
     * Fase 3/8 — esfuerzo promedio, distribución RIR/RPE y tendencia semanal.
     *
     * Query params:
     *   - window: '30' | '90' | '365' | 'all' (default '30')
     *   - user_id: opcional (trainer/admin)
     */
    public function esfuerzo(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);
        $window = $this->stats->normalizeWindow($request->input('window', '30'));
        $cacheKey = CacheHistorial::key($targetUserId, "stats:esfuerzo:w{$window}");

        return Cache::remember($cacheKey, 300, fn () => response()->json(
            $this->stats->buildEsfuerzo($targetUserId, $window)
        ));
    }

    /**
     * Fase 4 — Estimación de 1RM a lo largo del tiempo para un ejercicio.
     *
     * Query params:
     *   - ejercicio_nombre (string, requerido) — matchea por nombre
     *   - user_id (int, opcional, trainer/admin) — ver el de un alumno
     *   - formula ('epley' | 'lander', default 'epley')
     *   - months (int, default 6) — ventana hacia atrás
     */
    public function estimated1rm(Request $request)
    {
        $user = $request->user();
        $ejercicioNombre = trim((string) $request->input('ejercicio_nombre', ''));
        if ($ejercicioNombre === '') {
            return response()->json(['error' => 'ejercicio_nombre requerido'], 422);
        }
        $formula = $request->input('formula', 'epley');
        if (! in_array($formula, ['epley', 'lander'], true)) {
            $formula = 'epley';
        }
        $months = max(1, min(24, (int) $request->input('months', 6)));

        $targetUserId = $this->usuarioObjetivo($request);

        $cacheKey = CacheHistorial::key($targetUserId, 'stats:1rm:ej:'.md5($ejercicioNombre).":{$formula}:m{$months}");

        return Cache::remember($cacheKey, 600, fn () => response()->json(
            $this->stats->buildEstimated1rm($targetUserId, $ejercicioNombre, $formula, $months)
        ));
    }

    /**
     * Fase 6 — Resumen del día para el HomeHero del dashboard.
     * Cache corto (60s) para no golpear la DB en cada refresh.
     */
    public function dashboardToday(Request $request)
    {
        $user = $request->user();
        $cacheKey = CacheHistorial::key($user->id, 'dashboard:today');

        return Cache::remember($cacheKey, 60, fn () => response()->json(
            $this->stats->buildDashboardToday($user)
        ));
    }
}
