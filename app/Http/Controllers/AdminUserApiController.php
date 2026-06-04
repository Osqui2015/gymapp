<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserApiController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = User::with('trainer:id,name')
            ->orderBy('name');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nick', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage, ['id', 'nick', 'name', 'email', 'telefono', 'role', 'suspended', 'trainer_id']);

        return response()->json([
            'users' => $users->items(),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'trainers' => User::where('role', User::ROLE_TRAINER)
                ->orWhere('role', User::ROLE_ADMINISTRADOR)
                ->orderBy('name')
                ->get(['id', 'name']),
            'current_user_id' => $request->user()->id,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'telefono' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:comun,alumno,trainer,administrador'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'role' => $data['role'],
        ];

        if ($data['role'] !== User::ROLE_ALUMNO) {
            $updateData['trainer_id'] = null;
        } else {
            $updateData['trainer_id'] = $data['trainer_id'] ?? null;
            if (! empty($updateData['trainer_id'])) {
                $trainer = User::findOrFail($updateData['trainer_id']);
                if (! $trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                    return response()->json([
                        'message' => 'El usuario seleccionado no es trainer.',
                        'errors' => ['trainer_id' => ['El usuario seleccionado no es trainer.']]
                    ], 422);
                }
            }
        }

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return response()->json(['success' => true, 'user' => $user]);
    }

    public function toggleSuspend(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes suspender tu propia cuenta'], 403);
        }

        $user->update(['suspended' => ! $user->suspended]);

        return response()->json(['success' => true, 'suspended' => $user->suspended]);
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes eliminar tu propia cuenta'], 403);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }

    public function getTrainersAndAlumnos(Request $request)
    {
        $trainers = User::query()
            ->whereIn('role', [User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'role']);

        $alumnos = User::query()
            ->where('role', User::ROLE_ALUMNO)
            ->with('trainer:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'email', 'trainer_id']);

        return response()->json([
            'trainers' => $trainers,
            'alumnos' => $alumnos,
        ]);
    }

    public function assignAlumnosToTrainer(Request $request, int $trainerId)
    {
        $trainer = User::findOrFail($trainerId);

        if (!$trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'El usuario seleccionado no es un entrenador válido'], 422);
        }

        $data = $request->validate([
            'alumno_ids' => ['nullable', 'array'],
            'alumno_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $alumnoIds = $data['alumno_ids'] ?? [];

        if (!empty($alumnoIds)) {
            $invalidCount = User::whereIn('id', $alumnoIds)
                ->where('role', '!=', User::ROLE_ALUMNO)
                ->count();
            if ($invalidCount > 0) {
                return response()->json(['error' => 'Uno o más usuarios seleccionados no son alumnos válidos'], 422);
            }
        }

        \DB::transaction(function () use ($trainerId, $alumnoIds) {
            User::where('trainer_id', $trainerId)
                ->whereNotIn('id', $alumnoIds)
                ->update(['trainer_id' => null]);

            if (!empty($alumnoIds)) {
                User::whereIn('id', $alumnoIds)
                    ->update(['trainer_id' => $trainerId]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Alumnos asignados correctamente al entrenador.',
        ]);
    }
}