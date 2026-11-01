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
 *   - Si el server rechaza la serie de forma PERMANENTE (4xx que no sea 408/429),
 *     la serie NO se borra: queda marcada como fallida para que el usuario la vea
 *     y pueda reintentarla o descartarla. Antes se destruía en silencio.
 *
 * La fecha que se guarda es `payload.fecha` (la fecha real de la serie), NO la fecha
 * del sync. El backend acepta fecha opcional; si esta ausente usa la fecha del server.
 *
 * El estado vive a nivel de MÓDULO (singleton): el composable se instancia en
 * DashboardContent, HistorialContent, RutinasAccordion y ActiveWorkoutModal, y
 * antes cada instancia tenía su propio `pendingCount`, su propio `isSyncing` y
 * sus propios listeners de `online`/`offline`. Eso hacía que el badge mostrara un
 * contador que no correspondía con el total real y que se registraran N listeners.
 *
 * Uso:
 *   const { recordSet, syncNow, pendingCount, isOnline, isSyncing } = useOfflineSeries();
 *   const result = await recordSet({ rutina_nombre, dia, ejercicio_nombre, ... });
 *   // result.status === 'synced' | 'queued' | 'rejected' | 'lost'
 */
import { computed, getCurrentInstance, onMounted, ref } from 'vue';
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
};// ---------------------------------------------------------------------------
// Estado singleton (a nivel de módulo)
// ---------------------------------------------------------------------------

const isOnline = ref(isOnlineRef());
const isSyncing = ref(false);
const pendingCount = ref(0);
const lastError = ref(null);
const lastSyncedAt = ref(null);
/** Series rechazadas de forma permanente: no se reintentan solas. */
const failedSeries = ref([]);

let syncInProgress = false;
let initialized = false;
let detachListeners = null;

/**
 * Lee el estado de conexión REAL y refresca el ref.
 *
 * El ref no puede quedar congelado: con el estado a nivel de módulo se crea
 * una sola vez (al importar el módulo), y si el navegador cambia de estado
 * después, el ref seguía diciendo "online". Por eso cada decisión (guardar una
 * serie, sincronizar) relee `navigator.onLine` en el momento.
 */
const leerOnline = () => {
    const value = isOnlineRef();
    isOnline.value = value;
    return value;
};

const refreshCount = async () => {
    if (!idbAvailable()) {
        pendingCount.value = 0;
        failedSeries.value = [];
        return;
    }
    try {
        const all = await getAllPending();
        pendingCount.value = all.length;
        failedSeries.value = all
            .filter((r) => r.failed)
            .map((r) => ({
                client_id: r.client_id,
                payload: r.payload,
                error: r.permanentError || r.lastError || 'Error desconocido',
                statusCode: r.statusCode ?? null,
            }));
    } catch {
        pendingCount.value = 0;
        failedSeries.value = [];
    }
};

/**
 * Envia UNA serie al backend. Devuelve { status, error?, permanente?, statusCode? }.
 * - 'sent' = llego al server
 * - 'failed' = no se pudo (error de red o del server)
 *
 * `permanente` = true cuando el server rechazó la serie por una razón que
 * no se va a arreglar reintentando (422 validación, 401/403 auth, 404).
 */
const sendOne = async (record) => {
    try {
        await axios.post('/api/historial/guardar', {
            ...record.payload,
            client_id: record.client_id,
        });
        return { status: 'sent' };
    } catch (err) {
        const statusCode = err.response?.status ?? null;
        // 408 (timeout) y 429 (rate limit) sí son transitorios.
        const permanente =
            statusCode !== null &&
            statusCode >= 400 &&
            statusCode < 500 &&
            statusCode !== 408 &&
            statusCode !== 429;

        return {
            status: 'failed',
            permanente,
            statusCode,
            error: err.response?.data?.message || err.message || 'Error desconocido',
        };
    }
};

/**
 * Marca una serie como fallida de forma permanente. NO se borra: queda en
 * IndexedDB para que el usuario la vea y pueda reintentarla o descartarla.
 */
const marcarFallida = async (record, result) => {
    await updatePending(record.client_id, {
        failed: true,
        permanentError: result.error,
        statusCode: result.statusCode,
    });

    failedSeries.value = [
        ...failedSeries.value.filter((f) => f.client_id !== record.client_id),
        {
            client_id: record.client_id,
            payload: record.payload,
            error: result.error,
            statusCode: result.statusCode,
        },
    ];
};

/**
 * Sincroniza todas las pendientes en serie. Se llama automaticamente al volver online
 * o manualmente via syncNow().
 */
const syncNow = async () => {
    if (syncInProgress) return;
    if (!leerOnline()) return;
    if (!idbAvailable()) return;

    syncInProgress = true;
    isSyncing.value = true;
    lastError.value = null;
    try {
        const pending = await getAllPending();
        for (const record of pending) {
            // Las fallidas de forma permanente requieren una acción explícita
            // del usuario (reintentar o descartar). No se reintentan solas.
            if (record.failed) {
                continue;
            }

            const result = await sendOne(record);
            if (result.status === 'sent') {
                await removePending(record.client_id);
                pendingCount.value = Math.max(0, pendingCount.value - 1);
                continue;
            }

            if (result.permanente) {
                // No se borra: se conserva marcada como fallida. Antes se
                // eliminaba del IndexedDB sin avisarle nada al usuario, y la
                // serie se perdía.
                await marcarFallida(record, result);
                lastError.value = `Serie rechazada (${result.statusCode}): ${result.error}`;
                continue;
            }

            await updatePending(record.client_id, {
                attempts: (record.attempts || 0) + 1,
                lastError: result.error,
            });
            // Fallo transitorio: paramos aca para no quemar el server.
            // Reintentara en el proximo online event.
            break;
        }
        lastSyncedAt.value = new Date().toISOString();
        await refreshCount();
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
 * NOTA: si la red esta pero el server da 5xx, también se encola (asumimos
 * que es un problema transitorio y que el user prefiere no perder la serie).
 */
const recordSet = async (payload) => {
    if (!leerOnline()) {
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

    // El server la rechazó de forma permanente (422/401/403/404): reintentar
    // no va a cambiar nada, pero la serie NO se pierde: queda en IndexedDB
    // marcada como fallida y se le avisa al usuario.
    if (result.permanente) {
        lastError.value = result.error;

        if (idbAvailable()) {
            await addPending({
                ...record,
                failed: true,
                permanentError: result.error,
                statusCode: result.statusCode,
            });
            pendingCount.value += 1;
            await refreshCount();
        }

        return {
            status: 'rejected',
            client_id,
            statusCode: result.statusCode,
            error: result.error,
        };
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

/**
 * Descarta una serie fallida: la elimina del IndexedDB y de la lista visible.
 * Es la acción explícita del usuario ("la Perdés"), ya que sola no se borra.
 */
const dismissFailed = async (clientId) => {
    if (idbAvailable()) {
        await removePending(clientId);
    }
    failedSeries.value = failedSeries.value.filter((f) => f.client_id !== clientId);
    pendingCount.value = Math.max(0, pendingCount.value - 1);
};

/**
 * Reintenta una serie fallida: le saca la marca de fallida y la vuelve a encolar.
 */
const retryFailed = async (clientId) => {
    if (!idbAvailable()) return false;

    await updatePending(clientId, { failed: false, permanentError: null });
    await refreshCount();
    await syncNow();

    return true;
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
 * Inicializa listeners y estado. Es idempotente: con el estado a nivel de
 * módulo solo se registran una vez los listeners de `online`/`offline`, por
 * más que el composable se instancie en varios componentes.
 */
const init = async () => {
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

    // refreshCount() tiene que resolverse ANTES de mirar pendingCount: es
    // async, así que sin el await la condición de abajo se evaluaba con el
    // contador todavía en 0 y el sync automático al abrir la app nunca
    // llegaba a dispararse (las series offline quedaban colgadas).
    await refreshCount();

    if (leerOnline() && pendingCount.value > 0) {
        syncNow();
    }
};

/**
 * Limpia listeners y estado. Solo para uso manual / tests: con el estado
 * compartido no se llama automáticamente al desmontar un componente, porque
 * los listeners son de toda la app.
 */
const dispose = () => {
    if (detachListeners) detachListeners();
    detachListeners = null;
    initialized = false;
};

/**
 * Resetea el estado singleton. Solo para tests.
 */
const resetOfflineSeriesState = () => {
    dispose();
    isOnline.value = isOnlineRef();
    isSyncing.value = false;
    pendingCount.value = 0;
    lastError.value = null;
    lastSyncedAt.value = null;
    failedSeries.value = [];
    syncInProgress = false;
};

/**
 * Computeds a nivel de módulo: todas las instancias del composable devuelven
 * EXACTAMENTE las mismas refs, así que el estado es realmente compartido y no
 * solo los valores.
 */
const computedState = {
    isOnline: computed(() => isOnline.value),
    isSyncing: computed(() => isSyncing.value),
    pendingCount: computed(() => pendingCount.value),
    lastError: computed(() => lastError.value),
    lastSyncedAt: computed(() => lastSyncedAt.value),
    failedSeries: computed(() => failedSeries.value),
    failedCount: computed(() => failedSeries.value.length),
    idbAvailable: computed(() => idbAvailable()),
};

export function useOfflineSeries() {
    // Si hay component instance, usamos el ciclo de vida de Vue para arrancar
    // el composable cuando el componente está montado.
    if (getCurrentInstance()) {
        onMounted(init);
    } else {
        // Sin component instance: init lazy (tests, scripts).
        init();
    }

    return {
        // Estado reactivo (compartido)
        ...computedState,

        // API
        recordSet,
        syncNow,
        refreshCount,
        dismissFailed,
        retryFailed,
    };
}

export { resetOfflineSeriesState };
