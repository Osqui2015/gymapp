import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

// vi.hoisted se ejecuta ANTES de los vi.mock factories, asi que los mocks
// ya estan inicializados cuando el composable los importa.
const { mockDb, mockPost, store } = vi.hoisted(() => {
    // Store falso en memoria: refleja lo que hace IndexedDB de verdad, asi
    // los contadores (pendingCount, failedCount) son reales y no un numero
    // que el propio test define.
    const store = [];

    const mockDb = {
        isAvailable: vi.fn(() => true),
        addPending: vi.fn(async (r) => {
            store.push(r);
            return r;
        }),
        getAllPending: vi.fn(async () => store.map((r) => ({ ...r }))),
        countPending: vi.fn(async () => store.length),
        removePending: vi.fn(async (id) => {
            const i = store.findIndex((r) => r.client_id === id);
            if (i >= 0) store.splice(i, 1);
        }),
        updatePending: vi.fn(async (id, patch) => {
            const rec = store.find((r) => r.client_id === id);
            if (!rec) return null;
            Object.assign(rec, patch);
            return { ...rec };
        }),
        clearAllPending: vi.fn(async () => {
            store.length = 0;
        }),
    };

    const mockPost = vi.fn();

    return { mockDb, mockPost, store };
});

vi.mock('./useOfflineDB', () => mockDb);

vi.mock('axios', () => ({
    default: {
        post: (...args) => mockPost(...args),
    },
}));

import { resetOfflineSeriesState, useOfflineSeries } from './useOfflineSeries';

// El estado del composable vive a nivel de módulo (singleton), así que hay que
// resetearlo entre tests para que no se filtren refs de un test al siguiente.
const flush = async () => {
    for (let i = 0; i < 10; i += 1) {
        await nextTick();
    }
};

describe('useOfflineSeries', () => {
    beforeEach(() => {
        // Defaults: online, sin pendientes, post OK.
        Object.defineProperty(navigator, 'onLine', {
            value: true,
            writable: true,
            configurable: true,
        });
        store.length = 0;
        mockDb.isAvailable.mockReturnValue(true);
        mockPost.mockReset();
        mockPost.mockResolvedValue({ data: { message: 'Guardado', count: 1 } });
        vi.useFakeTimers();
        resetOfflineSeriesState();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
        resetOfflineSeriesState();
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

    const encolar = (clientId, extra = {}) => ({
        client_id: clientId,
        payload: basePayload(),
        createdAt: '2026-09-01',
        attempts: 0,
        ...extra,
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
        await flush();
        expect(pendingCount.value).toBe(0);
    });

    it('offline: encola en IndexedDB y devuelve queued', async () => {
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        const { recordSet, pendingCount, isOnline } = useOfflineSeries();
        await flush();
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
        const { syncNow, pendingCount, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('a-1'), encolar('b-2'));
        await refreshCount();
        expect(pendingCount.value).toBe(2);

        await syncNow();

        expect(mockPost).toHaveBeenCalledTimes(2);
        expect(mockDb.removePending).toHaveBeenCalledWith('a-1');
        expect(mockDb.removePending).toHaveBeenCalledWith('b-2');
        expect(pendingCount.value).toBe(0);
    });

    it('syncNow() se detiene en la primera falla y no elimina esa pendiente', async () => {
        const { syncNow, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('ok'), encolar('fail'), encolar('never-tried'));
        await refreshCount();

        mockPost
            .mockResolvedValueOnce({ data: { ok: true } })
            .mockRejectedValueOnce(new Error('boom'));

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
        const { syncNow, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('a'));
        await refreshCount();

        await syncNow();

        expect(mockPost).not.toHaveBeenCalled();
    });

    it('auto-sync al volver online: dispara syncNow cuando hay pendientes', async () => {
        Object.defineProperty(navigator, 'onLine', {
            value: false,
            writable: true,
            configurable: true,
        });
        const { isOnline, pendingCount, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('a'));
        await refreshCount();
        expect(pendingCount.value).toBe(1);

        mockPost.mockResolvedValue({ data: { ok: true } });

        // Simular volver online
        Object.defineProperty(navigator, 'onLine', {
            value: true,
            writable: true,
            configurable: true,
        });
        window.dispatchEvent(new Event('online'));
        await flush();

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
        await flush();
        expect(idbAvailable.value).toBe(false);

        const result = await recordSet(basePayload());

        expect(result.status).toBe('lost');
    });

    // === Errores permanentes (4xx) ===

    const errorConStatus = (status, message = 'Error') => {
        const err = new Error(message);
        err.response = { status, data: { message } };
        return err;
    };

    it('422 (validación): conserva la serie marcada como fallida y devuelve rejected', async () => {
        mockPost.mockRejectedValueOnce(errorConStatus(422, 'El peso es requerido'));

        const { recordSet, pendingCount, failedSeries } = useOfflineSeries();
        const result = await recordSet(basePayload());

        expect(result.status).toBe('rejected');
        expect(result.statusCode).toBe(422);
        expect(result.error).toBe('El peso es requerido');
        // La serie NO se pierde: queda en IndexedDB marcada como fallida para
        // que el usuario la vea y pueda reintentarla o descartarla.
        expect(mockDb.addPending).toHaveBeenCalledTimes(1);
        expect(mockDb.addPending.mock.calls[0][0]).toMatchObject({
            failed: true,
            permanentError: 'El peso es requerido',
            statusCode: 422,
        });
        expect(mockDb.removePending).not.toHaveBeenCalled();
        await flush();
        expect(pendingCount.value).toBe(1);
        expect(failedSeries.value.length).toBe(1);
    });

    it('403 (auth): conserva la serie marcada como fallida', async () => {
        mockPost.mockRejectedValueOnce(errorConStatus(403, 'No autorizado'));

        const { recordSet, failedSeries } = useOfflineSeries();
        const result = await recordSet(basePayload());

        expect(result.status).toBe('rejected');
        expect(mockDb.addPending).toHaveBeenCalledTimes(1);
        await flush();
        expect(failedSeries.value.length).toBe(1);
    });

    it('429 (rate limit): es transitorio, se encola', async () => {
        mockPost.mockRejectedValueOnce(errorConStatus(429, 'Demasiadas peticiones'));

        const { recordSet, pendingCount } = useOfflineSeries();
        const result = await recordSet(basePayload());

        expect(result.status).toBe('queued');
        expect(mockDb.addPending).toHaveBeenCalledTimes(1);
        expect(pendingCount.value).toBe(1);
    });

    it('syncNow NO descarta las pendientes con error permanente: las marca como fallidas', async () => {
        const { syncNow, pendingCount, failedSeries, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('mala-1'), encolar('buena-2'));
        await refreshCount();

        mockPost
            .mockRejectedValueOnce(errorConStatus(422, 'Payload inválido'))
            .mockResolvedValueOnce({ data: { message: 'Guardado' } });

        await syncNow();

        // La mala NO se borra: queda marcada como fallida. La buena se sincroniza.
        expect(mockDb.removePending).not.toHaveBeenCalledWith('mala-1');
        expect(mockDb.removePending).toHaveBeenCalledWith('buena-2');
        expect(mockDb.updatePending).toHaveBeenCalledWith(
            'mala-1',
            expect.objectContaining({ failed: true, permanentError: 'Payload inválido' })
        );
        expect(mockPost).toHaveBeenCalledTimes(2);
        expect(pendingCount.value).toBe(1);
        expect(failedSeries.value.length).toBe(1);
    });

    it('las fallidas no se reintentan solas en el siguiente syncNow', async () => {
        const { syncNow, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('mala-1', { failed: true }));
        await refreshCount();

        mockPost.mockResolvedValue({ data: { message: 'Guardado' } });

        await syncNow();

        expect(mockPost).not.toHaveBeenCalled();
        expect(mockDb.removePending).not.toHaveBeenCalled();
    });

    it('dismissFailed elimina la serie fallida', async () => {
        const { dismissFailed, failedSeries, pendingCount, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('mala-1', { failed: true }));
        await refreshCount();
        expect(failedSeries.value.length).toBe(1);
        expect(pendingCount.value).toBe(1);

        await dismissFailed('mala-1');

        expect(mockDb.removePending).toHaveBeenCalledWith('mala-1');
        expect(failedSeries.value.length).toBe(0);
        expect(pendingCount.value).toBe(0);
    });

    it('retryFailed saca la marca de fallida y vuelve a encolar', async () => {
        const { retryFailed, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('mala-1', { failed: true }));
        await refreshCount();

        mockPost.mockResolvedValue({ data: { message: 'Guardado' } });

        await retryFailed('mala-1');

        expect(mockDb.updatePending).toHaveBeenCalledWith(
            'mala-1',
            expect.objectContaining({ failed: false })
        );
        // Y se sincroniza en el acto.
        expect(mockPost).toHaveBeenCalledWith(
            '/api/historial/guardar',
            expect.objectContaining({ client_id: 'mala-1' })
        );
    });

    it('auto-sync al iniciar con pendientes: sincroniza sin esperar al evento online', async () => {
        // Regresión: refreshCount() era async y no se-awaited, así que
        // pendingCount.value seguía en 0 cuando se evaluaba la condición y
        // el sync inicial NUNCA se disparaba (las series offline quedaban
        // colgadas hasta que la conexión fluctuara).
        store.push(encolar('pendiente-1'));
        mockPost.mockResolvedValue({ data: { message: 'Guardado' } });

        useOfflineSeries();
        await flush();

        expect(mockPost).toHaveBeenCalledWith(
            '/api/historial/guardar',
            expect.objectContaining({ client_id: 'pendiente-1' })
        );
    });

    it('el estado es compartido entre instancias (singleton)', async () => {
        const a = useOfflineSeries();
        const b = useOfflineSeries();

        expect(a.pendingCount).toBe(b.pendingCount);
        expect(a.isSyncing).toBe(b.isSyncing);
        expect(a.failedCount).toBe(b.failedCount);
        expect(a.failedSeries).toBe(b.failedSeries);
    });

    it('syncNow con fallo transitorio corta la cola (se reintenta después)', async () => {
        const { syncNow, refreshCount } = useOfflineSeries();
        await flush();

        store.push(encolar('a-1'), encolar('b-2'));
        await refreshCount();

        mockPost.mockRejectedValueOnce(new Error('Network Error'));

        await syncNow();

        expect(mockPost).toHaveBeenCalledTimes(1);
        expect(mockDb.removePending).not.toHaveBeenCalled();
        expect(mockDb.updatePending).toHaveBeenCalledWith(
            'a-1',
            expect.objectContaining({ attempts: 1 })
        );
    });
});
