<?php

namespace App\Policies;

use App\Models\Meta;
use App\Models\User;

/**
 * Autorización para Metas (objetivos personales del alumno).
 *
 * Reglas:
 *   - viewAny: cualquier user autenticado (el controller filtra por user_id
 *     según el rol: alumno ve las suyas, trainer las de sus alumnos, admin todas)
 *   - view: el alumno dueño, su trainer, o admin/coordinador
 *   - create: admin o el alumno (crea metas para sí mismo; el controller
 *     setea user_id = $user->id)
 *   - update/delete: el alumno dueño o admin
 */
class MetaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Meta $meta): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ($meta->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($meta->user_id);
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
        ]);
    }

    public function update(User $user, Meta $meta): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        return (int) $meta->user_id === (int) $user->id;
    }

    public function delete(User $user, Meta $meta): bool
    {
        return $this->update($user, $meta);
    }
}
