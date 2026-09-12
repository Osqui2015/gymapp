/**
 * useOfflineSeries — Composable principal para registro de series offline-first.
 *
 * Comportamiento:
 *   - Si hay red: POST directo a /api/historial/guardar, devuelve { status: 'synced' }.
 *   - Si no hay red: guarda en IndexedDB con client_id, devuelve { status: 'queued' }.
 *   - Cuando vuelve la conexion (evento 'online'), sincroniza automaticamente todas
 *     las pendientes. Cada una se manda al backend y se elimina de IndexedDB al confirmar.
 *   - Si una pendiente falla al sincronizar, se incrementa attempts y se guarda el error.
 *     Reintenta en el proximo online event o cuando se llame syncNow().
 *
 * La fecha que se guarda es `payload.fecha` (la fecha real de la serie), NO la fecha
 * del sync. El backend acepta fecha opcional; si esta ausente usa la fecha del server.
 *
 * Uso:
 *   const { recordSet, syncNow, pendingCount, isOnline, isSyncing } = useOfflineSeries();
 *   const result = await recordSet({ rutina_nombre, dia, ejercicio_nombre, ... });
 *   // result.status === 'synced' | 'queued'
 */
import { computed, getCurrentInstance, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import {
    addPending,
    getAllPending,
    isAvailable as idbAvailable,
    removePending,
    updatePending,
} from './useOfflineDB';

const generateId = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'cs-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
};

const isOnlineRef = () => {
    return typeof navigator !== 'undefined' ? navigator.onLine : true;
};

export function useOfflineSeries() {
    const isOnline = ref(isOnlineRef());
    const isSyncing = ref(false);
    const pendingCount = ref(0);
    const lastError = ref(null);
    const lastSyncedAt = ref(null);

    let syncInProgress = false;
    let initialized = false;
    let detachListeners = null;

    const refreshCount = async () => {
        if (!idbAvailable()) {
            pendingCount.value = 0;
            return;
        }
        try {
            const all = await getAllPending();
            pendingCount.value = all.length;
        } catch {
            pendingCount.value = 0;
        }
    };

    /**
     * Envia UNA serie al backend. Devuelve { status, error? }.
     * - 'sent' = llego al server
     * - 'failed' = no se pudo (error de red o del server)
     */
    const sendOne = async (record) => {
        try {
            await axios.post('/api/historial/guardar', {
                ...record.payload,
                client_id: record.client_id,
            });
            return { status: 'sent' };
        } catch (err) {
            return {
                status: 'failed',
                error: err.response?.data?.message || err.message || 'Error desconocido',
            };
        }
    };

    /**
     * Sincroniza todas las pendientes en serie. Se llama automaticamente al volver online
     * o manualmente via syncNow().
     */
    const syncNow = async () => {
        if (syncInProgress) return;
        if (!isOnline.value) return;
        if (!idbAvailable()) return;

        syncInProgress = true;
        isSyncing.value = true;
        lastError.value = null;
        try {
            const pending = await getAllPending();
            for (const record of pending) {
                const result = await sendOne(record);
                if (result.status === 'sent') {
                    await removePending(record.client_id);
                    pendingCount.value = Math.max(0, pendingCount.value - 1);
                } else {
                    await updatePending(record.client_id, {
                        attempts: (record.attempts || 0) + 1,
                        lastError: result.error,
                    });
                    // Si una falla, paramos aca para no quemar el server. Reintentara
                    // en el proximo online event.
                    break;
                }
            }
            lastSyncedAt.value = new Date().toISOString();
        } finally {
            syncInProgress = false;
            isSyncing.value = false;
        }
    };

    /**
     * API principal: registra una serie.
     *   - Si hay red: POST directo, devuelve { status: 'synced' }.
     *   - Si no hay red o falla el POST: guarda en IndexedDB, devuelve { status: 'queued' }.
     *
     * NOTA: si la red esta pero el server da 5xx, tambi\u00e9n se encola (asumimos
     * que es un problema transitorio y que el user prefiere no perder la serie).
     */
    const recordSet = async (payload) => {
        if (!isOnline.value) {
            if (!idbAvailable()) {
                // Sin red y sin IDB: la serie se pierde. Caso raro (modo privado).
                return { status: 'lost', reason: 'offline_y_sin_idb' };
            }
            const record = {
                client_id: generateId(),
                payload,
                createdAt: new Date().toISOString(),
                attempts: 0,
                lastError: null,
            };
            await addPending(record);
            pendingCount.value += 1;
            return { status: 'queued', client_id: record.client_id };
        }

        // Hay red: intentamos directo.
        const client_id = generateId();
        const record = {
            client_id,
            payload,
            createdAt: new Date().toISOString(),
            attempts: 0,
            lastError: null,
        };
        const result = await sendOne(record);
        if (result.status === 'sent') {
            lastSyncedAt.value = new Date().toISOString();
            return { status: 'synced', client_id };
        }

        // Fallo de red transitorio o 5xx. Encolamos para reintento.
        if (idbAvailable()) {
            await addPending(record);
            pendingCount.value += 1;
        }
        return {
            status: 'queued',
            client_id,
            error: result.error,
        };
    };

    const handleOnline = () => {
        isOnline.value = true;
        // Sincronizar automaticamente al volver online.
        syncNow();
    };

    const handleOffline = () => {
        isOnline.value = false;
    };

    /**
     * Inicializa listeners y estado. Se llama desde onMounted (cuando se usa
     * dentro de un componente Vue) o automaticamente en la primera invocacion
     * del composable (cuando se usa fuera, p.ej. en tests).
     */
    const init = () => {
        if (initialized) return;
        initialized = true;
        if (typeof window !== 'undefined') {
            window.addEventListener('online', handleOnline);
            window.addEventListener('offline', handleOffline);
            detachListeners = () => {
                window.removeEventListener('online', handleOnline);
                window.removeEventListener('offline', handleOffline);
            };
        }
        refreshCount();
        if (isOnline.value && pendingCount.value > 0) {
            syncNow();
        }
    };

    /**
     * Limpia listeners. Llamar manualmente si se uso el composable fuera
     * de un componente (sin onBeforeUnmount automatico).
     */
    const dispose = () => {
        if (detachListeners) detachListeners();
        detachListeners = null;
    };

    // Si hay component instance, usamos el ciclo de vida de Vue.
    if (getCurrentInstance()) {
        onMounted(init);
        onBeforeUnmount(dispose);
    } else {
        // Sin component instance: init lazy, dispose manual.
        init();
    }

    return {
        // Estado reactivo
        isOnline: computed(() => isOnline.value),
        isSyncing: computed(() => isSyncing.value),
        pendingCount: computed(() => pendingCount.value),
        lastError: computed(() => lastError.value),
        lastSyncedAt: computed(() => lastSyncedAt.value),
        idbAvailable: computed(() => idbAvailable()),

        // API
        recordSet,
        syncNow,
        refreshCount,
    };
}
