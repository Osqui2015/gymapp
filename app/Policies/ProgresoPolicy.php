<?php

namespace App\Policies;

use App\Models\Progreso;
use App\Models\User;

/**
 * Autorización para Progreso (mediciones antropométricas del alumno).
 *
 * Reglas:
 *   - viewAny: cualquier user autenticado (el controller filtra por user_id
 *     según el rol: alumno ve las suyas, trainer las de sus alumnos, admin todas)
 *   - view/create/update/delete: el alumno dueño, su trainer, o admin/coordinador
 */
class ProgresoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Progreso $progreso): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ((int) $progreso->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($progreso->user_id);
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

    public function update(User $user, Progreso $progreso): bool
    {
        return $this->view($user, $progreso);
    }

    public function delete(User $user, Progreso $progreso): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        if ((int) $progreso->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($progreso->user_id);
            return $owner !== null && (int) $owner->trainer_id === (int) $user->id;
        }

        return false;
    }
}
