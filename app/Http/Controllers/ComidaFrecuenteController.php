<?php

namespace App\Http\Controllers;

use App\Models\ComidaFrecuente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComidaFrecuenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $comidas = ComidaFrecuente::where('user_id', $request->user()->id)
            ->orderBy('nombre')
            ->get();

        return response()->json($comidas);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'calorias' => 'required|integer|min:0|max:10000',
            'proteinas' => 'required|integer|min:0|max:1000',
            'carbohidratos' => 'required|integer|min:0|max:1000',
            'grasas' => 'required|integer|min:0|max:1000',
            'porcion' => 'nullable|string|max:50',
        ]);

        $comida = ComidaFrecuente::create(array_merge($data, [
            'user_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Comida frecuente guardada correctamente',
            'comida' => $comida,
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $comida = ComidaFrecuente::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $comida->delete();

        return response()->json([
            'message' => 'Comida frecuente eliminada correctamente',
        ]);
    }
}
