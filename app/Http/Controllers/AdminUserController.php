<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'nick' => ['required', 'string', 'max:255', 'unique:users,nick'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:comun,alumno,trainer,administrador'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($data['role'] !== User::ROLE_ALUMNO) {
            $data['trainer_id'] = null;
        }

        if (! empty($data['trainer_id'])) {
            $trainer = User::findOrFail($data['trainer_id']);
            if (! $trainer->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'El usuario seleccionado no es trainer.',
                        'errors' => ['trainer_id' => ['El usuario seleccionado no es trainer.']]
                    ], 422);
                }
                return redirect()->route('profile.edit')
                    ->withErrors(['trainer_id' => 'El usuario seleccionado no es trainer.'], 'adminUserCreation')
                    ->withInput();
            }
        }

        User::create([
            'nick' => $data['nick'],
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'trainer_id' => $data['trainer_id'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
            ], 201);
        }

        return redirect()->route('profile.edit')->with('status', 'admin-user-created');
    }
}
