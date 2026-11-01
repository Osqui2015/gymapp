<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TrainerAlumnoController extends Controller
{
    /**
     * Lista de alumnos visibles para el trainer/admin que hace la request.
     *
     * - Trainers: solo ven sus alumnos asignados (trainer_id = $trainer->id)
     * - Admins: ven todos
     */
    public function index(Request $request)
    {
        $trainer = $request->user();

        $query = User::query()->where('role', User::ROLE_ALUMNO);

        if ($trainer->hasRole(User::ROLE_TRAINER)) {
            $query->where('trainer_id', $trainer->id);
        }

        $alumnos = $query
            ->with(['rutinaSeleccionada.rutina'])
            ->orderBy('name')
            ->get(['id', 'nick', 'name', 'email', 'trainer_id']);

        return response()->json($alumnos);
    }
}
