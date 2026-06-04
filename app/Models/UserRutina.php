<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRutina extends Model
{
    protected $fillable = [
        'user_id',
        'rutina_id',
        'assigned_by',
        'nivel',
        'modalidad',
        'dia_actual',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'rutina_id' => 'integer',
        'assigned_by' => 'integer',
    ];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}