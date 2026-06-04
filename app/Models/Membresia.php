<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Membresia extends Model
{
    protected $fillable = [
        'user_id',
        'tipo_plan',
        'precio',
        'fecha_inicio',
        'fecha_vencimiento',
        'estado',
        'ultimo_pago',
        'metodo_pago',
        'notas',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'ultimo_pago' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDiasRestantes(): int
    {
        return Carbon::now()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstaVencida(): bool
    {
        return $this->estado === 'vencido' || $this->fecha_vencimiento < Carbon::now();
    }

    public function getEstaPorVencer(): bool
    {
        $dias = $this->getDiasRestantes();
        return $dias >= 0 && $dias <= 7;
    }

    public static function actualizarEstados()
    {
        $membresias = self::whereIn('estado', ['activo', 'por_vencer'])->get();
        
        foreach ($membresias as $membresia) {
            $diasRestantes = $membresia->getDiasRestantes();
            
            if ($diasRestantes < 0) {
                $membresia->update(['estado' => 'vencido']);
            } elseif ($diasRestantes <= 7) {
                $membresia->update(['estado' => 'por_vencer']);
            } else {
                $membresia->update(['estado' => 'activo']);
            }
        }
    }

    public function getEstadoColor(): string
    {
        return match($this->estado) {
            'activo' => 'green',
            'por_vencer' => 'yellow',
            'vencido' => 'red',
            'cancelado' => 'gray',
            default => 'gray',
        };
    }
}