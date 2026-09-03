<?php

namespace App\Http\Controllers;

use App\Models\DiarioNutricion;
use App\Services\TdeeService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DiarioNutricionController extends Controller
{
    public function __construct(private TdeeService $tdee) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $fechaStr = $request->input('fecha', Carbon::now()->toDateString());

        // Buscar por fecha truncada (no por timestamp completo) para evitar mismatches
        $diario = DiarioNutricion::where('user_id', $user->id)
            ->whereDate('fecha', $fechaStr)
            ->first();

        if ($diario) {
            $this->authorize('view', $diario);
        } else {
            $this->authorize('create', DiarioNutricion::class);
            $diario = DiarioNutricion::create([
                'user_id' => $user->id,
                'fecha' => $fechaStr,
                'calorias' => 0,
                'proteinas' => 0,
                'carbohidratos' => 0,
                'grasas' => 0,
                'agua_vasos' => 0,
            ]);
        }

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

        $diario = DiarioNutricion::where('user_id', $user->id)
            ->whereDate('fecha', $fechaStr)
            ->first();

        if ($diario) {
            $this->authorize('update', $diario);
            $diario->update($data);
        } else {
            $this->authorize('create', DiarioNutricion::class);
            $diario = DiarioNutricion::create(array_merge($data, [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ]));
        }

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

        $defaults = [
            'calorias' => 0,
            'proteinas' => 0,
            'carbohidratos' => 0,
            'grasas' => 0,
            'agua_vasos' => 0,
        ];

        $diario = DiarioNutricion::where('user_id', $user->id)
            ->whereDate('fecha', $fechaStr)
            ->first();

        if ($diario) {
            $this->authorize('update', $diario);
        } else {
            $this->authorize('create', DiarioNutricion::class);
            $diario = DiarioNutricion::create(array_merge($defaults, [
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ]));
        }

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

    /**
     * Devuelve el cálculo de TDEE y los targets de macros para el user.
     * Si faltan datos (sexo/edad/peso/altura/actividad), los lista en
     * `faltantes` para que la UI le pida al user.
     *
     * GET /api/nutricion/tdee
     */
    public function tdee(Request $request)
    {
        $reporte = $this->tdee->calcular($request->user());
        return response()->json($reporte);
    }

    /**
     * Actualiza los datos de nutrición del user (sexo, edad, actividad,
     * objetivo). El peso y la altura se leen del último Progreso, así
     * que no se aceptan por acá (van por /api/progreso).
     *
     * PATCH /api/nutricion/config
     */
    public function updateConfig(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'sexo' => ['required', 'in:masculino,femenino'],
            'edad' => ['required', 'integer', 'min:10', 'max:120'],
            'nivel_actividad' => ['required', 'in:sedentario,ligero,moderado,activo,muy_activo'],
            'objetivo_nutricional' => ['required', 'in:perder_grasa,mantener,ganar_masa'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Configuración nutricional actualizada',
            'tdee' => $this->tdee->calcular($user->fresh()),
        ]);
    }
}
