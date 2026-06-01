<?php

namespace App\Http\Controllers;

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

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'nivel' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'string', 'max:255'],
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
                'nivel' => $data['nivel'],
                'modalidad' => $data['modalidad'],
                'dia_actual' => $data['dia_actual'] ?? 'Día 1',
                'assigned_by' => $targetUser->id === $user->id ? null : $user->id,
            ]
        );

        return response()->json($userRutina);
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

        $userRutina = UserRutina::where('user_id', $targetUserId)->first();

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
}