<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorización para Users (Fase 0.1 — basado en RolePermissions).
 *
 * Las reglas se derivan de la matriz de permisos, que es la fuente única
 * de verdad (ver `App\Support\RolePermissions`).
 *
 * Reglas:
 *   - viewAny: quien tenga `gestionar_usuarios` (admin, coordinador, recepcionista).
 *   - view: quien tenga `ver_alumnos` ve a cualquiera; el trainer solo ve
 *     SUS alumnos (mismo trainer_id); alumno/comun solo se ven a sí mismos.
 *   - create: quien tenga `gestionar_usuarios`.
 *   - update: admin puede a cualquiera; recepcionista/coordinador también
 *     (alta, edición de datos); el user puede actualizarse a sí mismo
 *     (los campos admin role/suspended se restringen en el controller,
 *     la policy solo controla "¿puede editar a ESTE user?").
 *   - delete: solo admin (`eliminar_usuarios`). La recepcionista puede
 *     dar de alta y editar, pero NO borrar.
 *   - suspend: solo admin (`suspender_usuarios`).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('gestionar_usuarios');
    }

    public function view(User $user, User $target): bool
    {
        // El usuario siempre se ve a sí mismo.
        if ($user->id === $target->id) {
            return true;
        }

        // El trainer ve SOLO a SUS alumnos (mismo trainer_id), no a los
        // de otros trainers. Es el scoping original de la policy y no se
        // relaja: el trainer tiene `gestionar_alumnos` para trabajar con
        // los suyos, pero el alcance lo define el `trainer_id`.
        if ($user->hasRole(User::ROLE_TRAINER)) {
            return $target->trainer_id === $user->id;
        }

        // Admin, coordinador y recepcionista gestionan usuarios y pueden
        // ver a cualquiera.
        return $user->tienePermiso('gestionar_usuarios');
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('gestionar_usuarios');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->tienePermiso('gestionar_usuarios')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->tienePermiso('eliminar_usuarios');
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->tienePermiso('suspender_usuarios');
    }
}
