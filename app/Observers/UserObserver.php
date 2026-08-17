<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Registra cambios críticos en User en el audit log.
 * Especialmente: suspensión, cambio de rol, asignación de trainer.
 */
class UserObserver
{
    private const TRACKED_FIELDS = [
        'role', 'suspended', 'trainer_id', 'name', 'email', 'nick',
    ];

    public function created(User $user): void
    {
        AuditLog::forModel($user, 'created', null, $user->getAttributes());
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $old = [];
        $new = [];
        foreach (self::TRACKED_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $old[$field] = $user->getOriginal($field);
                $new[$field] = $user->getAttribute($field);
            }
        }
        if (empty($new)) return;

        // Loguear con descripción más clara para acciones críticas
        $action = 'updated';
        $description = null;
        if (isset($new['suspended'])) {
            $action = $new['suspended'] ? 'suspended' : 'unsuspended';
            $description = "Usuario {$user->nick} {$action}";
        } elseif (isset($new['role'])) {
            $action = 'role_changed';
            $description = "{$user->nick}: rol cambió de '{$old['role']}' a '{$new['role']}'";
        } elseif (isset($new['trainer_id'])) {
            $action = 'trainer_assigned';
            $description = "Trainer asignado a {$user->nick}";
        }

        AuditLog::log(
            $action,
            $description ?? "User {$user->nick} updated",
            auth()->id(),
            User::class,
            $user->id,
            $old,
            $new
        );
    }

    public function deleted(User $user): void
    {
        AuditLog::forModel($user, 'deleted', $user->getAttributes(), null);
    }
}
