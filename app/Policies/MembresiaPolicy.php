<?php

namespace App\Policies;

use App\Models\Membresia;
use App\Models\User;

/**
 * Autorización para Membresías (Fase 0.1 — basado en RolePermissions).
 *
 * Reglas:
 *   - viewAny/create/update/renew/delete: quien tenga `ver_membresias` /
 *     `registrar_pagos` (admin, coordinador, recepcionista).
 *   - view: quien tenga `ver_membresias` ve cualquiera; el alumno dueño de
 *     la membresía puede ver SU PROPIA membresía pero no la lista ni las ajenas.
 *
 * NOTA: `recepcionista` puede ver y cobrar membresías pero NO ver balances
 * globales (eso es un permiso aparte: `ver_balances`, solo admin).
 */
class MembresiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tienePermiso('ver_membresias');
    }

    public function view(User $user, Membresia $membresia): bool
    {
        if ($user->tienePermiso('ver_membresias')) {
            return true;
        }

        return (int) $membresia->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tienePermiso('registrar_pagos');
    }

    public function update(User $user, Membresia $membresia): bool
    {
        return $user->tienePermiso('registrar_pagos');
    }

    public function renew(User $user, Membresia $membresia): bool
    {
        return $user->tienePermiso('registrar_pagos');
    }

    public function delete(User $user, Membresia $membresia): bool
    {
        return $user->tienePermiso('eliminar_usuarios');
    }
}
