import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface ToastAction {
    label: string;
    onClick: () => void;
}

export interface ToastOptions {
    title?: string;
    duration?: number;
    action?: ToastAction;
    persistent?: boolean;
    type?: ToastType;
}

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
    title: string | null;
    duration: number;
    action: ToastAction | null;
    persistent: boolean;
    createdAt: number;
}

type ToastInput = string | (ToastOptions & { message: string });

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
export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([]);

    function dismiss(id: number): void {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) toasts.value.splice(index, 1);
    }

    /**
     * Agrega un toast. Acepta string o un objeto con opciones.
     */
    function add(
        messageOrOptions: ToastInput,
        type: ToastType = 'info',
        opts: ToastOptions = {},
    ): number {
        const payload: ToastOptions & { message: string } =
            typeof messageOrOptions === 'string'
                ? { message: messageOrOptions, type, ...opts }
                : { type: 'info', ...messageOrOptions };

        const id = nextId++;
        const toast: Toast = {
            id,
            type: payload.type ?? 'info',
            message: payload.message,
            title: payload.title ?? null,
            duration: payload.duration ?? 4000,
            action: payload.action ?? null,
            persistent: payload.persistent ?? false,
            createdAt: Date.now(),
        };

        toasts.value.push(toast);

        if (!toast.persistent && toast.duration > 0) {
            setTimeout(() => dismiss(id), toast.duration);
        }

        return id;
    }

    function success(message: string, opts: ToastOptions = {}): number {
        return add(message, 'success', opts);
    }

    function error(message: string, opts: ToastOptions = {}): number {
        // Los errores duran más por defecto
        return add(message, 'error', { duration: 6000, ...opts });
    }

    function warning(message: string, opts: ToastOptions = {}): number {
        return add(message, 'warning', { duration: 5000, ...opts });
    }

    function info(message: string, opts: ToastOptions = {}): number {
        return add(message, 'info', opts);
    }

    function dismissAll(): void {
        toasts.value = [];
    }

    return {
        toasts,
        add,
        success,
        error,
        warning,
        info,
        dismiss,
        dismissAll,
    };
});
