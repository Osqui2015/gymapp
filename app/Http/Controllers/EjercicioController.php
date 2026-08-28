<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\Musculo;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index(Request $request)
    {
        // Las rutas /ejercicios, /ejercicios/grupos-musculares y
        // /ejercicios/equipamientos son públicas (sin middleware de auth),
        // por lo que no se invoca authorize() — la policy viewAny/view es
        // return true y los tests existentes llaman sin autenticar.

        $userId = $request->user()?->id;

        $query = Ejercicio::query()
            // Cargar músculos para que el body map en /ejercicios pueda
            // iluminar las partes trabajadas al seleccionar un ejercicio.
            // Select acotado para no inflar el response.
            ->with(['musculos:id,slug,nombre_es']);

        if ($request->has('busqueda') && $request->busqueda) {
            $busqueda = $request->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'like', '%' . $busqueda . '%')
                  ->orWhere('equipamiento', 'like', '%' . $busqueda . '%');
            });
        }

        if ($request->has('grupo_muscular') && $request->grupo_muscular) {
            $query->where('grupo_muscular', $request->grupo_muscular);
        }

        if ($request->has('equipamiento') && $request->equipamiento) {
            $query->where('equipamiento', $request->equipamiento);
        }

        // Filtro por músculo (usado cuando el usuario hace click en una
        // parte del body map). Filtra por la pivot ejercicio_musculos.
        if ($request->has('musculo_slug') && $request->musculo_slug) {
            $slug = $request->musculo_slug;
            $query->whereHas('musculos', function ($q) use ($slug) {
                $q->where('musculos.slug', $slug);
            });
        }

        // Si hay user autenticado, agregamos last_trained_at + is_favorite
        // con subqueries (evita N+1). Para rutas públicas devuelve null/false.
        if ($userId) {
            $lastTrainedSub = \DB::table('historials')
                ->select('ejercicio_id', \DB::raw('MAX(fecha) as last_trained_at'))
                ->where('user_id', $userId)
                ->where('completado', true)
                ->whereNotNull('ejercicio_id')
                ->groupBy('ejercicio_id');

            $favoriteSub = \DB::table('ejercicio_favoritos')
                ->select('ejercicio_id')
                ->where('user_id', $userId);

            $query->addSelect([
                'ejercicios.*',
                'last_trained_at' => \DB::table('historials')
                    ->select('fecha')
                    ->whereColumn('ejercicio_id', 'ejercicios.id')
                    ->where('user_id', $userId)
                    ->where('completado', true)
                    ->orderByDesc('fecha')
                    ->limit(1),
                'is_favorite' => \DB::table('ejercicio_favoritos')
                    ->selectRaw('1')
                    ->whereColumn('ejercicio_id', 'ejercicios.id')
                    ->where('user_id', $userId)
                    ->limit(1),
            ]);
        }

        $ejercicios = $query->paginate(20);

        // Convertir is_favorite a boolean (Laravel lo deja como null/1)
        if ($userId) {
            $ejercicios->getCollection()->transform(function ($ej) {
                $ej->is_favorite = (bool) $ej->is_favorite;
                return $ej;
            });
        }

        return response()->json($ejercicios);
    }

    public function gruposMusculares()
    {
        $grupos = Ejercicio::whereNotNull('grupo_muscular')
            ->where('grupo_muscular', '!=', '')
            ->distinct()
            ->orderBy('grupo_muscular')
            ->pluck('grupo_muscular');

        return response()->json($grupos);
    }

    public function equipamientos()
    {
        $equipamientos = Ejercicio::whereNotNull('equipamiento')
            ->where('equipamiento', '!=', '')
            ->distinct()
            ->orderBy('equipamiento')
            ->pluck('equipamiento');

        return response()->json($equipamientos);
    }

    /**
     * Catálogo de músculos canónicos (slug → nombre_es).
     * Usado por la página de Ejercicios para los tooltips del body map.
     * Es estático y seguro de cachear.
     */
    public function musculos()
    {
        $musculos = Musculo::orderBy('orden')
            ->get(['id', 'slug', 'nombre_es', 'nombre_en', 'body_part', 'orden'])
            ->map(fn($m) => [
                'slug' => $m->slug,
                'nombre_es' => $m->nombre_es,
                'nombre_en' => $m->nombre_en,
                'body_part' => $m->body_part,
                'orden' => (int) $m->orden,
            ]);

        return response()->json($musculos);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ejercicio::class);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'equipamiento' => 'required|string|max:255',
            'url_img' => 'nullable|string',
            'url_video' => 'nullable|string',
            'visibilidad' => 'boolean',
            'grupo_muscular' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $ejercicio = Ejercicio::create($data);

        AuditLog::forModel($ejercicio, 'created', null, $data);

        return response()->json($ejercicio, 201);
    }

    public function destroy($id)
    {
        $ejercicio = Ejercicio::findOrFail($id);

        $this->authorize('delete', $ejercicio);

        $ejercicioData = $ejercicio->toArray();
        $ejercicio->delete();

        AuditLog::log('deleted', "Eliminó ejercicio {$ejercicioData['nombre']}", auth()->id(), Ejercicio::class, $id, $ejercicioData, null);

        return response()->json(['message' => 'Eliminado']);
    }

    /**
     * Toggle favorito del user actual sobre un ejercicio.
     * POST /api/ejercicios/{id}/favorite → si no existe lo crea, si existe lo borra.
     */
    public function toggleFavorite(Request $request, $id)
    {
        $ejercicio = Ejercicio::findOrFail($id);
        $userId = $request->user()->id;

        $favorito = \App\Models\EjercicioFavorito::where('user_id', $userId)
            ->where('ejercicio_id', $ejercicio->id)
            ->first();

        if ($favorito) {
            $favorito->delete();
            return response()->json(['is_favorite' => false]);
        }

        \App\Models\EjercicioFavorito::create([
            'user_id' => $userId,
            'ejercicio_id' => $ejercicio->id,
        ]);

        return response()->json(['is_favorite' => true]);
    }

    /**
     * Quick log: crea un historial rapido para HOY con 1 set vacio
     * (peso=0, reps=0, completado=true). Sirve para que el ejercicio
     * aparezca como 'Hoy' en la lista y se sume al body map, sin tener
     * que pasar por el flujo completo de series del dashboard.
     *
     * El user despues puede ir al dashboard y editar los sets reales.
     */
    public function quickLog(Request $request, $id)
    {
        $ejercicio = Ejercicio::findOrFail($id);
        $userId = $request->user()->id;

        // Mapear día de la semana al string corto que usa la tabla
        $dias = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
        $hoy = $dias[now()->dayOfWeekIso - 1]; // 1=lunes, 7=domingo

        $historial = \App\Models\Historial::create([
            'user_id' => $userId,
            'ejercicio_id' => $ejercicio->id,
            'ejercicio_nombre' => $ejercicio->nombre,
            'rutina_nombre' => 'Quick log',
            'dia' => $hoy,
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '0',
            'reps_max' => '0',
            'reps_realizadas' => 0,
            'peso' => 0,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        return response()->json([
            'ok' => true,
            'historial_id' => $historial->id,
        ]);
    }
}
