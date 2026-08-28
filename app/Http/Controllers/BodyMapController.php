<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\Musculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Devuelve los datos crudos que BodyMap.vue necesita para calcular la carga
 * por músculo del usuario. El cálculo real (load, fatigue, strength) lo
 * hace el composable useMuscleLoad en el frontend (es un cálculo puro).
 *
 * Cache por usuario, 5 minutos (los historiales no cambian tan seguido).
 */
class BodyMapController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $windowDays = (int) $request->input('window', 90);  // últimos 90 días
        $cacheKey = "body-map:user:{$userId}:w{$windowDays}";

        $payload = Cache::remember($cacheKey, 300, function () use ($userId, $windowDays) {
            $desde = now()->subDays($windowDays);

            $historiales = Historial::query()
                ->where('user_id', $userId)
                ->where('completado', true)
                ->whereDate('fecha', '>=', $desde)
                ->with(['ejercicioRef.musculos' => function ($q) {
                    $q->withPivot('tipo', 'peso');
                }])
                ->orderBy('fecha', 'desc')
                ->get(['id', 'fecha', 'peso', 'reps_realizadas', 'ejercicio_id', 'ejercicio_nombre', 'user_id']);

            // Transformar al shape que espera useMuscleLoad
            $items = $historiales->map(function ($h) {
                $musculos = ($h->ejercicioRef?->musculos ?? collect())->map(function ($m) {
                    return [
                        'musculo_slug' => $m->slug,
                        'tipo' => $m->pivot->tipo,  // 'primario' o 'secundario'
                        'peso' => (float) $m->pivot->peso,
                    ];
                })->values()->all();

                return [
                    'id' => $h->id,
                    'fecha' => $h->fecha?->toDateString(),
                    'peso' => (float) $h->peso,
                    'reps_realizadas' => (int) $h->reps_realizadas,
                    'completado' => (bool) $h->completado,
                    'ejercicio' => [
                        'id' => $h->ejercicio_id,
                        'nombre' => $h->ejercicioRef?->nombre ?? $h->ejercicio_nombre,
                        'musculos' => $musculos,
                    ],
                ];
            })->values()->all();

            // Catálogo de músculos para que el frontend pueda mapear slug→label
            $musculos = Musculo::orderBy('orden')->get(['id', 'slug', 'nombre_es', 'nombre_en', 'body_part'])
                ->map(fn($m) => [
                    'slug' => $m->slug,
                    'nombre_es' => $m->nombre_es,
                    'nombre_en' => $m->nombre_en,
                    'body_part' => $m->body_part,
                ])->all();

            return [
                'historiales' => $items,
                'musculos' => $musculos,
                'total' => count($items),
                'window_days' => $windowDays,
            ];
        });

        return response()->json($payload);
    }

    /**
     * Devuelve, para cada músculo, cuántos días pasaron desde el último set
     * completado del usuario. Útil para mostrar un modo "no entrenaste" en el
     * body map (músculos en rojo si pasaron muchos días).
     */
    public function muscleRecency(Request $request)
    {
        $userId = $request->user()->id;
        $cacheKey = "muscle-recency:user:{$userId}";

        $payload = Cache::remember($cacheKey, 300, function () use ($userId) {
            // Para cada músculo, el MAX(fecha) de los historiales del user
            // donde ese músculo aparece (vía pivot ejercicio_musculos).
            $rows = \DB::table('historials as h')
                ->join('ejercicio_musculos as em', 'em.ejercicio_id', '=', 'h.ejercicio_id')
                ->join('musculos as m', 'm.id', '=', 'em.musculo_id')
                ->where('h.user_id', $userId)
                ->where('h.completado', true)
                ->whereNotNull('h.ejercicio_id')
                ->groupBy('m.id', 'm.slug', 'm.nombre_es')
                ->selectRaw('m.slug, m.nombre_es, MAX(h.fecha) as last_trained_at')
                ->get();

            $hoy = now()->startOfDay();
            $recency = $rows->map(function ($r) use ($hoy) {
                $last = \Carbon\Carbon::parse($r->last_trained_at)->startOfDay();
                $days = (int) $last->diffInDays($hoy, false);
                return [
                    'slug' => $r->slug,
                    'nombre_es' => $r->nombre_es,
                    'last_trained_at' => $r->last_trained_at,
                    'days_since' => $days,
                ];
            })->values()->all();

            return [
                'recency' => $recency,
                'generated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }
    public function ejerciciosPorMusculo(Request $request, string $slug)
    {
        $userId = $request->user()->id;
        $tipo = $request->input('tipo'); // 'primario' | 'secundario' | null

        $musculo = \App\Models\Musculo::where('slug', $slug)->first();
        if (!$musculo) {
            return response()->json(['error' => 'Músculo no encontrado'], 404);
        }

        $query = \App\Models\Ejercicio::query()
            ->whereHas('musculos', function ($q) use ($musculo, $tipo) {
                $q->where('musculos.id', $musculo->id);
                if ($tipo) $q->where('ejercicio_musculos.tipo', $tipo);
            })
            ->where('visibilidad', true);

        $ejercicios = $query->get(['id', 'nombre', 'equipamiento', 'grupo_muscular']);

        // Volumen por ejercicio en últimos 30d
        $desde = now()->subDays(30);
        $setsPorEj = \App\Models\Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $desde)
            ->whereIn('ejercicio_id', $ejercicios->pluck('id'))
            ->selectRaw('ejercicio_id, count(*) as sets, max(peso) as max_peso')
            ->groupBy('ejercicio_id')
            ->get()
            ->keyBy('ejercicio_id');

        $rows = $ejercicios->map(function ($e) use ($setsPorEj) {
            $s = $setsPorEj[$e->id] ?? null;
            return [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'equipamiento' => $e->equipamiento,
                'grupo_muscular' => $e->grupo_muscular,
                'sets_30d' => $s ? (int) $s->sets : 0,
                'max_peso_30d' => $s && $s->max_peso ? (float) $s->max_peso : null,
            ];
        })
        ->sortByDesc('sets_30d')
        ->values();

        return response()->json([
            'musculo' => [
                'slug' => $musculo->slug,
                'nombre_es' => $musculo->nombre_es,
                'nombre_en' => $musculo->nombre_en,
                'body_part' => $musculo->body_part,
            ],
            'ejercicios' => $rows,
            'total' => $rows->count(),
        ]);
    }
}
