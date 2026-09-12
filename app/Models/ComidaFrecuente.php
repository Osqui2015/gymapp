<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComidaFrecuente extends Model
{
    use HasFactory;

    protected $table = 'comidas_frecuentes';

    protected $fillable = [
        'user_id',
        'nombre',
        'calorias',
        'proteinas',
        'carbohidratos',
        'grasas',
        'porcion',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'calorias' => 'integer',
        'proteinas' => 'integer',
        'carbohidratos' => 'integer',
        'grasas' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
