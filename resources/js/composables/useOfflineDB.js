/**
 * useOfflineDB — Wrapper minimo sobre IndexedDB para guardar series pendientes.
 *
 * Schema:
 *   db: gymapp-offline-<userId> (v1)
 *   store: pending_series
 *     keyPath: client_id
 *     indices: por createdAt (auto-mantenido)
 *
 * ESTRUCTURA DEL RECORD:
 *   {
 *     client_id: 'uuid-generado-en-el-cliente',
 *     payload: { ... campos que van a /api/historial/guardar ... },
 *     createdAt: '2026-09-04T19:30:00.000Z',
 *     attempts: 0,           // cantidad de veces que intentamos sincronizar
 *     lastError: null,       // ultimo mensaje de error si fallo
 *   }
 *
 * SCOPE POR USUARIO (privacidad)
 * -------------------------------
 * Antes el nombre de la DB era fijo (`gymapp-offline`) y el store era un
 * singleton por DB. En un dispositivo compartido (gimnasio, recepcion) las
 * series pendientes del socio A se sincronizaban como del socio B al
 * loguearse B: las series de A se guardaban en el historial de B.
 *
 * Ahora la DB se nombra `gymapp-offline-<userId>`: cada usuario tiene su
 * propia base. Cuando no hay usuario identificado (antes de loguearse) se
 * usa un scope anónimo que NO se sincroniza.
 *
 * Si IndexedDB no esta disponible (modo privado, navegador raro, etc),
 * todas las funciones devuelven arrays vacios o rechazan con un error
 * controlado. El caller debe manejar el fallback.
 */

const DB_NAME_BASE = 'gymapp-offline';
const DB_VERSION = 1;
const STORE = 'pending_series';

/** Scope usado cuando no hay usuario identificado. */
const SCOPE_ANONYMOUS = 'anon';

/**
 * Obtiene el id del usuario actual desde el store de auth.
 *
 * Se lee de `window.__user` (inyectado en layouts/app.blade.php) para no
 * crear una dependencia circular entre composables y el store de Pinia.
 *
 * @returns {string} Scope del usuario.
 */
const resolveUserScope = () => {
    if (typeof window === 'undefined') return SCOPE_ANONYMOUS;

    const user = window.__user;
    if (!user || !user.id) return SCOPE_ANONYMOUS;

    return String(user.id);
};

/**
 * Nombre de la DB para un scope dado.
 *
 * @param {string} scope
 * @returns {string}
 */
const dbNameFor = (scope) => `${DB_NAME_BASE}-${scope}`;

/** Instancias abiertas, por scope. */
const dbPromises = new Map();

export const isAvailable = () => {
    return typeof indexedDB !== 'undefined';
};

/**
 * Abre (o reutiliza) la DB de un scope.
 *
 * @param {string} [scope]
 * @returns {Promise<IDBDatabase>}
 */
const openDB = (scope = resolveUserScope()) => {
    if (!isAvailable()) {
        return Promise.reject(new Error('IndexedDB no disponible'));
    }

    if (dbPromises.has(scope)) return dbPromises.get(scope);

    const promise = new Promise((resolve, reject) => {
        const req = indexedDB.open(dbNameFor(scope), DB_VERSION);
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

    dbPromises.set(scope, promise);

    return promise;
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

/**
 * Cierra y borra la DB del usuario actual.
 *
 * Se llama al LOGOUT: en un dispositivo compartido, las series pendientes
 * del socio que se va no deben quedar disponibles para el siguiente.
 *
 * @returns {Promise<boolean>} true si se borró.
 */
export const purgeCurrentScope = async () => {
    const scope = resolveUserScope();

    try {
        const db = await openDB(scope);
        db.close();
    } catch {
        /* la DB puede no existir: no es un error */
    }

    dbPromises.delete(scope);

    if (!isAvailable()) return false;

    return new Promise((resolve) => {
        const req = indexedDB.deleteDatabase(dbNameFor(scope));
        req.onsuccess = () => resolve(true);
        req.onerror = () => resolve(false);
        req.onblocked = () => resolve(false);
    });
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
    purgeCurrentScope,
};
