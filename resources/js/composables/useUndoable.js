import { useToast } from './useToast';

/**
 * Patrón "undo real" para acciones destructivas.
 *
 * Flujo:
 *   1. Se aplica el cambio visualmente de inmediato (optimistic UI).
 *   2. Se muestra un toast con botón "Deshacer" durante `duration` ms.
 *   3. Si el usuario hace click en "Deshacer" antes de que termine el
 *      contador, se revierte el cambio visual y se CANCELA el request
 *      al servidor.
 *   4. Si pasa el tiempo sin acción, se ejecuta el `commit()` real
 *      (request al server). Si falla, se revierte el cambio visual.
 *
 * Uso:
 *   await useUndoable({
 *       message: 'Rutina eliminada',
 *       apply:    () => rutinas.value.splice(idx, 1),
 *       undo:     () => rutinas.value.splice(idx, 0, snapshot),
 *       commit:   () => axios.delete('/api/rutinas', { data: { id } }),
 *       onError:  (err) => toast.apiError(err),
 *   });
 *
 * @param {object}   opts
 * @param {string}   opts.message              Texto del toast
 * @param {string}   [opts.undoLabel='Deshacer']
 * @param {Function} opts.apply                Efecto visual inmediato
 * @param {Function} opts.undo                 Revertir el efecto visual
 * @param {Function} opts.commit               Acción real (server)
 * @param {Function} [opts.onError]            Callback si commit() falla
 * @param {number}   [opts.duration=5000]      ms para deshacer
 * @returns {Promise<{cancelled: boolean, executed: boolean}>}
 */
export function useUndoable(opts) {
    const toast = useToast();
    const {
        message,
        undoLabel = 'Deshacer',
        apply,
        undo,
        commit,
        onError,
        duration = 5000,
    } = opts;

    if (typeof apply !== 'function' || typeof undo !== 'function' || typeof commit !== 'function') {
        throw new Error('[useUndoable] apply, undo y commit son obligatorios y deben ser funciones.');
    }

    // 1) Aplicar inmediatamente (optimistic)
    apply();

    return new Promise((resolve) => {
        let cancelled = false;
        let executed = false;
        let resolved = false;

        const finish = () => {
            if (!resolved) {
                resolved = true;
                resolve({ cancelled, executed });
            }
        };

        // 2) Toast con botón Deshacer
        toast.info(message, {
            duration,
            action: {
                label: undoLabel,
                onClick: () => {
                    cancelled = true;
                    undo();
                    finish();
                },
            },
        });

        // 3) Si pasa el tiempo, hacer el commit real
        setTimeout(async () => {
            if (cancelled) return;
            executed = true;
            try {
                await commit();
            } catch (err) {
                // Falló el server: revertimos el cambio visual
                executed = false;
                cancelled = true;
                try { undo(); } catch (_) { /* noop */ }
                if (typeof onError === 'function') onError(err);
                else toast.apiError(err, 'No se pudo completar la acción');
            } finally {
                finish();
            }
        }, duration);
    });
}
