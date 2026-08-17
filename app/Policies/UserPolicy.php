<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorización para Users.
 *
 * Reglas:
 *   - viewAny: solo admin (gate manage-users)
 *   - view: admin/coordinador ven a cualquiera; trainer solo ve SUS alumnos
 *     (mismo trainer_id); alumno/comun solo se ven a sí mismos
 *   - update: admin puede a cualquiera; el user puede actualizarse a sí mismo
 *     (los campos admin role/suspended se restringen en el FormRequest,
 *     la policy solo controla "¿puede editar a ESTE user?")
 *   - delete: solo admin
 *   - suspend: solo admin
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ($user->id === $target->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            return $target->trainer_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->hasRole(User::ROLE_ADMINISTRADOR);
    }
}
