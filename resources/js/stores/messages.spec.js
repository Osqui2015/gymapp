import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import axios from 'axios';
import { useMessagesStore } from './messages';

vi.mock('axios');

describe('useMessagesStore', () => {
    let store;

    beforeEach(() => {
        store = useMessagesStore();
        // Reset state
        store.conversations = [];
        store.activeMessages = [];
        store.activeUserId = null;
        store.isLoading = false;
        store.isSending = false;
        store.totalUnread = 0;
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('fetchConversations', () => {
        it('loads conversations and computes totalUnread', async () => {
            axios.get.mockResolvedValue({
                data: {
                    conversations: [
                        { other_user: { id: 1 }, unread_count: 3, last_message: { id: 100 } },
                        { other_user: { id: 2 }, unread_count: 5, last_message: { id: 101 } },
                    ],
                },
            });

            await store.fetchConversations();

            expect(store.conversations).toHaveLength(2);
            expect(store.totalUnread).toBe(8);
            expect(store.isLoading).toBe(false);
        });

        it('handles empty conversations list', async () => {
            axios.get.mockResolvedValue({ data: { conversations: [] } });

            await store.fetchConversations();

            expect(store.conversations).toEqual([]);
            expect(store.totalUnread).toBe(0);
        });

        it('handles missing conversations key', async () => {
            axios.get.mockResolvedValue({ data: {} });

            await store.fetchConversations();

            expect(store.conversations).toEqual([]);
        });

        it('logs error and stops loading on failure', async () => {
            const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
            axios.get.mockRejectedValue(new Error('Network error'));

            await store.fetchConversations();

            expect(consoleError).toHaveBeenCalled();
            expect(store.isLoading).toBe(false);
            consoleError.mockRestore();
        });
    });

    describe('openConversation', () => {
        it('sets activeUserId and loads messages', async () => {
            axios.get.mockResolvedValue({
                data: { data: [{ id: 1, body: 'Hola' }, { id: 2, body: 'Mundo' }] },
            });

            await store.openConversation(42);

            expect(store.activeUserId).toBe(42);
            expect(store.activeMessages).toHaveLength(2);
        });

        it('resets to empty on error', async () => {
            const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
            axios.get.mockRejectedValue(new Error('Network error'));

            await store.openConversation(42);

            expect(store.activeMessages).toEqual([]);
            consoleError.mockRestore();
        });
    });

    describe('sendMessage', () => {
        beforeEach(() => {
            store.activeUserId = 42;
            store.conversations = [
                { other_user: { id: 42 }, unread_count: 1, last_message: null },
            ];
        });

        it('sends message and appends to activeMessages', async () => {
            const newMessage = { id: 200, sender_id: 1, recipient_id: 42, body: 'Test' };
            axios.post.mockResolvedValue({ data: { data: newMessage } });

            await store.sendMessage('Test');

            expect(axios.post).toHaveBeenCalledWith('/api/messages', {
                recipient_id: 42,
                body: 'Test',
            });
            expect(store.activeMessages).toHaveLength(1);
            expect(store.activeMessages[0]).toEqual(newMessage);
        });

        it('does nothing with empty body', async () => {
            await store.sendMessage('   ');

            expect(axios.post).not.toHaveBeenCalled();
        });

        it('does nothing without activeUserId', async () => {
            store.activeUserId = null;
            await store.sendMessage('Test');

            expect(axios.post).not.toHaveBeenCalled();
        });

        it('updates existing conversation in sidebar', async () => {
            const newMessage = { id: 200, sender_id: 1, recipient_id: 42, body: 'Hi' };
            axios.post.mockResolvedValue({ data: { data: newMessage } });

            await store.sendMessage('Hi');

            expect(store.conversations[0].last_message).toEqual(newMessage);
        });

        it('refetches conversations when message is to a new user', async () => {
            const newMessage = { id: 200, sender_id: 1, recipient_id: 99, body: 'Hi' };
            axios.post.mockResolvedValue({ data: { data: newMessage } });
            axios.get.mockResolvedValue({ data: { conversations: [] } });

            await store.sendMessage('Hi');

            expect(axios.get).toHaveBeenCalledWith('/api/messages/conversations');
        });
    });

    describe('markConversationRead', () => {
        it('resets unread_count and totalUnread locally', async () => {
            store.conversations = [
                { other_user: { id: 42 }, unread_count: 5 },
                { other_user: { id: 99 }, unread_count: 3 },
            ];
            store.totalUnread = 8;
            axios.post.mockResolvedValue({});

            await store.markConversationRead(42);

            expect(store.conversations[0].unread_count).toBe(0);
            expect(store.totalUnread).toBe(3);
        });
    });

    describe('closeConversation', () => {
        it('resets active state', () => {
            store.activeUserId = 42;
            store.activeMessages = [{ id: 1 }];

            store.closeConversation();

            expect(store.activeUserId).toBe(null);
            expect(store.activeMessages).toEqual([]);
        });
    });
});
