import { watch, onBeforeUnmount } from 'vue';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Atrapa el foco dentro de un elemento (útil para modales).
 *
 * Funciona en dos modos:
 *
 * 1. **Montaje fijo** (cuando el contenedor existe desde el mount del componente):
 *      const modalRef = ref(null);
 *      useFocusTrap(modalRef);
 *
 * 2. **Condicional** (cuando el contenedor aparece/desaparece con v-if):
 *      const isOpen = ref(false);
 *      const modalRef = ref(null);
 *      useFocusTrap(modalRef, { when: isOpen });
 *
 * Features:
 *   - Tab y Shift+Tab ciclan solo dentro del contenedor
 *   - Escape ejecuta onEscape (default: dispatch 'close' event)
 *   - Foco al primer focusable al activarse
 *   - Restaura el foco al elemento que abrió el contenedor al desactivarse
 */
export function useFocusTrap(elementRef, opts = {}) {
    const onEscape = opts.onEscape || (() => elementRef.value?.dispatchEvent(new CustomEvent('close')));
    const whenRef = opts.when || null;
    let triggerEl = null;
    let listenerAttached = false;
    let active = false;

    function activate() {
        if (active) return;
        if (!elementRef.value) return;

        triggerEl = document.activeElement;
        active = true;

        // Mover foco al primer focusable (en el siguiente tick para que el DOM esté listo)
        Promise.resolve().then(() => {
            if (!elementRef.value) return;
            const focusables = elementRef.value.querySelectorAll(FOCUSABLE);
            if (focusables.length) focusables[0].focus();
        });

        document.addEventListener('keydown', handleKeydown);
        listenerAttached = true;
    }

    function deactivate() {
        if (!active) return;
        active = false;

        if (listenerAttached) {
            document.removeEventListener('keydown', handleKeydown);
            listenerAttached = false;
        }

        // Restaurar foco al elemento que abrió el modal
        if (triggerEl && typeof triggerEl.focus === 'function') {
            try { triggerEl.focus(); } catch (_) { /* noop */ }
        }
        triggerEl = null;
    }

    function handleKeydown(e) {
        if (!elementRef.value) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            onEscape();
            return;
        }

        if (e.key !== 'Tab') return;

        const focusables = elementRef.value.querySelectorAll(FOCUSABLE);
        if (!focusables.length) {
            e.preventDefault();
            return;
        }

        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        const activeEl = document.activeElement;

        if (e.shiftKey) {
            if (activeEl === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (activeEl === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    // Modo condicional (cuando se pasa opts.when)
    if (whenRef) {
        watch(
            whenRef,
            (isOpen) => {
                if (isOpen) {
                    // Pequeño delay para asegurar que el v-if ya renderizó el contenedor
                    Promise.resolve().then(() => activate());
                } else {
                    deactivate();
                }
            },
            { immediate: true }
        );
    } else {
        // Modo fijo: activar en el siguiente tick después del mount
        Promise.resolve().then(() => activate());
    }

    onBeforeUnmount(() => deactivate());
}