import { onBeforeUnmount, onMounted, unref, watch } from 'vue';

/**
 * Detecta clicks/Toque fuera de uno o mas elementos.
 *
 * Reemplaza la directiva `@click.away` de Alpine (que no existe en Vue 3).
 *
 * Uso:
 *   const root = ref(null);
 *   const open = ref(false);
 *   useClickOutside(root, () => { open.value = false; }, open);
 *
 * @param {import('vue').Ref<HTMLElement|null>|HTMLElement} target  elemento(s) a vigilar
 * @param {Function} handler  callback que se dispara al click fuera
 * @param {import('vue').Ref<boolean>|boolean} [when]  si se pasa, solo escucha cuando es truthy
 */
export function useClickOutside(target, handler, when = true) {
    let listenerAttached = false;

    function getTargets() {
        const t = unref(target);
        if (!t) return [];
        if (Array.isArray(t)) {
            // Array de refs (o de elementos): hay que desenvolver cada item.
            return t.map((item) => resolveElement(unref(item))).filter(Boolean);
        }
        return [resolveElement(t)].filter(Boolean);
    }

    /** Normaliza un ref / elemento / componente a un Element del DOM. */
    function resolveElement(value) {
        if (!value) return null;
        if (value instanceof Element) return value;
        // Un ref con multiples elementos (v-for) -> usa $el
        if (value.$el instanceof Element) return value.$el;
        return null;
    }

    function onPointerDown(event) {
        const targets = getTargets();
        if (!targets.length) return;

        const clickedInside = targets.some((el) => el.contains(event.target));
        if (clickedInside) return;

        handler(event);
    }

    function shouldListen() {
        return !!unref(when);
    }

    function attach() {
        if (listenerAttached || !shouldListen()) return;
        document.addEventListener('pointerdown', onPointerDown, true);
        listenerAttached = true;
    }

    function detach() {
        if (!listenerAttached) return;
        document.removeEventListener('pointerdown', onPointerDown, true);
        listenerAttached = false;
    }

    onMounted(attach);
    onBeforeUnmount(detach);

    // Si `when` es un ref, enganchamos/desenganchamos el listener segun su valor.
    // Asi el dropdown arranca cerrado (sin listener) y solo escucha mientras
    // esta abierto: no es lo mismo que el `@click.away` de Alpine, que ya
    // estaba escuchando siempre.
    if (when && typeof when === 'object' && 'value' in when) {
        watch(
            when,
            (value) => {
                if (value) attach();
                else detach();
            },
            { immediate: true }
        );
    }

    return {
        attach,
        detach,
        /** Re-evalua `when` (util si el estado lo maneja otro composable) */
        refresh() {
            if (shouldListen()) attach();
            else detach();
        },
    };
}
