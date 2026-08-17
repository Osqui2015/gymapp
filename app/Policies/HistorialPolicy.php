<?php

namespace App\Policies;

use App\Models\Historial;
use App\Models\User;

/**
 * Autorización para Historial (registros de entrenamiento).
 *
 * Reglas:
 *   - admin/coordinador: pueden ver/editar cualquier historial
 *   - trainer: puede ver los historiales de SUS alumnos, no editar
 *   - alumno: puede crear/ver/editar SOLO su propio historial
 *   - comun: no tiene historial propio (debería ser alumno siempre)
 */
class HistorialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Historial $historial): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) return true;
        if ($historial->user_id === $user->id) return true;
        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = \App\Models\User::find($historial->user_id);
            return $owner && $owner->trainer_id === $user->id;
        }
        return false;
    }

    public function create(User $user): bool
    {
        // Solo alumnos (y roles que tengan membresía activa) pueden crear historial
        return $user->hasRole([User::ROLE_ALUMNO, User::ROLE_COMUN, User::ROLE_ADMINISTRADOR]);
    }

    public function update(User $user, Historial $historial): bool
    {
        // Solo el dueño puede editar SU propio historial
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) return true;
        return $historial->user_id === $user->id;
    }

    public function delete(User $user, Historial $historial): bool
    {
        return $this->update($user, $historial);
    }
}
