<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Notificación in-app (la del centro de notificaciones, no los toasts).
 *
 * Diferencia con toasts: las notificaciones PERSISTEN en la DB y el user
 * las puede ver después en el centro (campana). Los toasts son efímeros
 * y desaparecen a los 4-6s.
 *
 * Se puede crear directamente con `Notification::create([...])` o via
 * `Notifiable::notify()` si más adelante querés sumar canales email/SMS.
 */
class Notification extends Model
{
    protected $table = 'notifications';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper: ¿es no leída?
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId);
    }
}
