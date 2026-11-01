<?php

namespace App\Http\Controllers;

use App\Models\BienestarDiario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BienestarController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        $registro = BienestarDiario::where('user_id', $user->id)
            ->whereDate('fecha', $fechaStr)
            ->first();

        if (! $registro) {
            return response()->json([
                'fecha' => $fechaStr,
                'horas_sueno' => null,
                'calidad_sueno' => null,
                'nivel_estres' => null,
                'dolor_muscular' => null,
                'notas' => null,
            ]);
        }

        return response()->json($registro);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        $data = $request->validate([
            'fecha' => 'nullable|date',
            'horas_sueno' => 'nullable|numeric|min:0|max:24',
            'calidad_sueno' => 'nullable|integer|min:1|max:5',
            'nivel_estres' => 'nullable|integer|min:1|max:5',
            'dolor_muscular' => 'nullable|integer|min:1|max:5',
            'notas' => 'nullable|string|max:255',
        ]);

        $attributes = [
            'horas_sueno' => $data['horas_sueno'] ?? null,
            'calidad_sueno' => $data['calidad_sueno'] ?? null,
            'nivel_estres' => $data['nivel_estres'] ?? null,
            'dolor_muscular' => $data['dolor_muscular'] ?? null,
            'notas' => $data['notas'] ?? null,
        ];

        $registro = BienestarDiario::where('user_id', $user->id)
            ->whereDate('fecha', $fechaStr)
            ->first();

        if ($registro) {
            $registro->update($attributes);
        } else {
            $registro = BienestarDiario::create(array_merge($attributes, [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ]));
        }

        return response()->json([
            'message' => 'Registro de bienestar guardado',
            'bienestar' => $registro,
        ]);
    }
}
