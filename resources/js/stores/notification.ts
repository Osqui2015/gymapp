import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export interface NotificationItem {
    id: number | string;
    read_at: string | null;
    [key: string]: unknown;
}

interface NotificationsListResponse {
    data?: NotificationItem[];
}

interface MarkAllReadResponse {
    updated?: number;
}

/**
 * Store de notificaciones in-app (centro de notificaciones, distinto de toasts).
 *
 * Las notificaciones PERSISTEN en la DB. El store las carga, mantiene un
 * cache reactivo, y expone acciones para marcar como leídas / eliminar.
 *
 * Polling cada 60s (configurable) para refrescar — más adelante se puede
 * sumar WebSocket realtime con Laravel Echo si el costo de polling es alto.
 */
export const useNotificationStore = defineStore('notification', () => {
    const items = ref<NotificationItem[]>([]);
    const unreadCount = ref<number>(0);
    const isLoading = ref<boolean>(false);
    const lastFetchedAt = ref<number | null>(null);

    /**
     * Carga las notificaciones del backend.
     * @param unreadOnly Si true, solo trae las no leídas
     */
    async function fetch(unreadOnly = false): Promise<void> {
        isLoading.value = true;
        try {
            const { data } = await axios.get<NotificationItem[] | NotificationsListResponse>(
                '/api/notifications',
                {
                    params: unreadOnly ? { unread_only: 1, per_page: 50 } : { per_page: 50 },
                },
            );
            // Laravel paginator entrega `data` adentro, o array directo
            items.value = Array.isArray(data) ? data : (data.data || []);
            unreadCount.value = items.value.filter((n) => !n.read_at).length;
            lastFetchedAt.value = Date.now();
        } catch (e) {
            console.error('[notifications] fetch falló:', e);
        } finally {
            isLoading.value = false;
        }
    }

    async function markRead(id: number | string): Promise<void> {
        try {
            await axios.post(`/api/notifications/${id}/read`);
            const item = items.value.find((n) => n.id === id);
            if (item && !item.read_at) {
                item.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (e) {
            console.error('[notifications] markRead falló:', e);
        }
    }

    async function markAllRead(): Promise<number | undefined> {
        try {
            const { data } = await axios.post<MarkAllReadResponse>('/api/notifications/read-all');
            const now = new Date().toISOString();
            items.value.forEach((n) => { if (!n.read_at) n.read_at = now; });
            unreadCount.value = 0;
            return data.updated;
        } catch (e) {
            console.error('[notifications] markAllRead falló:', e);
            return undefined;
        }
    }

    async function remove(id: number | string): Promise<void> {
        const wasUnread = items.value.find((n) => n.id === id && !n.read_at);
        try {
            await axios.delete(`/api/notifications/${id}`);
            items.value = items.value.filter((n) => n.id !== id);
            if (wasUnread) unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {
            console.error('[notifications] remove falló:', e);
        }
    }

    /**
     * Incrementa el contador (útil cuando llega una push notification y querés
     * refrescar el bell sin volver a pedir la lista completa).
     */
    function incrementUnread(): void {
        unreadCount.value += 1;
    }

    return {
        items,
        unreadCount,
        isLoading,
        lastFetchedAt,
        fetch,
        markRead,
        markAllRead,
        remove,
        incrementUnread,
    };
});
