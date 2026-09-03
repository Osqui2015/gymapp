<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProgresoFoto extends Model
{
    protected $table = 'progreso_fotos';

    protected $fillable = [
        'user_id',
        'fecha',
        'tipo',
        'foto_path',
        'notas',
        'peso',
    ];

    protected $casts = [
        'fecha' => 'date',
        'peso' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL pública de la foto. Usa el disk "public" (symlink public/storage).
     * Devuelve null si el archivo ya no existe (foto borrada del disco).
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->foto_path || !Storage::disk('public')->exists($this->foto_path)) {
            return null;
        }
        return Storage::disk('public')->url($this->foto_path);
    }
}
