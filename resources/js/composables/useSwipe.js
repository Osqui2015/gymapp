/**
 * Composable de gestos swipe (izquierda/derecha/arriba/abajo).
 *
 * Uso típico: navegación entre tabs o días de rutina en mobile.
 *
 *   const { onSwipeLeft, onSwipeRight } = useSwipe(targetEl, {
 *       threshold: 60,
 *       timeout: 600,
 *   });
 *   onSwipeLeft(() => nextTab());
 *   onSwipeRight(() => prevTab());
 *
 * El composable NO bloquea scrolls verticales: si el movimiento dominante
 * es vertical (más Y que X), ignora el gesto.
 */

import { onMounted, onBeforeUnmount } from 'vue';

const DEFAULTS = {
    threshold: 50,   // px mínimos en el eje dominante
    timeout: 800,    // ms máximos entre touchstart y touchend
    restraint: 75,   // px tolerados en el eje perpendicular
};

export function useSwipe(targetRef, options = {}) {
    const opts = { ...DEFAULTS, ...options };
    const handlers = {
        left: null,
        right: null,
        up: null,
        down: null,
    };

    let startX = 0;
    let startY = 0;
    let startTime = 0;
    let tracking = false;

    const onStart = (e) => {
        const t = e.touches ? e.touches[0] : e;
        startX = t.clientX;
        startY = t.clientY;
        startTime = Date.now();
        tracking = true;
    };

    const onEnd = (e) => {
        if (!tracking) return;
        tracking = false;
        const elapsed = Date.now() - startTime;
        if (elapsed > opts.timeout) return;

        const t = e.changedTouches ? e.changedTouches[0] : e;
        const dx = t.clientX - startX;
        const dy = t.clientY - startY;

        const absX = Math.abs(dx);
        const absY = Math.abs(dy);

        // Movimiento dominante vertical: ignorar (es un scroll)
        if (absY > absX) return;

        if (absX < opts.threshold) return;
        if (absY > opts.restraint) return;

        if (dx < 0) handlers.left?.();
        else handlers.right?.();
    };

    const onMove = (e) => {
        // no-op: se procesa en end. Pero si el usuario hace scroll vertical,
        // podemos abortar el tracking para no robar el evento.
        if (!tracking) return;
        const t = e.touches ? e.touches[0] : e;
        const dx = Math.abs(t.clientX - startX);
        const dy = Math.abs(t.clientY - startY);
        if (dy > dx * 1.5) tracking = false;
    };

    const attach = () => {
        const el = targetRef.value;
        if (!el) return;
        el.addEventListener('touchstart', onStart, { passive: true });
        el.addEventListener('touchmove', onMove, { passive: true });
        el.addEventListener('touchend', onEnd, { passive: true });
        el.addEventListener('touchcancel', onEnd, { passive: true });
    };

    const detach = () => {
        const el = targetRef.value;
        if (!el) return;
        el.removeEventListener('touchstart', onStart);
        el.removeEventListener('touchmove', onMove);
        el.removeEventListener('touchend', onEnd);
        el.removeEventListener('touchcancel', onEnd);
    };

    onMounted(attach);
    onBeforeUnmount(detach);

    return {
        onSwipeLeft: (fn) => { handlers.left = fn; },
        onSwipeRight: (fn) => { handlers.right = fn; },
        onSwipeUp: (fn) => { handlers.up = fn; },
        onSwipeDown: (fn) => { handlers.down = fn; },
        detach,
        attach,
    };
}
