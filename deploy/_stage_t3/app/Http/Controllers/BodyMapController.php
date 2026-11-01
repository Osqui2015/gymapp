<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResuelveAlumnoObjetivo;
use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Musculo;
use App\Services\CacheHistorial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    use ResuelveAlumnoObjetivo;

    public function index(Request $request)
    {
        $userId = $this->targetUserId($request);
        $windowDays = max(1, min(730, (int) $request->input('window', 90)));  // últimos 90 días
        $cacheKey = CacheHistorial::key($userId, "body-map:w{$windowDays}");

        $payload = Cache::remember($cacheKey, 300, function () use ($userId, $windowDays) {
            $desde = now()->subDays($windowDays);

            $historiales = Historial::query()
                ->where('user_id', $userId)
                ->where('completado', true)
                ->whereDate('fecha', '>=', $desde)
                ->orderBy('fecha', 'desc')
                // 'completado' tiene que venir en el select: el map de abajo lo lee
                // y, con Model::shouldBeStrict() activo en dev, leer un atributo
                // que no fue recuperado lanza MissingAttributeException (500).
                ->get(['id', 'fecha', 'peso', 'reps_realizadas', 'ejercicio_id', 'ejercicio_nombre', 'user_id', 'completado']);

            // Ejercicios de los sets: por FK si la tienen, si no por nombre.
            $mapa = $this->mapaEjercicios($historiales);

            // Transformar al shape que espera useMuscleLoad.
            // El mapeo de músculos va en un diccionario por ejercicio (no
            // repetido en cada serie): el payload bajaba de ~37KB a ~15KB.
            $items = $historiales->map(function ($h) use ($mapa) {
                // La rutina linkea ejercicios por NOMBRE, así que la FK
                // 'ejercicio_id' suele venir en null. Para el body map hace
                // falta el ejercicio con su mapeo de músculos.
                $ejercicio = $this->resolverEjercicio($h, $mapa);

                return [
                    'id' => $h->id,
                    'fecha' => $h->fecha?->toDateString(),
                    'peso' => (float) $h->peso,
                    'reps_realizadas' => (int) $h->reps_realizadas,
                    'completado' => (bool) $h->completado,
                    'ejercicio_id' => $ejercicio?->id,
                    'ejercicio_nombre' => $ejercicio?->nombre ?? $h->ejercicio_nombre,
                ];
            })->values()->all();

            // Diccionario ejercicio_id → { nombre, musculos[] }
            $ejercicios = $mapa['todos']->keyBy('id')->map(function ($e) {
                return [
                    'nombre' => $e->nombre,
                    'musculos' => $e->musculos->map(function ($m) {
                        return [
                            'musculo_slug' => $m->slug,
                            'tipo' => $m->pivot->tipo,  // 'primario' o 'secundario'
                            'peso' => (float) $m->pivot->peso,
                        ];
                    })->values()->all(),
                ];
            })->all();

            // Catálogo de músculos para que el frontend pueda mapear slug→label
            $musculos = Musculo::orderBy('orden')->get(['id', 'slug', 'nombre_es', 'nombre_en', 'body_part'])
                ->map(fn ($m) => [
                    'slug' => $m->slug,
                    'nombre_es' => $m->nombre_es,
                    'nombre_en' => $m->nombre_en,
                    'body_part' => $m->body_part,
                ])->all();

            return [
                'historiales' => $items,
                'ejercicios' => $ejercicios,
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
        $userId = $this->targetUserId($request);
        $cacheKey = CacheHistorial::key($userId, 'muscle-recency');

        $payload = Cache::remember($cacheKey, 300, function () use ($userId) {
            // Para cada músculo, la última fecha en la que el usuario lo
            // entrenó. Los historiales se guardan con 'ejercicio_nombre'
            // (la rutina linkea por nombre), así que el ejercicio se resuelve
            // por FK o, si no hay, por nombre.
            $historiales = Historial::where('user_id', $userId)
                ->where('completado', true)
                ->get(['fecha', 'ejercicio_id', 'ejercicio_nombre']);

            $mapa = $this->mapaEjercicios($historiales);

            $ultimoPorMusculo = [];
            $nombrePorMusculo = [];

            foreach ($historiales as $h) {
                $ejercicio = $this->resolverEjercicio($h, $mapa);
                if (! $ejercicio) {
                    continue;
                }

                $fecha = $h->fecha?->toDateString();
                if (! $fecha) {
                    continue;
                }

                foreach ($ejercicio->musculos as $m) {
                    $slug = $m->slug;
                    if (! isset($ultimoPorMusculo[$slug]) || $fecha > $ultimoPorMusculo[$slug]) {
                        $ultimoPorMusculo[$slug] = $fecha;
                    }
                    $nombrePorMusculo[$slug] = $m->nombre_es;
                }
            }

            $hoy = now()->startOfDay();
            $recency = collect($ultimoPorMusculo)->map(function ($last, $slug) use ($hoy, $nombrePorMusculo) {
                $lastDia = Carbon::parse($last)->startOfDay();

                return [
                    'slug' => $slug,
                    'nombre_es' => $nombrePorMusculo[$slug] ?? $slug,
                    'last_trained_at' => $last,
                    'days_since' => (int) $lastDia->diffInDays($hoy, false),
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
        $userId = $this->targetUserId($request);
        $tipo = $request->input('tipo'); // 'primario' | 'secundario' | null

        $musculo = Musculo::where('slug', $slug)->first();
        if (! $musculo) {
            return response()->json(['error' => 'Músculo no encontrado'], 404);
        }

        $query = Ejercicio::query()
            ->whereHas('musculos', function ($q) use ($musculo, $tipo) {
                $q->where('musculos.id', $musculo->id);
                if ($tipo) {
                    $q->where('ejercicio_musculos.tipo', $tipo);
                }
            })
            ->where('visibilidad', true);

        $ejercicios = $query->get(['id', 'nombre', 'equipamiento', 'grupo_muscular']);

        // Volumen por ejercicio en últimos 30d.
        // Los historiales se guardan con 'ejercicio_nombre' (la rutina linkea
        // por nombre), así que el volumen se calcula por FK y por nombre:
        // un set cuenta si coincide con alguno de los dos.
        $desde = now()->subDays(30);
        $historiales = Historial::where('user_id', $userId)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $desde)
            ->where(function ($q) use ($ejercicios) {
                $q->whereIn('ejercicio_id', $ejercicios->pluck('id'));
                $q->orWhereIn('ejercicio_nombre', $ejercicios->pluck('nombre')->unique());
            })
            ->get(['ejercicio_id', 'ejercicio_nombre', 'peso']);

        $porId = [];
        $porNombre = [];
        foreach ($historiales as $h) {
            $nombre = $h->ejercicio_nombre;

            $porNombre[$nombre]['sets'] = ($porNombre[$nombre]['sets'] ?? 0) + 1;
            $porNombre[$nombre]['max_peso'] = max($porNombre[$nombre]['max_peso'] ?? 0, (float) $h->peso);

            if ($h->ejercicio_id) {
                $porId[$h->ejercicio_id]['sets'] = ($porId[$h->ejercicio_id]['sets'] ?? 0) + 1;
                $porId[$h->ejercicio_id]['max_peso'] = max($porId[$h->ejercicio_id]['max_peso'] ?? 0, (float) $h->peso);
            }
        }

        $rows = $ejercicios->map(function ($e) use ($porId, $porNombre) {
            $s = $porId[$e->id] ?? ($porNombre[$e->nombre] ?? null);

            return [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'equipamiento' => $e->equipamiento,
                'grupo_muscular' => $e->grupo_muscular,
                'sets_30d' => $s ? (int) $s['sets'] : 0,
                'max_peso_30d' => $s && $s['max_peso'] ? (float) $s['max_peso'] : null,
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

    /**
     * Mapa de ejercicios para resolver los sets del historial: por FK y por
     * nombre (los historiales se guardan con 'ejercicio_nombre').
     *
     * El catálogo tiene nombres homónimos (el mismo ejercicio con distinto
     * equipamiento: "Press de banca" x3). Cuando hay homónimos gana el que
     * tenga más músculos mapeados; desempate por id (el más antiguo).
     *
     * @param  Collection<int, Historial>  $historiales
     * @return array{por_id: Collection, por_nombre: array<string, Ejercicio>}
     */
    private function mapaEjercicios($historiales): array
    {
        $ids = $historiales->pluck('ejercicio_id')->filter()->unique()->values();
        $nombres = $historiales->pluck('ejercicio_nombre')->filter()->unique()->values();

        if ($ids->isEmpty() && $nombres->isEmpty()) {
            return ['por_id' => collect(), 'por_nombre' => [], 'todos' => collect()];
        }

        $ejercicios = Ejercicio::query()
            ->with('musculos')
            ->where(function ($q) use ($ids, $nombres) {
                if ($ids->isNotEmpty()) {
                    $q->whereIn('id', $ids);
                }
                if ($nombres->isNotEmpty()) {
                    $ids->isNotEmpty()
                        ? $q->orWhereIn('nombre', $nombres)
                        : $q->whereIn('nombre', $nombres);
                }
            })
            ->get();

        return [
            'por_id' => $ejercicios->keyBy('id'),
            'por_nombre' => $ejercicios->groupBy('nombre')
                ->map(fn ($grupo) => $grupo
                    ->sortBy(fn ($e) => [-$e->musculos->count(), $e->id])
                    ->first()
                )
                ->all(),
            'todos' => $ejercicios,
        ];
    }

    /**
     * Ejercicio efectivo de un set: por FK si la tiene, si no por nombre.
     */
    private function resolverEjercicio(Historial $h, array $mapa): ?Ejercicio
    {
        if ($h->ejercicio_id && isset($mapa['por_id'][$h->ejercicio_id])) {
            return $mapa['por_id'][$h->ejercicio_id];
        }

        return $mapa['por_nombre'][$h->ejercicio_nombre] ?? null;
    }

    /**
     * Usuario objetivo del body map.
     *
     * Por defecto es el propio usuario. El front manda ?user_id= cuando
     * quien consulta es trainer/admin y está viendo el body map de un alumno.
     *
     * Autorización: mismo criterio que el resto del historial (el usuario
     * ve lo suyo, el trainer sólo a sus alumnos asignados y el admin a
     * cualquiera) — ver ResuelveAlumnoObjetivo.
     */
    private function targetUserId(Request $request): int
    {
        return $this->usuarioObjetivo($request);
    }
}
