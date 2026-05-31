<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index(Request $request)
    {
        $query = Ejercicio::query();

        if ($request->has('busqueda') && $request->busqueda) {
            $query->where('nombre', 'like', '%' . $request->busqueda . '%')
                  ->orWhere('equipamiento', 'like', '%' . $request->busqueda . '%');
        }

        $ejercicios = $query->paginate(20);

        return response()->json($ejercicios);
    }

    public function store(Request $request)
    {
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

        return response()->json($ejercicio, 201);
    }

    public function destroy($id)
    {
        $ejercicio = Ejercicio::findOrFail($id);
        $ejercicio->delete();

        return response()->json(['message' => 'Eliminado']);
    }
}
