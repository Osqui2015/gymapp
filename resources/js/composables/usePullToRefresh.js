/**
 * Composable de pull-to-refresh.
 *
 * Detecta un gesto de arrastre desde el top de un contenedor scrollable
 * (touch + puntero) y dispara `onRefresh` cuando el usuario suelta después
 * de pasar el umbral. Mientras arrastra, expone un "offset" reactivo para
 * que la UI muestre un spinner/indicador.
 *
 * El objetivo puede ser un Element ref (cualquier contenedor con scroll
 * interno) o `window` (scroll principal de la página). Esto último es lo
 * más útil para pull-to-refresh a nivel de página en mobile.
 *
 * Sólo se activa si el scrollTop del contenedor está en 0 al iniciar el
 * gesto, para no interferir con scroll normal.
 *
 * Respeta `prefers-reduced-motion` desactivando la animación de resorte.
 *
 * Uso (target = elemento con overflow):
 *   const scrollEl = ref(null);
 *   const { isPulling, pullOffset, isRefreshing } = usePullToRefresh(scrollEl, async () => {
 *       await cargarDatos();
 *   });
 *
 * Uso (target = window, scroll principal):
 *   const { isPulling, pullOffset } = usePullToRefresh(window, cargarDatos);
 */

import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const THRESHOLD = 70;            // px a arrastrar para disparar refresh
const MAX_PULL = 120;            // tope duro para evitar stretching infinito
const RESISTANCE = 0.4;          // damping: cada px de dedo → 0.4px de offset

export function usePullToRefresh(target, onRefresh, options = {}) {
    const {
        threshold = THRESHOLD,
        maxPull = MAX_PULL,
        resistance = RESISTANCE,
    } = options;

    // Normalizar target: aceptar ref o window directo
    const isWindow = target === window;
    const targetRef = isWindow ? ref(window) : target;

    let startY = 0;
    let active = false;

    const isPulling = ref(false);
    const isRefreshing = ref(false);
    const pullOffset = ref(0);

    const progress = computed(() => Math.min(pullOffset.value / threshold, 1));

    const reducedMotion = () =>
        typeof window !== 'undefined'
        && window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const setOffset = (raw) => {
        const next = Math.max(0, Math.min(maxPull, raw * resistance));
        pullOffset.value = next;
    };

    const getScrollTop = () => {
        if (isWindow) {
            return window.scrollY || document.documentElement.scrollTop || 0;
        }
        return targetRef.value?.scrollTop || 0;
    };

    const finishRefresh = () => {
        isRefreshing.value = false;
        pullOffset.value = 0;
    };

    const onStart = (clientY) => {
        if (isRefreshing.value) return;
        if (getScrollTop() > 2) return;
        startY = clientY;
        active = true;
    };

    const onMove = (clientY) => {
        if (!active || isRefreshing.value) return;
        const delta = clientY - startY;
        if (delta <= 0) return;
        setOffset(delta);
        isPulling.value = pullOffset.value > 0;
    };

    const onEnd = async () => {
        if (!active) return;
        const triggered = pullOffset.value >= threshold;
        active = false;
        isPulling.value = false;
        if (!triggered) {
            pullOffset.value = 0;
            return;
        }
        isRefreshing.value = true;
        pullOffset.value = reducedMotion() ? 0 : Math.min(pullOffset.value, 60);
        try {
            await onRefresh?.();
        } catch (e) {
            console.error('[usePullToRefresh] onRefresh lanzó:', e);
        } finally {
            setTimeout(finishRefresh, 200);
        }
    };

    // Handlers unificados (mismo shape para pointer y touch)
    const pointerDown = (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        onStart(e.clientY);
    };
    const pointerMove = (e) => onMove(e.clientY);
    const pointerUp = () => onEnd();

    const touchStart = (e) => onStart(e.touches[0].clientY);
    const touchMove = (e) => onMove(e.touches[0].clientY);
    const touchEnd = () => onEnd();

    onMounted(() => {
        const el = targetRef.value;
        if (!el) return;
        // Pointer Events: mouse + touch + stylus en navegadores modernos
        el.addEventListener('pointerdown', pointerDown, { passive: true });
        el.addEventListener('pointermove', pointerMove, { passive: true });
        el.addEventListener('pointerup', pointerUp, { passive: true });
        el.addEventListener('pointercancel', pointerUp, { passive: true });
        // Fallback explícito de touch
        el.addEventListener('touchstart', touchStart, { passive: true });
        el.addEventListener('touchmove', touchMove, { passive: true });
        el.addEventListener('touchend', touchEnd, { passive: true });
    });

    onBeforeUnmount(() => {
        const el = targetRef.value;
        if (!el) return;
        el.removeEventListener('pointerdown', pointerDown);
        el.removeEventListener('pointermove', pointerMove);
        el.removeEventListener('pointerup', pointerUp);
        el.removeEventListener('pointercancel', pointerUp);
        el.removeEventListener('touchstart', touchStart);
        el.removeEventListener('touchmove', touchMove);
        el.removeEventListener('touchend', touchEnd);
    });

    return {
        isPulling,
        isRefreshing,
        pullOffset,
        progress,
    };
}
