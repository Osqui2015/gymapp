<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    public function __construct(private StatsService $stats) {}

    public function resumen(Request $request)
    {
        $user = $request->user();
        $cacheKey = "stats:resumen:user:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $dias = $this->stats->getDiasConActividad($user->id);

            return response()->json([
                'current_streak' => $this->stats->calcCurrentStreak($dias),
                'longest_streak' => $this->stats->calcLongestStreak($dias),
                'this_week' => $this->stats->calcCountInRange($dias, now()->startOfWeek(), now()->endOfWeek()),
                'this_month' => $this->stats->calcCountInRange($dias, now()->startOfMonth(), now()->endOfMonth()),
                'last_30_days' => $this->stats->calcCountInRange($dias, now()->subDays(30), now()),
                'total_workouts' => count($dias),
                'total_sets' => $this->stats->countSets($user->id),
            ]);
        });
    }

    public function heatmap(Request $request)
    {
        $user = $request->user();
        $weeks = (int) $request->input('weeks', 53);
        $cacheKey = "stats:heatmap:user:{$user->id}:w{$weeks}";

        return Cache::remember($cacheKey, 600, fn() => response()->json(
            $this->stats->buildHeatmap($user->id, $weeks)
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
        $user = $request->user();
        $window = $this->stats->normalizeWindow($request->input('window', '30'));
        $cacheKey = "stats:esfuerzo:user:{$user->id}:w{$window}";

        return Cache::remember($cacheKey, 300, fn() => response()->json(
            $this->stats->buildEsfuerzo($user->id, $window)
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
        if (!in_array($formula, ['epley', 'lander'], true)) {
            $formula = 'epley';
        }
        $months = max(1, min(24, (int) $request->input('months', 6)));

        $targetUserId = (int) $request->integer('user_id', $user->id);
        if ($targetUserId !== $user->id) {
            if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            if ($user->hasRole(User::ROLE_TRAINER)) {
                $target = User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
        }

        $cacheKey = "stats:1rm:user:{$targetUserId}:ej:" . md5($ejercicioNombre) . ":{$formula}:m{$months}";

        return Cache::remember($cacheKey, 600, fn() => response()->json(
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
        $cacheKey = "dashboard:today:user:{$user->id}";

        return Cache::remember($cacheKey, 60, fn() => response()->json(
            $this->stats->buildDashboardToday($user)
        ));
    }
}
