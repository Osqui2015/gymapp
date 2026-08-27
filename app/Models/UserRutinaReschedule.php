<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRutinaReschedule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_rutina_id',
        'from_day',
        'to_day',
        'reason',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userRutina(): BelongsTo
    {
        return $this->belongsTo(UserRutina::class);
    }
}
