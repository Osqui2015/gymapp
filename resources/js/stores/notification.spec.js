import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useNotificationStore } from './notification';
import axios from 'axios';

vi.mock('axios', () => {
    const mockAxios = {
        get: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
        defaults: { headers: { common: {} } },
    };
    return { default: mockAxios, ...mockAxios };
});

describe('useNotificationStore', () => {
    let store;

    beforeEach(() => {
        vi.clearAllMocks();
        store = useNotificationStore();
    });

    it('inicia vacío', () => {
        expect(store.items).toEqual([]);
        expect(store.unreadCount).toBe(0);
        expect(store.isLoading).toBe(false);
        expect(store.lastFetchedAt).toBeNull();
    });

    describe('fetch()', () => {
        it('carga las notificaciones del backend y actualiza unreadCount', async () => {
            axios.get.mockResolvedValueOnce({
                data: [
                    { id: '1', type: 'trainer_comment', data: { title: 'A', body: 'B' }, read_at: null, created_at: '2026-01-01' },
                    { id: '2', type: 'milestone', data: { title: 'C', body: 'D' }, read_at: '2026-01-02T00:00:00Z', created_at: '2026-01-01' },
                ],
            });
            await store.fetch();
            expect(store.items).toHaveLength(2);
            expect(store.unreadCount).toBe(1);
            expect(store.lastFetchedAt).toBeGreaterThan(0);
        });

        it('soporta paginator de Laravel (data.data)', async () => {
            axios.get.mockResolvedValueOnce({
                data: { data: [{ id: '1', data: {}, read_at: null, created_at: 'x' }] },
            });
            await store.fetch();
            expect(store.items).toHaveLength(1);
        });

        it('no rompe si la request falla', async () => {
            axios.get.mockRejectedValue(new Error('network'));
            await expect(store.fetch()).resolves.toBeUndefined();
            expect(store.items).toEqual([]);
        });

        it('setea isLoading durante la request', async () => {
            let resolvePromise;
            axios.get.mockReturnValueOnce(new Promise((r) => { resolvePromise = r; }));
            const promise = store.fetch();
            expect(store.isLoading).toBe(true);
            resolvePromise({ data: [] });
            await promise;
            expect(store.isLoading).toBe(false);
        });
    });

    describe('markRead()', () => {
        it('marca como leída y decrementa unreadCount', async () => {
            axios.get.mockResolvedValueOnce({
                data: [
                    { id: '1', data: { title: 'A' }, read_at: null, created_at: 'x' },
                    { id: '2', data: { title: 'B' }, read_at: null, created_at: 'y' },
                ],
            });
            await store.fetch();
            expect(store.unreadCount).toBe(2);

            axios.post.mockResolvedValueOnce({ data: { ok: true } });
            await store.markRead('1');
            expect(axios.post).toHaveBeenCalledWith('/api/notifications/1/read');
            expect(store.unreadCount).toBe(1);
            expect(store.items[0].read_at).toBeTruthy();
        });

        it('no decrementa si la notif ya estaba leída', async () => {
            axios.get.mockResolvedValueOnce({
                data: [{ id: '1', data: {}, read_at: '2026-01-01', created_at: 'x' }],
            });
            await store.fetch();
            expect(store.unreadCount).toBe(0);

            await store.markRead('1');
            expect(store.unreadCount).toBe(0);
        });
    });

    describe('markAllRead()', () => {
        it('marca todas como leídas y resetea el contador', async () => {
            axios.get.mockResolvedValueOnce({
                data: [
                    { id: '1', data: {}, read_at: null, created_at: 'x' },
                    { id: '2', data: {}, read_at: null, created_at: 'y' },
                    { id: '3', data: {}, read_at: null, created_at: 'z' },
                ],
            });
            await store.fetch();
            expect(store.unreadCount).toBe(3);

            axios.post.mockResolvedValueOnce({ data: { updated: 3 } });
            const updated = await store.markAllRead();
            expect(updated).toBe(3);
            expect(store.unreadCount).toBe(0);
            expect(store.items.every((n) => n.read_at)).toBe(true);
        });
    });

    describe('remove()', () => {
        it('elimina del state y del backend', async () => {
            axios.get.mockResolvedValueOnce({
                data: [
                    { id: '1', data: {}, read_at: null, created_at: 'x' },
                    { id: '2', data: {}, read_at: '2026-01-01', created_at: 'y' },
                ],
            });
            await store.fetch();

            axios.delete.mockResolvedValueOnce({ data: { ok: true } });
            await store.remove('1');
            expect(store.items).toHaveLength(1);
            expect(store.unreadCount).toBe(0); // era 1, queda 0
        });

        it('no afecta unreadCount si la notif eliminada ya estaba leída', async () => {
            axios.get.mockResolvedValueOnce({
                data: [
                    { id: '1', data: {}, read_at: '2026-01-01', created_at: 'x' },
                    { id: '2', data: {}, read_at: null, created_at: 'y' },
                ],
            });
            await store.fetch();
            expect(store.unreadCount).toBe(1);

            axios.delete.mockResolvedValueOnce({ data: { ok: true } });
            await store.remove('1');
            expect(store.unreadCount).toBe(1);
        });
    });

    describe('incrementUnread()', () => {
        it('incrementa el contador', () => {
            expect(store.unreadCount).toBe(0);
            store.incrementUnread();
            expect(store.unreadCount).toBe(1);
            store.incrementUnread();
            expect(store.unreadCount).toBe(2);
        });
    });
});
