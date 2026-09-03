<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RutinaFavorita extends Model
{
    protected $table = 'rutina_favoritas';

    protected $fillable = [
        'user_id',
        'rutina_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }
}
