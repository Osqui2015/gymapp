import { defineStore } from 'pinia';

let nextId = 1;

/**
 * Store global de toasts/notifications.
 *
 * Uso:
 *   const toast = useToastStore();
 *   toast.success('Guardado');
 *   toast.error('Falló', { duration: 5000 });
 *   toast.add({ message: '...', type: 'warning', action: { label: 'Deshacer', onClick: fn } });
 *
 * Tipos: 'success' | 'error' | 'warning' | 'info'
 */
export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),

    actions: {
        /**
         * Agrega un toast.
         * @param {string|object} messageOrOptions
         * @param {string} [type='info']
         * @param {object} [opts]
         */
        add(messageOrOptions, type = 'info', opts = {}) {
            const payload = typeof messageOrOptions === 'string'
                ? { message: messageOrOptions, type, ...opts }
                : { type: 'info', ...messageOrOptions };

            const id = nextId++;
            const toast = {
                id,
                type: payload.type,
                message: payload.message,
                title: payload.title || null,
                duration: payload.duration ?? 4000,
                action: payload.action || null, // { label, onClick }
                persistent: payload.persistent ?? false,
                createdAt: Date.now(),
            };

            this.toasts.push(toast);

            if (!toast.persistent && toast.duration > 0) {
                setTimeout(() => this.dismiss(id), toast.duration);
            }

            return id;
        },

        success(message, opts = {}) {
            return this.add(message, 'success', opts);
        },

        error(message, opts = {}) {
            // Los errores duran más por defecto
            return this.add(message, 'error', { duration: 6000, ...opts });
        },

        warning(message, opts = {}) {
            return this.add(message, 'warning', { duration: 5000, ...opts });
        },

        info(message, opts = {}) {
            return this.add(message, 'info', opts);
        },

        dismiss(id) {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index > -1) this.toasts.splice(index, 1);
        },

        dismissAll() {
            this.toasts = [];
        },
    },
});
