<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\User|null $user
 */
class Membresia extends Model
{
    use HasFactory;

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
        'es_trial',
        'trial_ends_at',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'ultimo_pago' => 'date',
        'trial_ends_at' => 'date',
        'es_trial' => 'boolean',
    ];

    /**
     * Marcas de una membresía de CORTESÍA (backfill Fase 0.5).
     * Sin vencimiento y precio 0: es un acceso de cortesía, no un plan.
     */
    public const METODO_PAGO_CORTESIA = 'cortesia';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ¿Es una membresía de cortesía (sin vencimiento)?
     *
     * REGLA ÚNICA (Fase 0.5-0.6): la cortesía se identifica EXCLUSIVAMENTE
     * por `metodo_pago = 'cortesia'`.
     *
     * Antes esta método también aceptaba `fecha_vencimiento === null &&
     * precio == 0`, lo que la dejaba MÁS permisiva que el middleware
     * `CheckMembership` (que solo mira `metodo_pago`). Eso producía dos
     * verdades distintas según el caller: una membresía podía ser "cortesía"
     * para el modelo y "no pagada" para el middleware.
     *
     * `fecha_vencimiento IS NULL` NO es sinónimo de cortesía: la columna es
     * nullable y también la tienen los trials mal configurados y las filas
     * viejas. Tratarlas como cortesía abriría el acceso sin que nadie haya
     * pagado. Ver AGENTS.md, sección "Membresías y acceso".
     */
    public function esCortesia(): bool
    {
        return $this->metodo_pago === self::METODO_PAGO_CORTESIA;
    }

    /**
     * ¿Es un trial en curso?
     *
     * Un trial = membresía con `es_trial = true` y `trial_ends_at` (o
     * `fecha_vencimiento`) en el futuro.
     */
    public function esTrialEnCurso(): bool
    {
        if (! $this->es_trial) {
            return false;
        }

        $fin = $this->trial_ends_at ?? $this->fecha_vencimiento;

        return $fin !== null && $fin->isFuture();
    }

    /**
     * Días hasta el vencimiento.
     *
     * Las membresías de cortesía (sin vencimiento) devuelven PHP_INT_MAX:
     * nunca vencen. Antes, con `fecha_vencimiento = null`, el cast
     * 'date' devolvía null y `diffInDays` fallaba con un TypeError.
     *
     * OJO con el cast: en Carbon 3 `diffInDays()` devuelve FLOAT (y con
     * valores fraccionarios: 10.000000000023148 días en vez de 10). Por eso
     * el `(int)` explícito: sin él, `getEstaPorVencer()` comparaba contra un
     * float con error de precisión y el conteo de días podía verse
     * afectado.
     */
    public function getDiasRestantes(): int
    {
        if ($this->fecha_vencimiento === null) {
            return PHP_INT_MAX;
        }

        return (int) Carbon::now()->startOfDay()
            ->diffInDays($this->fecha_vencimiento->copy()->startOfDay(), false);
    }

    public function getEstaVencida(): bool
    {
        if ($this->fecha_vencimiento === null) {
            return false;
        }

        return $this->estado === 'vencido' || $this->fecha_vencimiento < Carbon::now();
    }

    public function getEstaPorVencer(): bool
    {
        if ($this->fecha_vencimiento === null) {
            return false;
        }

        $dias = $this->getDiasRestantes();

        return $dias >= 0 && $dias <= 7;
    }

    /**
     * Actualiza masivamente el estado de las membresías según su vencimiento.
     *
     * ANTES: cargaba TODAS las membresías y hacía un `update()` por fila. Con
     * 5k socios eran 5k UPDATEs dentro del scheduler de las 00:05 (cada uno
     * con su Eloquent overhead y su watcher).
     *
     * AHORA: tres UPDATE ... WHERE masivos (uno por estado). El resultado es
     * el mismo pero en una sola pasada por tabla.
     *
     * Las membresías de cortesía (`metodo_pago = 'cortesia'`) tienen
     * `fecha_vencimiento = NULL`: no vencen nunca y quedan 'activo'.
     */
    public static function actualizarEstados(): void
    {
        $hoy = Carbon::now()->startOfDay();

        // Vencidas: vencimiento anterior a hoy.
        self::whereIn('estado', ['activo', 'por_vencer'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', $hoy->toDateString())
            ->update(['estado' => 'vencido']);

        // Por vencer: dentro de los próximos 7 días (o hoy).
        self::whereIn('estado', ['activo', 'por_vencer'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '>=', $hoy->toDateString())
            ->where('fecha_vencimiento', '<=', $hoy->copy()->addDays(7)->toDateString())
            ->update(['estado' => 'por_vencer']);

        // Activas: más de 7 días para vencer.
        self::whereIn('estado', ['activo', 'por_vencer'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '>', $hoy->copy()->addDays(7)->toDateString())
            ->update(['estado' => 'activo']);
    }

    public function getEstadoColor(): string
    {
        return match ($this->estado) {
            'activo' => 'green',
            'por_vencer' => 'yellow',
            'vencido' => 'red',
            'cancelado' => 'gray',
            default => 'gray',
        };
    }
}
