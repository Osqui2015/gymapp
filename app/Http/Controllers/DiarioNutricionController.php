<?php

namespace App\Http\Controllers;

use App\Models\DiarioNutricion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiarioNutricionController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        $diario = DiarioNutricion::firstOrCreate(
            [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ],
            [
                'calorias' => 0,
                'proteinas' => 0,
                'carbohidratos' => 0,
                'grasas' => 0,
                'agua_vasos' => 0,
            ]
        );

        return response()->json($diario);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        $data = $request->validate([
            'calorias' => 'required|integer|min:0',
            'proteinas' => 'required|integer|min:0',
            'carbohidratos' => 'required|integer|min:0',
            'grasas' => 'required|integer|min:0',
        ]);

        $diario = DiarioNutricion::updateOrCreate(
            [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ],
            $data
        );

        return response()->json([
            'message' => 'Diario nutricional actualizado',
            'diario' => $diario,
        ]);
    }

    public function updateAgua(Request $request)
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        $data = $request->validate([
            'accion' => 'required|string|in:incrementar,decrementar',
        ]);

        $diario = DiarioNutricion::firstOrCreate(
            [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ],
            [
                'calorias' => 0,
                'proteinas' => 0,
                'carbohidratos' => 0,
                'grasas' => 0,
                'agua_vasos' => 0,
            ]
        );

        if ($data['accion'] === 'incrementar') {
            $diario->increment('agua_vasos');
        } else {
            if ($diario->agua_vasos > 0) {
                $diario->decrement('agua_vasos');
            }
        }

        $diario->refresh();

        return response()->json([
            'message' => 'Contador de agua actualizado',
            'diario' => $diario,
        ]);
    }
}
