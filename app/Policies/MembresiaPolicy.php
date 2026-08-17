<?php

namespace App\Policies;

use App\Models\Membresia;
use App\Models\User;

/**
 * Autorización para Membresías.
 *
 * Reglas:
 *   - viewAny/create/update/renew/delete: solo admin
 *     (gate manage-users cubre; especificamos por instancia para no
 *      depender solo del gate en código de controller)
 *   - view: admin o el alumno dueño de la membresía
 *     (un alumno puede ver SU PROPIA membresía pero no la lista ni las ajenas)
 */
class MembresiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function view(User $user, Membresia $membresia): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        return (int) $membresia->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function update(User $user, Membresia $membresia): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function renew(User $user, Membresia $membresia): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function delete(User $user, Membresia $membresia): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }
}
