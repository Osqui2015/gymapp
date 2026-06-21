import { useToastStore } from '../stores/toast';

let listenersInstalled = false;

/**
 * Composable para usar el sistema de toasts desde cualquier componente.
 *
 * Ejemplo:
 *   const toast = useToast();
 *   toast.success('Guardado');
 *   toast.error('Error', { duration: 8000 });
 *
 *   // Con acción (ej: undo)
 *   toast.info('Rutina eliminada', {
 *     action: { label: 'Deshacer', onClick: () => restaurarRutina(id) },
 *     duration: 5000,
 *   });
 *
 *   // Confirmación (reemplaza window.confirm)
 *   toast.confirm('¿Eliminar esta rutina?', { onConfirm: () => eliminar() });
 */
export function useToast() {
    const store = useToastStore();

    return {
        add: store.add.bind(store),
        success: store.success.bind(store),
        error: store.error.bind(store),
        warning: store.warning.bind(store),
        info: store.info.bind(store),
        dismiss: store.dismiss.bind(store),
        dismissAll: store.dismissAll.bind(store),

        // Helpers comunes
        apiError(error, fallback = 'Ocurrió un error inesperado') {
            const message = error?.response?.data?.message
                || error?.response?.data?.error
                || error?.message
                || fallback;
            return store.error(message);
        },

        validationError(errors) {
            // errors es un objeto { field: [msg1, msg2] }
            const first = Object.values(errors || {})[0];
            const message = Array.isArray(first) ? first[0] : (first || 'Datos inválidos');
            return store.error(message);
        },

        /**
         * Muestra un toast de confirmación con dos acciones: Cancelar y Confirmar.
         * Devuelve una Promise<boolean> que resuelve true si el usuario confirmó.
         *
         * @param {string} message
         * @param {object} [opts]
         * @param {string} [opts.title]
         * @param {string} [opts.confirmLabel='Confirmar']
         * @param {string} [opts.cancelLabel='Cancelar']
         * @param {string} [opts.type='warning']
         * @param {number} [opts.duration=0]  // 0 = persistent
         * @returns {Promise<boolean>}
         */
        confirm(message, opts = {}) {
            return new Promise((resolve) => {
                const id = store.add(message, opts.type || 'warning', {
                    title: opts.title,
                    duration: opts.duration ?? 0, // 0 = no auto-dismiss
                    action: {
                        label: opts.confirmLabel || 'Confirmar',
                        onClick: () => resolve(true),
                    },
                });
                // Si el toast se cierra sin confirmar (botón X, click fuera, etc.)
                // resolvemos false. Usamos watch vía subscribe de Vue.
                const stop = store.$subscribe((mutation, state) => {
                    const exists = state.toasts.find((t) => t.id === id);
                    if (!exists) {
                        stop();
                        resolve(false);
                    }
                });
            });
        },
    };
}

/**
 * Instala listeners globales para que los errores HTTP de axios
 * se traduzcan en toasts automáticamente. Se llama una vez en app.js.
 *
 * Se sincroniza con los CustomEvents que ya emite bootstrap.js:
 *   - auth:forbidden (403)
 *   - auth:expired (401)
 *   - auth:csrf-mismatch (419)
 *   - validation:error (422)
 *   - server:error (500+)
 */
export function setupGlobalToastListeners() {
    if (listenersInstalled) return;
    listenersInstalled = true;

    // Necesitamos que el componente toast ya esté montado (o al menos pinia listo)
    // para que useToastStore() funcione. Usamos nextTick o esperamos al mount.
    const handle = (type, message, opts = {}) => {
        // Reusar el store activo. Si por algún motivo no está listo, reintentamos.
        try {
            const store = useToastStore();
            if (store && typeof store.add === 'function') {
                store.add(message, type, opts);
            }
        } catch (e) {
            console.warn('[toast] store no listo aún:', e);
        }
    };

    window.addEventListener('auth:forbidden', (e) => {
        handle('error', e.detail?.message || 'No tenés permisos para esta acción');
    });

    window.addEventListener('auth:expired', () => {
        handle('warning', 'Tu sesión expiró. Redirigiendo al login...', { duration: 3000 });
    });

    window.addEventListener('auth:csrf-mismatch', () => {
        handle('warning', 'Recargando la página por seguridad...', { duration: 2500 });
    });

    window.addEventListener('validation:error', (e) => {
        const errors = e.detail || {};
        const first = Object.values(errors)[0];
        const message = Array.isArray(first) ? first[0] : (first || 'Datos inválidos');
        handle('error', message);
    });

    window.addEventListener('server:error', (e) => {
        handle('error', e.detail?.message || 'Error del servidor. Por favor intenta de nuevo.', { duration: 6000 });
    });
}
