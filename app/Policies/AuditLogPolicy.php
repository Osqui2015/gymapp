<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Autorización para AuditLog (bitácora de auditoría del sistema).
 *
 * Reglas:
 *   - viewAny/view: solo admin o coordinador (gate view-audit-logs)
 *
 * Los AuditLog son append-only (solo se crean, no se editan ni borran).
 * No hay create/update/delete definidos — si en el futuro se necesita
 * purga programada, debería ser un job del sistema, no algo accesible
 * por HTTP.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador']);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole([User::ROLE_ADMINISTRADOR, 'coordinador']);
    }
}
