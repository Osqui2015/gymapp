<?php

namespace App\Http\Controllers;

use App\Models\EjercicioClave;
use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Http\Request;

class EjercicioClaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        // Cualquier user autenticado puede pedir el listado; la policy
        // decide si el target concreto es visible.
        $this->authorize('viewAny', EjercicioClave::class);

        if ($targetUserId !== $user->id) {
            // Construimos un mock no persistido con los campos que la
            // policy `view` necesita (user_id y trainer_id) para autorizar
            // sobre un target que puede no estar aún cargado.
            $target = User::find($targetUserId);
            $this->authorize('view', new EjercicioClave([
                'user_id' => $targetUserId,
                'trainer_id' => $target?->trainer_id,
            ]));
        }

        // Obtener los ejercicios clave del alumno
        $ejerciciosClave = EjercicioClave::where('user_id', $targetUserId)
            ->with('trainer:id,name')
            ->get();

        // Obtener lista de nombres de ejercicios disponibles para autocompletado/dropdown
        $historyExercises = Historial::where('user_id', $targetUserId)
            ->distinct()
            ->pluck('ejercicio_nombre')
            ->toArray();

        $routineExercises = Rutina::distinct()
            ->pluck('ejercicio_nombre')
            ->toArray();

        $todosEjercicios = array_values(array_unique(array_merge($historyExercises, $routineExercises)));
        sort($todosEjercicios);

        return response()->json([
            'ejercicios_clave' => $ejerciciosClave,
            'todos_ejercicios' => $todosEjercicios,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'ejercicio_nombre' => 'required|string|max:255',
            'notas_trainer' => 'nullable|string',
        ]);

        $targetUserId = (int) $data['user_id'];

        // La policy `create` valida que sea admin o el trainer del alumno.
        $this->authorize('create', [EjercicioClave::class, $targetUserId]);

        // Crear o actualizar el ejercicio clave
        $ejercicioClave = EjercicioClave::updateOrCreate(
            [
                'user_id' => $targetUserId,
                'ejercicio_nombre' => $data['ejercicio_nombre'],
            ],
            [
                'trainer_id' => $user->id,
                'notas_trainer' => $data['notas_trainer'] ?? null,
            ]
        );

        $ejercicioClave->load('trainer:id,name');

        return response()->json([
            'message' => 'Ejercicio clave guardado con éxito',
            'ejercicio_clave' => $ejercicioClave,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $ejercicioClave = EjercicioClave::findOrFail($id);

        $this->authorize('delete', $ejercicioClave);

        $ejercicioClave->delete();

        return response()->json([
            'message' => 'Ejercicio clave eliminado con éxito',
        ]);
    }
}
