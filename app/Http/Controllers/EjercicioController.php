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

        $ejercicios = $query->paginate(20);

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
}
