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

        // Si se consulta el historial de otro usuario, validar permisos
        if ($targetUserId !== $user->id) {
            if (!$user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($user->hasRole(User::ROLE_TRAINER)) {
                $target = User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
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

        // Validar que solo el entrenador asignado o un admin puedan agregar/editar
        if (!$user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado. Se requiere rol de entrenador o administrador.'], 403);
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $student = User::findOrFail($targetUserId);
            if ($student->trainer_id !== $user->id) {
                return response()->json(['error' => 'No autorizado. Este alumno no está asignado a tu tutela.'], 403);
            }
        }

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
        $user = $request->user();
        $ejercicioClave = EjercicioClave::findOrFail($id);

        // Validar que solo el entrenador asignado o un admin puedan borrar
        if (!$user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            if ($ejercicioClave->trainer_id !== $user->id) {
                // Si el entrenador cambió, permitir también al nuevo entrenador del alumno
                $student = User::findOrFail($ejercicioClave->user_id);
                if ($student->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado. No eres el entrenador de este alumno.'], 403);
                }
            }
        }

        $ejercicioClave->delete();

        return response()->json([
            'message' => 'Ejercicio clave eliminado con éxito',
        ]);
    }
}
