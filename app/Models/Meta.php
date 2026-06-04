<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meta extends Model
{
    protected $fillable = [
        'user_id',
        'tipo',
        'descripcion',
        'valor_objetivo',
        'completada',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'valor_objetivo' => 'decimal:2',
        'completada' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
