<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\GymConfig;
use App\Services\GymConfigService;

/**
 * Observer de la configuración del gimnasio (Fase 0.4 / plan §12.8).
 *
 * Hace dos cosas:
 *
 *  1. Invalida el cache de `GymConfigService` en cada cambio. Así, cualquier
 *     escritura (venga del service o de un update directo) refresca la
 *     config sin depender de que el service esté en el medio.
 *
 *  2. Audita el cambio con valor anterior y nuevo. La configuración del
 *     gym gobierna el acceso (días de trial, gracia) y el precio: cambiarla
 *     sin dejar rastro es exactamente el tipo de operación que después no
 *     se puede reconstruir.
 */
class GymConfigObserver
{
    public function __construct(private GymConfigService $configService) {}

    public function created(GymConfig $config): void
    {
        $this->configService->forget();

        AuditLog::log(
            'gym_config_created',
            "Configuración del gym: se creó '{$config->key}'",
            auth()->id(),
            GymConfig::class,
            $config->id,
            null,
            ['key' => $config->key, 'value' => $config->value]
        );
    }

    public function updated(GymConfig $config): void
    {
        $this->configService->forget();

        $changes = $config->getChanges();
        if (! array_key_exists('value', $changes)) {
            return;
        }

        AuditLog::log(
            'gym_config_updated',
            "Configuración del gym: cambió '{$config->key}'",
            auth()->id(),
            GymConfig::class,
            $config->id,
            ['key' => $config->key, 'value' => $config->getOriginal('value')],
            ['key' => $config->key, 'value' => $config->value]
        );
    }

    public function deleted(GymConfig $config): void
    {
        $this->configService->forget();

        AuditLog::log(
            'gym_config_deleted',
            "Configuración del gym: se eliminó '{$config->key}' (vuelve al default)",
            auth()->id(),
            GymConfig::class,
            $config->id,
            ['key' => $config->key, 'value' => $config->getOriginal('value')],
            null
        );
    }
}
