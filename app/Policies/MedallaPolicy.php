<?php

namespace App\Policies;

use App\Models\MedallaUsuario;
use App\Models\User;

/**
 * Autorización para MedallaUsuario (logros/medallas del user).
 *
 * Reglas:
 *   - viewAny/view: el user dueño, su trainer, o admin/coordinador
 *
 * NO se permite create/update/delete manual: las medallas se otorgan
 * automáticamente vía AchievementService (ver App\Services\AchievementService).
 * Esta policy existe solo para que el controller pueda usar
 * `$this->authorize('view', $medalla)` consistentemente.
 */
class MedallaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MedallaUsuario $medallaUsuario): bool
    {
        if ($user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador'])) {
            return true;
        }

        if ((int) $medallaUsuario->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(User::ROLE_TRAINER)) {
            $owner = User::find($medallaUsuario->user_id);
            return $owner !== null && (int) $owner->trainer_id === (int) $user->id;
        }

        return false;
    }
}
