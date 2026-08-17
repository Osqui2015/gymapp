<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Crea notificaciones in-app para los users.
 *
 * Uso:
 *   app(NotificationService::class)->notify(
 *       $user,
 *       'trainer_comment',
 *       'Tu trainer te dejó un comentario',
 *       '💬 Nuevo comentario en tu entrenamiento',
 *       ['comment_id' => 123, 'trainer_name' => 'Juan']
 *   );
 */
class NotificationService
{
    /**
     * Crea una notificación para un user.
     *
     * @param User $user
     * @param string $type  Identificador del tipo (ej: 'trainer_comment', 'membership_expiring', 'milestone')
     * @param string $title Título que se muestra en el centro
     * @param string $body  Cuerpo / mensaje corto
     * @param array $data   Metadata extra (URL, IDs, etc.) — disponible en $notif->data
     * @return Notification
     */
    public function notify(User $user, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge([
                'title' => $title,
                'body' => $body,
            ], $data),
        ]);
    }

    /**
     * Notifica a múltiples users (útil para broadcasts del admin).
     */
    public function notifyMany(iterable $users, string $type, string $title, string $body, array $data = []): int
    {
        $count = 0;
        foreach ($users as $user) {
            $this->notify($user, $type, $title, $body, $data);
            $count++;
        }
        return $count;
    }
}
