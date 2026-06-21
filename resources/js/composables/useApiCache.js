/**
 * Cache LRU simple en memoria para llamadas HTTP.
 * Almacena las últimas `maxSize` respuestas indexadas por URL.
 * Las entradas expiran después de `ttlMs` milisegundos.
 *
 * Uso:
 *   import { cachedAxiosGet } from '../composables/useApiCache';
 *   const data = await cachedAxiosGet('/api/ejercicios/grupos-musculares');
 *
 *   // Limpiar cache manualmente:
 *   clearApiCache();
 *
 *   // Limpiar solo una URL:
 *   invalidateApiCache('/api/rutinas');
 */

const DEFAULT_TTL = 60_000; // 1 minuto
const DEFAULT_MAX_SIZE = 50;

const store = new Map(); // url -> { data, expiresAt }
const stats = { hits: 0, misses: 0 };

const isExpired = (entry) => !entry || Date.now() > entry.expiresAt;

/**
 * Obtiene una entrada del cache si existe y no está expirada.
 */
export function getCached(url) {
    const entry = store.get(url);
    if (isExpired(entry)) {
        store.delete(url);
        return null;
    }
    // Mover al final (LRU: más reciente)
    store.delete(url);
    store.set(url, entry);
    stats.hits++;
    return entry.data;
}

/**
 * Guarda una entrada en el cache, evictando la menos usada si se pasa del maxSize.
 */
export function setCached(url, data, ttl = DEFAULT_TTL) {
    // Si ya existe, eliminar para re-insertar al final
    if (store.has(url)) store.delete(url);

    store.set(url, { data, expiresAt: Date.now() + ttl });

    // Evict la entrada más vieja si excedemos el tamaño
    if (store.size > DEFAULT_MAX_SIZE) {
        const oldest = store.keys().next().value;
        store.delete(oldest);
    }
}

/**
 * Invalida todas las entradas que coincidan con un patrón (substring o RegExp).
 */
export function invalidateApiCache(pattern) {
    if (!pattern) {
        clearApiCache();
        return;
    }
    const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern);
    for (const key of store.keys()) {
        if (regex.test(key)) store.delete(key);
    }
}

/**
 * Limpia todo el cache.
 */
export function clearApiCache() {
    store.clear();
}

/**
 * Devuelve estadísticas del cache.
 */
export function getCacheStats() {
    return { ...stats, size: store.size, maxSize: DEFAULT_MAX_SIZE };
}

/**
 * Wrapper sobre axios.get que cachea automáticamente.
 * Omite el cache si la URL contiene `?_t=` (timestamp) o `cache=false`.
 */
export async function cachedAxiosGet(url, config = {}, options = {}) {
    const { ttl = DEFAULT_TTL, skipCache = url.includes('cache=false') || url.includes('_t=') } = options;

    if (!skipCache) {
        const cached = getCached(url);
        if (cached !== null) return { data: cached, __cached: true };
    }

    stats.misses++;
    const axios = (await import('axios')).default;
    const response = await axios.get(url, config);
    setCached(url, response.data, ttl);
    return response;
}