<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Rutina;

/**
 * Registra cambios en Rutinas en el audit log.
 * Se activa para created, updated, deleted.
 *
 * Para `updated` se trackean solo los campos que cambiaron (no todo el modelo
 * en cada save, que ensucia el log).
 */
class RutinaObserver
{
    /**
     * Campos que nos interesa trackear cuando cambian.
     * Si cambiás un campo que no está acá, no se loguea.
     */
    private const TRACKED_FIELDS = [
        'nivel', 'modalidad', 'dia', 'series', 'reps_min', 'reps_max',
        'descanso_min', 'ejercicio_nombre', 'ejercicio_id', 'orden',
        'superserie_grupo', 'created_by',
    ];

    public function created(Rutina $rutina): void
    {
        AuditLog::forModel($rutina, 'created', null, $rutina->getAttributes());
    }

    public function updated(Rutina $rutina): void
    {
        $changes = $rutina->getChanges();
        $old = [];
        $new = [];
        foreach (self::TRACKED_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $old[$field] = $rutina->getOriginal($field);
                $new[$field] = $rutina->getAttribute($field);
            }
        }
        if (empty($new)) return; // nada trackeable cambió

        AuditLog::forModel($rutina, 'updated', $old, $new);
    }

    public function deleted(Rutina $rutina): void
    {
        AuditLog::forModel($rutina, 'deleted', $rutina->getAttributes(), null);
    }
}
