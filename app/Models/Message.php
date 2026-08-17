<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function scopeUnreadFor($query, $userId)
    {
        return $query->where('recipient_id', $userId)->whereNull('read_at');
    }

    public function scopeConversationWith($query, $userId, $otherId)
    {
        return $query->where(function ($q) use ($userId, $otherId) {
            $q->where(function ($q2) use ($userId, $otherId) {
                $q2->where('sender_id', $userId)->where('recipient_id', $otherId);
            })->orWhere(function ($q2) use ($userId, $otherId) {
                $q2->where('sender_id', $otherId)->where('recipient_id', $userId);
            });
        });
    }
}
