<?php

namespace App\Policies;

use App\Models\DiarioNutricion;
use App\Models\User;

/**
 * Autorización para DiarioNutricion (registro diario de alimentación del alumno).
 *
 * Reglas:
 *   - viewAny: cualquier user autenticado (el controller filtra por user_id
 *     según el rol: alumno ve los suyos, trainer los de sus alumnos, admin todos)
 *   - view/create/update/delete: el alumno dueño, su trainer, o admin/coordinador
 */
class DiarioNutricionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DiarioNutricion $diarioNutricion): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ((int) $diarioNutricion->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($diarioNutricion->user_id);
            return $owner !== null && (int) $owner->trainer_id === (int) $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([
            User::ROLE_ADMINISTRADOR,
            User::ROLE_ALUMNO,
            User::ROLE_COMUN,
            User::ROLE_TRAINER,
            'coordinador',
        ]);
    }

    public function update(User $user, DiarioNutricion $diarioNutricion): bool
    {
        return $this->view($user, $diarioNutricion);
    }

    public function delete(User $user, DiarioNutricion $diarioNutricion): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        if ((int) $diarioNutricion->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($diarioNutricion->user_id);
            return $owner !== null && (int) $owner->trainer_id === (int) $user->id;
        }

        return false;
    }
}
