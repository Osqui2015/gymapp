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
        $users = User::with('trainer:id,name')
            ->orderBy('name')
            ->get(['id', 'nick', 'name', 'email', 'role', 'suspended', 'trainer_id']);

        return response()->json([
            'users' => $users,
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
            'role' => ['required', 'string', 'in:comun,alumno,trainer,administrador'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if ($data['role'] !== User::ROLE_ALUMNO) {
            $updateData['trainer_id'] = null;
        } else {
            $updateData['trainer_id'] = $data['trainer_id'] ?? null;
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
}