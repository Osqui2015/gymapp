<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Http\Request;

class TrainerAlumnoController extends Controller
{
    public function index(Request $request)
    {
        $trainer = $request->user();

        $query = User::query()->where('role', User::ROLE_ALUMNO);

        if ($trainer->hasRole(User::ROLE_TRAINER)) {
            $query->where('trainer_id', $trainer->id);
        }

        $alumnos = $query
            ->with(['rutinaSeleccionada'])
            ->orderBy('name')
            ->get(['id', 'nick', 'name', 'email', 'trainer_id']);

        return response()->json($alumnos);
    }

    public function asignarRutina(Request $request, User $alumno)
    {
        $trainer = $request->user();

        if (! $alumno->hasRole(User::ROLE_ALUMNO)) {
            return response()->json(['error' => 'El usuario objetivo no es alumno'], 422);
        }

        if ($trainer->hasRole(User::ROLE_TRAINER) && $alumno->trainer_id !== $trainer->id) {
            return response()->json(['error' => 'No puedes asignar a un alumno fuera de tu cargo'], 403);
        }

        $data = $request->validate([
            'nivel' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'string', 'max:255'],
            'dia_actual' => ['nullable', 'string', 'max:255'],
        ]);

        $asignacion = UserRutina::updateOrCreate(
            ['user_id' => $alumno->id],
            [
                'nivel' => $data['nivel'],
                'modalidad' => $data['modalidad'],
                'dia_actual' => $data['dia_actual'] ?? 'Día 1',
                'assigned_by' => $trainer->id,
            ]
        );

        return response()->json($asignacion);
    }
}
