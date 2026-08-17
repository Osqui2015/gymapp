<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

/**
 * Autorización para Mensajes.
 *
 * Reglas:
 *   - solo el sender y el recipient pueden ver/leer un mensaje
 *   - admin puede ver todos los mensajes (para moderación)
 */
class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        if ($user->hasRole(User::ROLE_ADMINISTRADOR)) return true;
        return $message->sender_id === $user->id || $message->recipient_id === $user->id;
    }

    public function send(User $user, int $recipientId): bool
    {
        // Cualquier user logueado puede mensajear (excepto a sí mismo)
        return $recipientId !== $user->id;
    }

    public function markRead(User $user, Message $message): bool
    {
        // Solo el recipient puede marcar como leído
        return $message->recipient_id === $user->id;
    }
}
