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
}
