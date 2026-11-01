import { onUnmounted, ref } from 'vue';

/**
 * Composable para detectar un long-press táctil/mouse.
 *
 * Caso de uso típico: completar una serie de ejercicio en el gym con
 * un solo dedo, sin necesidad de apuntar a un botón chico.
 *
 * Diferencia con `click`: el click se dispara en mouseup rápido; el
 * long-press requiere sostener ~500ms sin moverse. Una vez disparado,
 * se llama a `onLongPress` y se ignora el click posterior sobre el
 * mismo gesto.
 *
 * Uso:
 *   const { isPressed, bind } = useLongPress({
 *       onLongPress: () => completarSerie(),
 *       delay: 500,
 *   });
 *   <button v-bind="bind">...</button>
 *   <div v-if="isPressed" class="ring-2">...</div>
 *
 * @param {object}  opts
 * @param {Function} opts.onLongPress  Callback al completarse el gesto
 * @param {number}  [opts.delay=500]   ms de hold para disparar
 * @param {number}  [opts.tolerance=10] px de movimiento tolerados antes de cancelar
 * @returns {{ isPressed: Ref<boolean>, bind: object }}
 */
export function useLongPress(opts = {}) {
    const { onLongPress, delay = 500, tolerance = 10 } = opts;

    if (typeof onLongPress !== 'function') {
        throw new Error('[useLongPress] onLongPress es obligatorio');
    }

    const isPressed = ref(false);
    let timer = null;
    let startX = 0;
    let startY = 0;
    let triggered = false;

    const cancel = () => {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
        isPressed.value = false;
    };

    const onStart = (e) => {
        // Tomamos el punto de inicio (touch o mouse)
        const point = e.touches?.[0] ?? e;
        startX = point.clientX;
        startY = point.clientY;
        triggered = false;

        isPressed.value = true;
        timer = setTimeout(() => {
            triggered = true;
            isPressed.value = false;
            try {
                onLongPress();
            } catch (err) {
                console.error('[useLongPress] onLongPress lanzó:', err);
            }
        }, delay);
    };

    const onMove = (e) => {
        if (timer === null) return;
        const point = e.touches?.[0] ?? e;
        const dx = Math.abs(point.clientX - startX);
        const dy = Math.abs(point.clientY - startY);
        if (dx > tolerance || dy > tolerance) {
            cancel();
        }
    };

    const onEnd = () => {
        cancel();
    };

    const onClick = (e) => {
        // Si el long-press ya disparó, bloqueamos el click para no duplicar
        if (triggered) {
            e.preventDefault();
            e.stopPropagation();
            triggered = false;
        }
    };

    onUnmounted(cancel);

    return {
        isPressed,
        bind: {
            onMousedown: onStart,
            onMouseup: onEnd,
            onMouseleave: onEnd,
            onMousemove: onMove,
            onTouchstart: onStart,
            onTouchend: onEnd,
            onTouchcancel: onEnd,
            onTouchmove: onMove,
            onClick,
        },
    };
}
