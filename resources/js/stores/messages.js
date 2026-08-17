import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Store de mensajería 1-a-1.
 *
 * Mantiene:
 *   - conversations: lista de conversaciones con último mensaje + unread count
 *   - activeMessages: mensajes de la conversación actualmente abierta
 *   - activeUserId: con quién estamos chateando
 *
 * Polling cada 15s mientras hay una conversación abierta.
 */
export const useMessagesStore = defineStore('messages', {
    state: () => ({
        conversations: [],
        activeMessages: [],
        activeUserId: null,
        isLoading: false,
        isSending: false,
        totalUnread: 0,
    }),

    actions: {
        async fetchConversations() {
            this.isLoading = true;
            try {
                const { data } = await axios.get('/api/messages/conversations');
                this.conversations = data.conversations || [];
                this.totalUnread = this.conversations.reduce((sum, c) => sum + (c.unread_count || 0), 0);
            } catch (e) {
                console.error('[messages] fetch conversations error:', e);
            } finally {
                this.isLoading = false;
            }
        },

        async openConversation(userId) {
            this.activeUserId = userId;
            try {
                const { data } = await axios.get(`/api/messages/with/${userId}`);
                this.activeMessages = data.data || [];
            } catch (e) {
                console.error('[messages] open conversation error:', e);
                this.activeMessages = [];
            }
        },

        async sendMessage(body) {
            if (!body?.trim() || !this.activeUserId) return;
            this.isSending = true;
            try {
                const { data } = await axios.post('/api/messages', {
                    recipient_id: this.activeUserId,
                    body: body.trim(),
                });
                this.activeMessages.push(data.data);
                // Actualizar la conversación en la lista lateral
                this.refreshConversationInList(data.data);
                return data.data;
            } catch (e) {
                console.error('[messages] send error:', e);
                throw e;
            } finally {
                this.isSending = false;
            }
        },

        refreshConversationInList(message) {
            const idx = this.conversations.findIndex(
                (c) => c.other_user.id === message.sender_id || c.other_user.id === message.recipient_id
            );
            if (idx >= 0) {
                this.conversations[idx].last_message = message;
            } else {
                // Conversación nueva, refetch completo
                this.fetchConversations();
            }
        },

        async markConversationRead(userId) {
            try {
                await axios.post(`/api/messages/with/${userId}/read-all`);
                // Resetear unread count localmente
                const conv = this.conversations.find((c) => c.other_user.id === userId);
                if (conv) {
                    conv.unread_count = 0;
                    this.totalUnread = this.conversations.reduce((sum, c) => sum + (c.unread_count || 0), 0);
                }
            } catch (e) {
                console.error('[messages] mark read error:', e);
            }
        },

        closeConversation() {
            this.activeUserId = null;
            this.activeMessages = [];
        },
    },
});
