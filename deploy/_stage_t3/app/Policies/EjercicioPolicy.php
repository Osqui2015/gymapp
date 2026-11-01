<?php

namespace App\Policies;

use App\Models\Ejercicio;
use App\Models\User;

/**
 * Autorización para Ejercicios (biblioteca de ejercicios).
 *
 * Reglas:
 *   - viewAny/view: cualquier user autenticado puede ver
 *   - create/update/delete: solo admin o trainer (mantienen la biblioteca)
 */
class EjercicioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ejercicio $ejercicio): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, User::ROLE_TRAINER]);
    }

    public function update(User $user, Ejercicio $ejercicio): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, User::ROLE_TRAINER]);
    }

    public function delete(User $user, Ejercicio $ejercicio): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, User::ROLE_TRAINER]);
    }
}
