/**
 * usePolling — polling con control de visibilidad y backoff exponencial.
 *
 * PROBLEMA QUE RESUELVE
 * ----------------------
 * Antes cada consumidor hacía su propio `setInterval`:
 *
 *   - NotificationBell.vue   → cada 60s (siempre, incluso con la pestaña oculta)
 *   - ChatPanel.vue          → cada 15s (mientras está montado)
 *   - useRealtimeComments.js → cada 30s (si no hay WebSocket)
 *
 * Con la pestaña abierta todo el día eso son ~4.300 requests por consumidor,
 * sin importar si el usuario está mirando la pantalla. Y si el servidor
 * empieza a fallar, el polling seguía insistiendo al mismo ritmo.
 *
 * QUÉ HACE ESTE COMPOSABLE
 *   1. PAUSA el polling cuando la pestaña no está visible
 *      (`document.visibilityState === 'hidden'`) y lo retoma al volver.
 *      No se pierde nada: al volver se dispara un fetch inmediato.
 *   2. BACKOFF exponencial ante errores: 1x, 2x, 4x... hasta un máximo.
 *      Un servidor caído no recibe un request cada 15s.
 *   3. Limpia el timer al desmontar (sin timers huérfanos).
 *   4. Backoff exponencial ante errores: 1x, 2x, 4x... hasta un máximo.
 *   5. No dispara dos polls solapados: si el fetch anterior sigue en vuelo,
 *      espera.
 *
 * USO
 *   const { start, stop } = usePolling({
 *       intervalMs: 60_000,
 *       task: () => store.fetch(),
 *       onError: (e) => console.warn(e),
 *   });
 *   onMounted(start);
 *   onBeforeUnmount(stop);
 */

import { onBeforeUnmount, ref } from 'vue';

const DEFAULTS = {
    /** Intervalo base entre polls (ms). */
    intervalMs: 60_000,
    /** Multiplicador máximo del backoff ante errores. */
    maxBackoffFactor: 8,
    /** Intervalo mínimo entre polls (ms). */
    minIntervalMs: 1_000,
};

/**
 * @param {object} options
 * @param {() => any} options.task        Función a ejecutar en cada poll.
 * @param {number} [options.intervalMs]   Intervalo base.
 * @param {(err: any) => void} [options.onError]
 * @param {boolean} [options.immediate]   Disparar un poll apenas arranca.
 * @param {boolean} [options.pauseWhenHidden] Pausar con la pestaña oculta.
 */
export function usePolling(options = {}) {
    const {
        task,
        intervalMs = DEFAULTS.intervalMs,
        maxBackoffFactor = DEFAULTS.maxBackoffFactor,
        minIntervalMs = DEFAULTS.minIntervalMs,
        onError = null,
        immediate = true,
        pauseWhenHidden = true,
    } = options;

    /** El polling está corriendo (timer activo). */
    const isPolling = ref(false);
    /** Multiplicador de backoff actual (1 = sin errores). */
    const backoffFactor = ref(1);

    let timer = null;
    let inFlight = false;
    let hiddenHandler = null;

    const clearTimer = () => {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    };

    /** Ejecuta el task. Devuelve true si salió bien. */
    const runOnce = async () => {
        // No solapar polls: si el anterior sigue en vuelo, se espera.
        if (inFlight) return false;

        inFlight = true;
        try {
            await task();
            // Éxito: resetear el backoff.
            backoffFactor.value = 1;

            return true;
        } catch (err) {
            // Error: crecer el backoff (1x → 2x → 4x... hasta el máximo).
            backoffFactor.value = Math.min(
                backoffFactor.value * 2,
                maxBackoffFactor
            );

            if (onError) {
                try {
                    onError(err);
                } catch {
                    /* el handler no debe romper el polling */
                }
            }

            return false;
        } finally {
            inFlight = false;
        }
    };

    const scheduleNext = (delay) => {
        clearTimer();
        timer = setTimeout(async () => {
            await runOnce();
            scheduleNext(currentDelay());
        }, delay);
    };

    /** Delay actual, con backoff aplicado. */
    const currentDelay = () =>
        Math.max(minIntervalMs, Math.round(intervalMs * backoffFactor.value));

    /**
     * Inicia el polling.
     *
     * @param {boolean} [force] Forzar el arranque aunque la pestaña esté oculta.
     */
    const start = (force = false) => {
        if (timer !== null) return;

        // Si la pestaña está oculta y se respeta la pausa, no arrancamos
        // (pero registramos el handler para arrancar al volver).
        if (pauseWhenHidden && !force && isHidden()) {
            isPolling.value = false;

            return;
        }

        isPolling.value = true;

        if (immediate) {
            // Primer poll inmediato, sin esperar el intervalo.
            runOnce().then(() => scheduleNext(currentDelay()));
        } else {
            scheduleNext(currentDelay());
        }
    };

    /** Detiene el polling y limpia el timer. */
    const stop = () => {
        clearTimer();
        isPolling.value = false;
        backoffFactor.value = 1;
    };

    const isHidden = () =>
        typeof document !== 'undefined' && document.visibilityState === 'hidden';

    /**
     * Al volver a la pestaña: dispara un poll INMEDIATO (para que el usuario
     * vea los datos frescos sin esperar el intervalo) y reanuda.
     */
    const handleVisibilityChange = () => {
        if (!isHidden()) {
            // Retomar: poll inmediato + timer.
            if (isPolling.value || timer !== null) {
                start(true);
            }
        } else {
            // Oculta: cortar el timer. El backoff NO se resetea: si el
            // servidor estaba fallando, seguimos con el factor acumulado.
            clearTimer();
        }
    };

    if (typeof document !== 'undefined') {
        hiddenHandler = handleVisibilityChange;
        document.addEventListener('visibilitychange', hiddenHandler);
    }

    onBeforeUnmount(() => {
        stop();
        if (hiddenHandler && typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', hiddenHandler);
        }
    });

    return {
        start,
        stop,
        isPolling,
        backoffFactor,
        /** Fuerza un poll inmediato (p. ej. al abrir un dropdown). */
        pollNow: runOnce,
    };
}
