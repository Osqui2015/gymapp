<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiarioNutricion extends Model
{
    protected $table = 'diario_nutricion';

    protected $fillable = [
        'user_id',
        'fecha',
        'calorias',
        'proteinas',
        'carbohidratos',
        'grasas',
        'agua_vasos',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'fecha' => 'date',
        'calorias' => 'integer',
        'proteinas' => 'integer',
        'carbohidratos' => 'integer',
        'grasas' => 'integer',
        'agua_vasos' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
