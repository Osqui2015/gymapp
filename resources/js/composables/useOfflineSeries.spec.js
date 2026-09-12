import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

// vi.hoisted se ejecuta ANTES de los vi.mock factories, asi que los mocks
// ya estan inicializados cuando el composable los importa.
const { mockDb, mockPost } = vi.hoisted(() => {
    const mockDb = {
        isAvailable: vi.fn(() => true),
        addPending: vi.fn(async (r) => r),
        getAllPending: vi.fn(async () => []),
        countPending: vi.fn(async () => 0),
        removePending: vi.fn(async () => {}),
        updatePending: vi.fn(async (id, patch) => ({ id, ...patch })),
        clearAllPending: vi.fn(async () => {}),
    };
    const mockPost = vi.fn();
    return { mockDb, mockPost };
});

vi.mock('./useOfflineDB', () => mockDb);

vi.mock('axios', () => ({
    default: {
        post: (...args) => mockPost(...args),
    },
}));

import { useOfflineSeries } from './useOfflineSeries';

describe('useOfflineSeries', () => {
    beforeEach(() => {
        // Defaults: online, sin pendientes, post OK.
        Object.defineProperty(navigator, 'onLine', {
            value: true,
            writable: true,
            configurable: true,
        });
        mockDb.getAllPending.mockResolvedValue([]);
        mockDb.countPending.mockResolvedValue(0);
        mockDb.addPending.mockImplementation(async (r) => r);
        mockDb.removePending.mockResolvedValue();
        mockPost.mockReset();
        mockPost.mockResolvedValue({ data: { message: 'Guardado', count: 1 } });
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    const basePayload = () => ({
        rutina_nombre: 'Full Body A',
        dia: 'Lunes',
        ejercicio_nombre: 'Sentadilla',
        series_numero: 1,
        reps_min: '8',
        reps_max: '10',
        descanso_min: 2,
        peso: 80,
    });

    it('online + post OK: devuelve synced y NO encola', async () => {
        const { recordSet, pendingCount } = useOfflineSeries();
        const result = await recordSet(basePayload());

        expect(result.status).toBe('synced');
        expect(mockPost).toHaveBeenCalledTimes(1);
        expect(mockPost.mock.calls[0][1]).toMatchObject({
            rutina_nombre: 'Full Body A',
            ejercicio_nombre: 'Sentadilla',
        });
        expect(result.client_id).toBeTruthy();
        // No se encolo nada.
        expect(mockDb.addPending).not.toHaveBeenCalled();
        await nextTick();
        expect(pendingCount.value).toBe(0);
    });

    it('offline: encola en IndexedDB y devuelve queued', async () => {
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        const { recordSet, pendingCount, isOnline } = useOfflineSeries();
        await nextTick();
        expect(isOnline.value).toBe(false);

        const result = await recordSet(basePayload());

        expect(result.status).toBe('queued');
        expect(mockDb.addPending).toHaveBeenCalledTimes(1);
        const record = mockDb.addPending.mock.calls[0][0];
        expect(record.payload).toMatchObject({ ejercicio_nombre: 'Sentadilla' });
        expect(record.client_id).toBe(result.client_id);
        expect(pendingCount.value).toBe(1);
    });

    it('online + post falla (5xx): encola y devuelve queued', async () => {
        mockPost.mockRejectedValueOnce(new Error('Network Error'));
        const { recordSet, pendingCount } = useOfflineSeries();

        const result = await recordSet(basePayload());

        expect(result.status).toBe('queued');
        expect(mockDb.addPending).toHaveBeenCalledTimes(1);
        expect(pendingCount.value).toBe(1);
    });

    it('syncNow() sincroniza todas las pendientes y las elimina al exito', async () => {
        const pending = [
            { client_id: 'a-1', payload: basePayload(), createdAt: '2026-09-01', attempts: 0 },
            { client_id: 'b-2', payload: basePayload(), createdAt: '2026-09-02', attempts: 0 },
        ];
        mockDb.getAllPending.mockResolvedValue(pending);
        mockPost.mockResolvedValue({ data: { ok: true } });

        const { syncNow, pendingCount } = useOfflineSeries();
        // Disparar onMounted (que llama refreshCount)
        await nextTick();
        await nextTick();
        expect(pendingCount.value).toBe(2);

        await syncNow();

        expect(mockPost).toHaveBeenCalledTimes(2);
        expect(mockDb.removePending).toHaveBeenCalledWith('a-1');
        expect(mockDb.removePending).toHaveBeenCalledWith('b-2');
        expect(pendingCount.value).toBe(0);
    });

    it('syncNow() se detiene en la primera falla y no elimina esa pendiente', async () => {
        const pending = [
            { client_id: 'ok', payload: basePayload(), createdAt: '2026-09-01', attempts: 0 },
            { client_id: 'fail', payload: basePayload(), createdAt: '2026-09-02', attempts: 0 },
            {
                client_id: 'never-tried',
                payload: basePayload(),
                createdAt: '2026-09-03',
                attempts: 0,
            },
        ];
        mockDb.getAllPending.mockResolvedValue(pending);
        mockPost
            .mockResolvedValueOnce({ data: { ok: true } })
            .mockRejectedValueOnce(new Error('boom'));

        const { syncNow } = useOfflineSeries();
        await nextTick();
        await nextTick();

        await syncNow();

        expect(mockPost).toHaveBeenCalledTimes(2);
        expect(mockDb.removePending).toHaveBeenCalledWith('ok');
        expect(mockDb.removePending).not.toHaveBeenCalledWith('fail');
        expect(mockDb.removePending).not.toHaveBeenCalledWith('never-tried');
        expect(mockDb.updatePending).toHaveBeenCalledWith(
            'fail',
            expect.objectContaining({
                attempts: 1,
            })
        );
    });

    it('no sincroniza si esta offline', async () => {
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        mockDb.getAllPending.mockResolvedValue([
            { client_id: 'a', payload: basePayload(), attempts: 0 },
        ]);
        const { syncNow } = useOfflineSeries();
        await nextTick();

        await syncNow();

        expect(mockPost).not.toHaveBeenCalled();
    });

    it('auto-sync al volver online: dispara syncNow cuando hay pendientes', async () => {
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        mockDb.getAllPending.mockResolvedValue([
            { client_id: 'a', payload: basePayload(), attempts: 0 },
        ]);
        mockPost.mockResolvedValue({ data: { ok: true } });
        const { isOnline, pendingCount } = useOfflineSeries();
        await nextTick();
        await nextTick();
        expect(pendingCount.value).toBe(1);

        // Simular volver online
        Object.defineProperty(navigator, 'onLine', {
            value: true,
            writable: true,
            configurable: true,
        });
        window.dispatchEvent(new Event('online'));
        await nextTick();
        await nextTick();
        await nextTick();

        expect(isOnline.value).toBe(true);
        expect(mockPost).toHaveBeenCalledWith(
            '/api/historial/guardar',
            expect.objectContaining({
                client_id: 'a',
            })
        );
    });

    it('sin IndexedDB: offline devuelve status lost', async () => {
        mockDb.isAvailable.mockReturnValue(false);
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        const { recordSet, idbAvailable } = useOfflineSeries();
        await nextTick();
        expect(idbAvailable.value).toBe(false);

        const result = await recordSet(basePayload());

        expect(result.status).toBe('lost');
    });
});
