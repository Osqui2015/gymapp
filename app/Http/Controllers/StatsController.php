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
}
