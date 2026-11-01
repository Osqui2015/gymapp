<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedallaUsuario extends Model
{
    protected $table = 'medallas_usuario';

    protected $fillable = [
        'user_id',
        'slug',
        'nombre',
        'descripcion',
        'ganado_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'ganado_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
