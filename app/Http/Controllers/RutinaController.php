<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use Illuminate\Http\Request;

class RutinaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Rutina::query();

        // Only show default routines (created_by is null) OR custom routines created by this user
        $query->where(function ($q) use ($user) {
            $q->whereNull('created_by');
            if ($user) {
                $q->orWhere('created_by', $user->id);
            }
        });

        if ($request->has('nivel') && $request->nivel) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->has('modalidad') && $request->modalidad) {
            $query->where('modalidad', $request->modalidad);
        }
        if ($request->has('dia') && $request->dia) {
            $query->where('dia', $request->dia);
        }

        $query->with('ejercicio')->orderBy('orden');

        $rutinas = $query->get();

        return response()->json($rutinas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'dia' => 'required|string',
            'ejercicio_nombre' => 'required|string',
            'series' => 'required|integer',
            'reps_min' => 'required|string',
            'reps_max' => 'required|string',
            'descanso_min' => 'required|numeric',
            'orden' => 'required|integer',
        ]);

        $data['created_by'] = $request->user()->id;

        $rutina = Rutina::create($data);

        return response()->json($rutina, 201);
    }
}
