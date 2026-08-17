<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRutinaController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // D1: `rutina_id` es ahora la única fuente de verdad para nivel/modalidad.
        // Las columnas denormalizadas fueron dropeadas (migración 2026_08_17_000000).
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'rutina_id' => ['required', 'integer', 'exists:rutinas,id'],
            'dia_actual' => ['nullable', 'string', 'max:255'],
        ]);

        $targetUser = $user;
        if (! empty($data['user_id'])) {
            $targetUser = User::findOrFail($data['user_id']);

            if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado para asignar a otro usuario'], 403);
            }

            if ($user->hasRole(User::ROLE_TRAINER) && $targetUser->trainer_id !== $user->id) {
                return response()->json(['error' => 'Solo puedes asignar rutinas a tus alumnos'], 403);
            }
        }

        if ($user->hasRole(User::ROLE_ALUMNO) && $targetUser->id === $user->id) {
            return response()->json(['error' => 'Los alumnos no pueden autoasignarse rutinas'], 403);
        }

        $userRutina = UserRutina::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'rutina_id' => $data['rutina_id'],
                'dia_actual' => $data['dia_actual'] ?? 'Día 1',
                'assigned_by' => $targetUser->id === $user->id ? null : $user->id,
            ]
        );

        return response()->json($userRutina->load('rutina'));
    }

    public function show(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id) {
            if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($user->hasRole(User::ROLE_TRAINER)) {
                $target = User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
        }

        $userRutina = UserRutina::with('rutina')->where('user_id', $targetUserId)->first();

        return response()->json($userRutina);
    }

    public function updateDia(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($user->hasRole(User::ROLE_ALUMNO)) {
            // Los alumnos pueden actualizar su progreso diario.
        }

        $userRutina = UserRutina::where('user_id', $user->id)->first();

        if (! $userRutina) {
            return response()->json(['error' => 'Rutina no encontrada'], 404);
        }

        $data = $request->validate([
            'dia_actual' => ['required', 'string', 'max:255'],
        ]);

        $userRutina->dia_actual = $data['dia_actual'];
        $userRutina->save();

        return response()->json($userRutina);
    }

    /**
     * Obtener alumnos del trainer con sus rutinas asignadas
     */
    public function misAlumnos(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $alumnos = User::where('trainer_id', $user->id)
            ->with(['rutinaSeleccionada.rutina'])
            ->get();

        return response()->json($alumnos);
    }

    /**
     * Obtener todas las rutinas creadas por el trainer para asignar
     */
    public function misRutinas(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $rutinas = Rutina::where('created_by', $user->id)
            ->with('ejercicio')
            ->orderBy('nivel')
            ->orderBy('modalidad')
            ->orderBy('dia')
            ->get();

        return response()->json($rutinas);
    }

    /**
     * Asignar una rutina específica a un alumno
     */
    public function asignarRutina(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'alumno_id' => ['required', 'integer', 'exists:users,id'],
            'rutina_id' => ['required', 'integer', 'exists:rutinas,id'],
            'dia_actual' => ['nullable', 'string', 'max:255'],
        ]);

        $alumno = User::findOrFail($data['alumno_id']);

        // Verificar que el trainer tiene permisos sobre el alumno
        if ($user->hasRole(User::ROLE_TRAINER) && $alumno->trainer_id !== $user->id) {
            return response()->json(['error' => 'Solo puedes asignar rutinas a tus alumnos'], 403);
        }

        // Verificar que la rutina pertenece al trainer o es una rutina por defecto
        $rutina = Rutina::findOrFail($data['rutina_id']);
        if ($rutina->created_by !== null && $rutina->created_by !== $user->id) {
            return response()->json(['error' => 'No puedes asignar rutinas de otro trainer'], 403);
        }

        // Asignar la rutina al alumno (D1: nivel/modalidad se leen de la relación)
        $userRutina = UserRutina::updateOrCreate(
            ['user_id' => $alumno->id],
            [
                'rutina_id' => $rutina->id,
                'dia_actual' => $data['dia_actual'] ?? 'Día 1',
                'assigned_by' => $user->id,
            ]
        );

        return response()->json([
            'message' => 'Rutina asignada correctamente',
            'user_rutina' => $userRutina->load('rutina'),
        ]);
    }
}