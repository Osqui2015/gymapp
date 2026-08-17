<?php

namespace App\Policies;

use App\Models\EjercicioClave;
use App\Models\User;

/**
 * Autorización para EjercicioClave (ejercicios clave asignados por un trainer
 * a un alumno específico).
 *
 * Reglas:
 *   - viewAny: cualquier user autenticado (el controller filtra por
 *     user_id/trainer_id según el rol)
 *   - view: el alumno dueño, su trainer, o admin/coordinador
 *   - create/update/delete: solo el trainer del alumno o admin
 *     (para create se pasa el alumnoId y se valida que le pertenezca;
 *      para update/delete el modelo ya tiene trainer_id)
 */
class EjercicioClavePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EjercicioClave $ejercicioClave): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ($ejercicioClave->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            return $ejercicioClave->trainer_id === $user->id;
        }

        return false;
    }

    public function create(User $user, ?int $alumnoId = null): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER) && $alumnoId !== null) {
            $alumno = User::find($alumnoId);
            return $alumno !== null && (int) $alumno->trainer_id === (int) $user->id;
        }

        return false;
    }

    public function update(User $user, EjercicioClave $ejercicioClave): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        return $user->hasRole(User::ROLE_TRAINER)
            && (int) $ejercicioClave->trainer_id === (int) $user->id;
    }

    public function delete(User $user, EjercicioClave $ejercicioClave): bool
    {
        return $this->update($user, $ejercicioClave);
    }
}
