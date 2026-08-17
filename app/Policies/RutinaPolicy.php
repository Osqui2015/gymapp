<?php

namespace App\Policies;

use App\Models\Rutina;
use App\Models\User;

/**
 * Autorización granular para Rutinas.
 *
 * Reglas:
 *   - admin: puede todo
 *   - trainer: puede ver/editar las rutinas que él creó o las default (created_by null)
 *   - alumno: puede ver todas las rutinas, pero no editarlas
 *   - comun: puede ver (rutinas públicas)
 */
class RutinaPolicy
{
    /**
     * Ver la lista de rutinas (cualquier user autenticado).
     */
    public function viewAny(User $user): bool
    {
        return true; // cualquiera logueado puede ver la lista
    }

    /**
     * Ver una rutina específica.
     */
    public function view(User $user, Rutina $rutina): bool
    {
        return true; // públicas para todos los logueados
    }

    /**
     * Crear rutinas nuevas.
     * Solo admin, trainer, coordinador.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, User::ROLE_TRAINER, 'coordinador']);
    }

    /**
     * Editar una rutina.
     * - admin puede todo
     * - trainer solo las que él creó o las default (created_by null)
     * - nadie más puede
     */
    public function update(User $user, Rutina $rutina): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) return true;
        if ($user->hasRole([User::ROLE_TRAINER, 'coordinador'])) {
            return $rutina->created_by === null || $rutina->created_by === $user->id;
        }
        return false;
    }

    /**
     * Borrar una rutina.
     * Mismas reglas que update.
     */
    public function delete(User $user, Rutina $rutina): bool
    {
        return $this->update($user, $rutina);
    }

    /**
     * Asignar la rutina a un alumno.
     * Solo admin, trainer (a sus alumnos), coordinador.
     */
    public function assignTo(User $user, Rutina $rutina, ?int $alumnoId): bool
    {
        if (! $this->update($user, $rutina)) return false;
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) return true;
        // trainer: validar que el alumno le pertenece
        if ($user->hasRole(User::ROLE_TRAINER) && $alumnoId) {
            $alumno = \App\Models\User::find($alumnoId);
            return $alumno && $alumno->trainer_id === $user->id;
        }
        return false;
    }
}
