<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjercicioTrainer extends Model
{
    protected $table = 'ejercicios_trainer';

    protected $fillable = [
        'trainer_id',
        'nombre',
        'grupo_muscular',
        'equipamiento',
        'descripcion',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}