<?php

namespace App\Http\Controllers;

use App\Models\UserRutina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRutinaController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $data = $request->validate([
            'nivel' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'string', 'max:255'],
            'dia_actual' => ['nullable', 'string', 'max:255'],
        ]);

        $userRutina = UserRutina::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nivel' => $data['nivel'],
                'modalidad' => $data['modalidad'],
                'dia_actual' => $data['dia_actual'] ?? 'Día 1',
            ]
        );

        return response()->json($userRutina);
    }

    public function show(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $userRutina = UserRutina::where('user_id', $user->id)->first();

        return response()->json($userRutina);
    }

    public function updateDia(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $userRutina = UserRutina::where('user_id', $user->id)->first();

        if (!$userRutina) {
            return response()->json(['error' => 'Rutina no encontrada'], 404);
        }

        $data = $request->validate([
            'dia_actual' => ['required', 'string', 'max:255'],
        ]);

        $userRutina->dia_actual = $data['dia_actual'];
        $userRutina->save();

        return response()->json($userRutina);
    }
}