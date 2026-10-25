<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\TrainerTimelineController;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Resuelve el usuario objetivo de un endpoint que puede mostrar datos de
 * "otro" usuario (trainer/admin viendo un alumno).
 *
 * Regla única (la misma de TrainerTimelineController::puedeVerAlumno):
 *   - el propio usuario siempre puede ver lo suyo;
 *   - un admin puede ver cualquier alumno;
 *   - un trainer sólo a los alumnos que tiene asignados;
 *   - cualquier otro caso → 403.
 *
 * Se usa el parametro ?user_id= (opcional). Si no viene, el objetivo es el
 * propio usuario.
 */
trait ResuelveAlumnoObjetivo
{
    protected function usuarioObjetivo(Request $request): int
    {
        $auth = $request->user();
        $targetId = (int) $request->integer('user_id', $auth->id);

        if ($targetId === $auth->id) {
            return $auth->id;
        }

        $target = User::find($targetId);
        if (! $target || ! TrainerTimelineController::puedeVerAlumno($auth, $target)) {
            abort(403, 'No autorizado');
        }

        return $target->id;
    }
}
