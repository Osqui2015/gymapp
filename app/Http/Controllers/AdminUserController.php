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
        $data = $request->validate([
            'nick' => ['required', 'string', 'max:255', 'unique:users,nick'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
                return redirect()->route('profile.edit')
                    ->withErrors(['trainer_id' => 'El usuario seleccionado no es trainer.'], 'adminUserCreation')
                    ->withInput();
            }
        }

        User::create([
            'nick' => $data['nick'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'trainer_id' => $data['trainer_id'] ?? null,
        ]);

        return redirect()->route('profile.edit')->with('status', 'admin-user-created');
    }
}
