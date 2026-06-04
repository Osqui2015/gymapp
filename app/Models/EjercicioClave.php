<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjercicioClave extends Model
{
    protected $table = 'ejercicios_clave';

    protected $fillable = [
        'user_id',
        'trainer_id',
        'ejercicio_nombre',
        'notas_trainer',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'trainer_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
