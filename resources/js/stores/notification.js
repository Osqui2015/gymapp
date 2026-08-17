import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Store de notificaciones in-app (centro de notificaciones, distinto de toasts).
 *
 * Las notificaciones PERSISTEN en la DB. El store las carga, mantiene un
 * cache reactivo, y expone acciones para marcar como leídas / eliminar.
 *
 * Polling cada 60s (configurable) para refrescar — más adelante se puede
 * sumar WebSocket realtime con Laravel Echo si el costo de polling es alto.
 */
export const useNotificationStore = defineStore('notification', {
    state: () => ({
        items: [],
        unreadCount: 0,
        isLoading: false,
        lastFetchedAt: null,
    }),

    actions: {
        /**
         * Carga las notificaciones del backend.
         * @param {boolean} unreadOnly
         */
        async fetch(unreadOnly = false) {
            this.isLoading = true;
            try {
                const { data } = await axios.get('/api/notifications', {
                    params: unreadOnly ? { unread_only: 1, per_page: 50 } : { per_page: 50 },
                });
                // Laravel paginator entrega `data` adentro, o array directo
                this.items = Array.isArray(data) ? data : (data.data || []);
                this.unreadCount = this.items.filter((n) => !n.read_at).length;
                this.lastFetchedAt = Date.now();
            } catch (e) {
                console.error('[notifications] fetch falló:', e);
            } finally {
                this.isLoading = false;
            }
        },

        async markRead(id) {
            try {
                await axios.post(`/api/notifications/${id}/read`);
                const item = this.items.find((n) => n.id === id);
                if (item && !item.read_at) {
                    item.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch (e) {
                console.error('[notifications] markRead falló:', e);
            }
        },

        async markAllRead() {
            try {
                const { data } = await axios.post('/api/notifications/read-all');
                const now = new Date().toISOString();
                this.items.forEach((n) => { if (!n.read_at) n.read_at = now; });
                this.unreadCount = 0;
                return data.updated;
            } catch (e) {
                console.error('[notifications] markAllRead falló:', e);
            }
        },

        async remove(id) {
            const wasUnread = this.items.find((n) => n.id === id && !n.read_at);
            try {
                await axios.delete(`/api/notifications/${id}`);
                this.items = this.items.filter((n) => n.id !== id);
                if (wasUnread) this.unreadCount = Math.max(0, this.unreadCount - 1);
            } catch (e) {
                console.error('[notifications] remove falló:', e);
            }
        },

        /**
         * Incrementa el contador (útil cuando llega una push notification y querés
         * refrescar el bell sin volver a pedir la lista completa).
         */
        incrementUnread() {
            this.unreadCount += 1;
        },
    },
});
