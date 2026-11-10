<?php

namespace App\Policies;

use App\Models\Rutina;
use App\Models\User;
use App\Support\RolePermissions;

/**
 * Autorización granular para Rutinas.
 *
 * La fuente de verdad de los permisos es `App\Support\RolePermissions`
 * (la matriz), NO una lista de roles hardcodeada acá. Antes esta policy
 * comparaba contra `hasRole([...])` con una lista propia que contradecía la
 * matriz: `comun` y `alumno` TIENEN el permiso `crear_rutinas` en
 * `RolePermissions::MATRIZ`, pero la policy los rechazaba.
 *
 * Reglas:
 *   - admin: puede todo
 *   - quien tenga `crear_rutinas`: puede crear rutinas
 *   - trainer / coordinador: puede editar las rutinas que él creó o las
 *     default (created_by null)
 *   - nadie más puede
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
     *
     * Usa el permiso `crear_rutinas` de la matriz: lo tienen admin,
     * trainer, coordinador, alumno y comun.
     */
    public function create(User $user): bool
    {
        return RolePermissions::tiene($user, 'crear_rutinas');
    }

    /**
     * Editar una rutina.
     * - admin puede todo
     * - trainer / coordinador solo las que él creó o las default (created_by null)
     * - el resto (alumno, comun) solo las que él creó
     *
     * Nota: quien creó la rutina puede editarla, aunque la matriz no le dé
     * `gestionar_catalogo`. Es SU rutina; el catálogo oficial sí está
     * protegido.
     */
    public function update(User $user, Rutina $rutina): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return true;
        }

        if ($user->hasRole([User::ROLE_TRAINER, User::ROLE_COORDINADOR])) {
            return $rutina->created_by === null || $rutina->created_by === $user->id;
        }

        // El creador puede editar su propia rutina, pero NO el catálogo
        // oficial (created_by IS NULL).
        return $rutina->created_by !== null && $rutina->created_by === $user->id;
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
        if (! $this->update($user, $rutina)) {
            return false;
        }
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, User::ROLE_COORDINADOR])) {
            return true;
        }
        // trainer: validar que el alumno le pertenece
        if ($user->hasRole(User::ROLE_TRAINER) && $alumnoId) {
            $alumno = User::find($alumnoId);

            return $alumno && $alumno->trainer_id === $user->id;
        }

        return false;
    }
}
