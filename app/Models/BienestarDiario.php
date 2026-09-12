<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BienestarDiario extends Model
{
    use HasFactory;

    protected $table = 'bienestar_diario';

    protected $fillable = [
        'user_id',
        'fecha',
        'horas_sueno',
        'calidad_sueno',
        'nivel_estres',
        'dolor_muscular',
        'notas',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'fecha' => 'date',
        'horas_sueno' => 'decimal:1',
        'calidad_sueno' => 'integer',
        'nivel_estres' => 'integer',
        'dolor_muscular' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
