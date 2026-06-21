<?php

namespace App\Http\Controllers;

use App\Models\Progreso;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProgresoController extends Controller
{
    public function index()
    {
        return view('progreso');
    }

    public function obtener(Request $request)
    {
        $user = $request->user();

        $progresos = Progreso::where('user_id', $user->id)
            ->orderBy('fecha', 'asc')
            ->get();

        $ultimo = $progresos->last();

        return response()->json([
            'progresos' => $progresos,
            'ultimo' => $ultimo,
            'puede_registrar' => $this->puedeRegistrar($progresos),
        ]);
    }

    public function puedeRegistrar($progresos)
    {
        $ultimo = $progresos->last();

        if (!$ultimo) {
            return true;
        }

        $diasDesdeUltimo = Carbon::now()->diffInDays($ultimo->fecha);

        return $diasDesdeUltimo >= 14;
    }

    public function guardar(Request $request)
    {
        $user = $request->user();

        $progresos = Progreso::where('user_id', $user->id)
            ->orderBy('fecha', 'asc')
            ->get();

        $rules = [
            'peso' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'altura' => ['nullable', 'numeric', 'min:0.5', 'max:3.0'],
            'edad' => ['nullable', 'integer', 'min:10', 'max:120'],
            'sexo' => ['nullable', 'in:masculino,femenino'],
            'cuello' => ['nullable', 'numeric', 'min:10', 'max:80'],
            'hombros' => ['nullable', 'numeric', 'min:20', 'max:200'],
            'pecho' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'brazos' => ['nullable', 'numeric', 'min:10', 'max:80'],
            'cintura' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'cadera' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'muslos' => ['nullable', 'numeric', 'min:15', 'max:100'],
            'pantorrillas' => ['nullable', 'numeric', 'min:15', 'max:80'],
        ];

        if ($progresos->isEmpty()) {
            $rules['peso'] = ['required', 'numeric', 'min:20', 'max:400'];
            $rules['altura'] = ['required', 'numeric', 'min:0.5', 'max:3.0'];
            $rules['edad'] = ['required', 'integer', 'min:10', 'max:120'];
            $rules['sexo'] = ['required', 'in:masculino,femenino'];
        }

        $data = $request->validate($rules);

        $progreso = Progreso::create([
            'user_id' => $user->id,
            'fecha' => Carbon::now()->toDateString(),
            'peso' => $data['peso'] ?? null,
            'altura' => $data['altura'] ?? null,
            'edad' => $data['edad'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'cuello' => $data['cuello'] ?? null,
            'hombros' => $data['hombros'] ?? null,
            'pecho' => $data['pecho'] ?? null,
            'brazos' => $data['brazos'] ?? null,
            'cintura' => $data['cintura'] ?? null,
            'cadera' => $data['cadera'] ?? null,
            'muslos' => $data['muslos'] ?? null,
            'pantorrillas' => $data['pantorrillas'] ?? null,
        ]);

        $newMedals = AchievementService::checkProgressMilestones($user);

        return response()->json([
            'message' => 'Progreso guardado correctamente',
            'progreso' => $progreso,
            'new_medals' => $newMedals,
        ]);
    }

    public function obtenerDetalle(Request $request)
    {
        $user = $request->user();
        $id = $request->integer('id');

        $progreso = Progreso::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$progreso) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $progresosAnteriores = Progreso::where('user_id', $user->id)
            ->where('fecha', '<', $progreso->fecha)
            ->orderBy('fecha', 'desc')
            ->first();

        $campos = ['peso', 'altura', 'cuello', 'hombros', 'pecho', 'brazos', 'cintura', 'cadera', 'muslos', 'pantorrillas'];
        $comparacion = [];

        foreach ($campos as $campo) {
            if ($progreso->$campo !== null) {
                if ($progresosAnteriores && $progresosAnteriores->$campo !== null) {
                    $comparacion[$campo] = [
                        'anterior' => (float) $progresosAnteriores->$campo,
                        'actual' => (float) $progreso->$campo,
                        'diferencia' => round((float) $progreso->$campo - (float) $progresosAnteriores->$campo, 2),
                    ];
                } else {
                    $comparacion[$campo] = [
                        'anterior' => null,
                        'actual' => (float) $progreso->$campo,
                        'diferencia' => null,
                    ];
                }
            }
        }

        return response()->json([
            'progreso' => $progreso,
            'comparacion' => $comparacion,
        ]);
    }
}