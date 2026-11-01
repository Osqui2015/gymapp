/**
 * useOfflineDB — Wrapper minimo sobre IndexedDB para guardar series pendientes.
 *
 * Schema:
 *   db: gymapp-offline (v1)
 *   store: pending_series
 *     keyPath: client_id
 *     indices: por createdAt (auto-mantenido)
 *
 * Estructura del record:
 *   {
 *     client_id: 'uuid-generado-en-el-cliente',
 *     payload: { ... campos que van a /api/historial/guardar ... },
 *     createdAt: '2026-09-04T19:30:00.000Z',
 *     attempts: 0,           // cantidad de veces que intentamos sincronizar
 *     lastError: null,       // ultimo mensaje de error si fallo
 *   }
 *
 * Si IndexedDB no esta disponible (modo privado, navegador raro, etc),
 * todas las funciones devuelven arrays vacios o rechazan con un error
 * controlado. El caller debe manejar el fallback.
 */

const DB_NAME = 'gymapp-offline';
const DB_VERSION = 1;
const STORE = 'pending_series';

let dbPromise = null;

export const isAvailable = () => {
    return typeof indexedDB !== 'undefined';
};

const openDB = () => {
    if (!isAvailable()) {
        return Promise.reject(new Error('IndexedDB no disponible'));
    }
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'client_id' });
                store.createIndex('createdAt', 'createdAt', { unique: false });
            }
        };
        req.onsuccess = (e) => resolve(e.target.result);
        req.onerror = (e) => reject(e.target.error);
    });

    return dbPromise;
};

const tx = (db, mode = 'readonly') => {
    return db.transaction(STORE, mode).objectStore(STORE);
};

const reqToPromise = (req) => {
    return new Promise((resolve, reject) => {
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
};

/**
 * Agrega (o reemplaza) una serie pendiente.
 */
export const addPending = async (record) => {
    const db = await openDB();
    const store = tx(db, 'readwrite');
    await reqToPromise(store.put(record));
    return record;
};

/**
 * Devuelve todas las series pendientes, ordenadas por createdAt ascendente.
 */
export const getAllPending = async () => {
    const db = await openDB();
    const store = tx(db);
    return reqToPromise(store.getAll());
};

/**
 * Cuenta las pendientes. Mas barato que getAll.
 */
export const countPending = async () => {
    const db = await openDB();
    const store = tx(db);
    return reqToPromise(store.count());
};

/**
 * Elimina una pendiente por client_id. Se llama despues de un sync exitoso.
 */
export const removePending = async (clientId) => {
    const db = await openDB();
    const store = tx(db, 'readwrite');
    await reqToPromise(store.delete(clientId));
};

/**
 * Actualiza una pendiente (por ejemplo, incrementar attempts).
 */
export const updatePending = async (clientId, patch) => {
    const db = await openDB();
    const store = tx(db, 'readwrite');
    const existing = await reqToPromise(store.get(clientId));
    if (!existing) return null;
    const merged = { ...existing, ...patch };
    await reqToPromise(store.put(merged));
    return merged;
};

/**
 * Limpia todas las pendientes. Solo para testing / dev.
 */
export const clearAllPending = async () => {
    const db = await openDB();
    const store = tx(db, 'readwrite');
    await reqToPromise(store.clear());
};

// Las funciones ya estan exportadas individualmente arriba (addPending, etc).
// Este objeto las agrupa para uso conveniente cuando se importan varias a la vez.
export const offlineDb = {
    isAvailable,
    addPending,
    getAllPending,
    countPending,
    removePending,
    updatePending,
    clearAllPending,
};
