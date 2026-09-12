/**
 * useWakeLock — Mantiene la pantalla encendida durante una sesion activa.
 *
 * Wrapper sobre la Screen Wake Lock API (https://developer.mozilla.org/en-US/docs/Web/API/Screen_Wake_Lock_API).
 * Solo funciona en navegadores compatibles (Chrome/Edge/Opera). En Safari y otros
 * devuelve `{ supported: false }` y es un no-op silencioso.
 *
 * Comportamiento:
 *   - request() pide el wake lock. Devuelve true si lo obtuvo.
 *   - Si el user cambia de tab/app, el wake lock se libera automaticamente
 *     y lo re-pide cuando vuelve (visibility change).
 *   - release() suelta el lock manualmente.
 *
 * Uso:
 *   const wake = useWakeLock();
 *   onMounted(() => wake.request());
 *   onBeforeUnmount(() => wake.release());
 */
import { onBeforeUnmount, ref } from 'vue';

export function useWakeLock() {
    const supported = typeof navigator !== 'undefined' && 'wakeLock' in navigator;
    const active = ref(false);
    let sentinel = null;
    let reAcquiring = false;

    const acquire = async () => {
        if (!supported || sentinel) return false;
        try {
            sentinel = await navigator.wakeLock.request('screen');
            active.value = true;
            sentinel.addEventListener('release', () => {
                // El navegador puede liberarlo solo (timeout, low battery, etc).
                active.value = false;
                sentinel = null;
            });
            return true;
        } catch (err) {
            // Permission denied o no se pudo. No es un error bloqueante.
            // eslint-disable-next-line no-console
            console.warn('[useWakeLock] No se pudo adquirir el wake lock:', err.message);
            active.value = false;
            sentinel = null;
            return false;
        }
    };

    const release = async () => {
        if (sentinel) {
            try {
                await sentinel.release();
            } catch (err) {
                // Ya estaba liberado; ignorar.
            }
            sentinel = null;
        }
        active.value = false;
    };

    // Si el user vuelve a la tab y tenemos el wake lock solicitado, lo re-adquirimos.
    const handleVisibility = async () => {
        if (document.visibilityState === 'visible' && reAcquiring && !sentinel) {
            await acquire();
        }
    };

    /**
     * Pide el wake lock. Devuelve true si lo obtuvo.
     * Si la pestana esta oculta, espera a que vuelva visible.
     */
    const request = async () => {
        reAcquiring = true;
        if (document.visibilityState === 'visible') {
            return acquire();
        }
        // Si la pestana esta oculta, esperamos al visibilitychange.
        return true;
    };

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', handleVisibility);
    }

    onBeforeUnmount(() => {
        reAcquiring = false;
        if (typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', handleVisibility);
        }
        release();
    });

    return {
        supported,
        active,
        request,
        release,
    };
}
