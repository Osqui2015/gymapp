import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// === Timeout global: 30s por request ===
// Antes: sin timeout (requests colgados quedaban para siempre).
// 30s es generoso para conexiones lentas + payloads grandes (PDF, CSV).
window.axios.defaults.timeout = 30_000;

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// === Retry config ===
// Solo se reintenta en:
//   - Errores de red (timeout, ECONNRESET, etc.)
//   - 5xx del servidor
// Y SOLO en GET (POST/PUT/DELETE pueden duplicar efectos: reenvío de
// formulario, doble cargo, etc.).
const RETRY_MAX = 2;             // 2 reintentos (3 intentos totales)
const RETRY_BASE_DELAY = 1_000;  // 1s base, se duplica cada intento
const RETRYABLE_METHODS = new Set(['get']);

/**
 * Espera `ms` milisegundos. Usado para el backoff entre reintentos.
 */
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Determina si un error es reintentable.
 *   - Sin `response` → error de red (timeout, ECONNRESET, etc.) → sí
 *   - response.status >= 500 → error del servidor → sí
 *   - resto → no
 */
function isRetryable(error) {
    if (!error.config) return false; // abort / setup error
    if (!RETRYABLE_METHODS.has(error.config.method?.toLowerCase())) return false;
    if (!error.response) return true;          // error de red puro
    return error.response.status >= 500;       // 5xx
}

/**
 * Lee la cantidad de reintentos ya hechos (almacenado en config por axios).
 * Si no existe, arranca en 0.
 */
function getRetryCount(config) {
    return config.__retryCount || 0;
}

function incrementRetryCount(config) {
    config.__retryCount = getRetryCount(config) + 1;
}

// === Interceptor de respuestas: maneja errores globales + retry ===
window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        // 1) Si el error es reintentable y todavía no superamos el máximo, retry
        if (isRetryable(error) && getRetryCount(error.config || {}) < RETRY_MAX) {
            incrementRetryCount(error.config);
            const delay = RETRY_BASE_DELAY * 2 ** (error.config.__retryCount - 1);
            console.warn(
                `[axios] reintento ${error.config.__retryCount}/${RETRY_MAX} en ${delay}ms ` +
                `(${error.config.method?.toUpperCase()} ${error.config.url})`,
            );
            await sleep(delay);
            return window.axios.request(error.config);
        }

        // 2) Manejo centralizado de errores de auth / validación / server
        const status = error.response?.status;
        const url = error.config?.url || '';
        const isAuthEndpoint = url.includes('/login') || url.includes('/register') || url.includes('/logout');

        // 401: no autenticado (excepto si ya estamos en login/register)
        if (status === 401 && !isAuthEndpoint) {
            window.dispatchEvent(new CustomEvent('auth:expired', { detail: { url } }));
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }

        // 403: sin permisos - emitir evento para que la UI muestre un toast
        if (status === 403) {
            window.dispatchEvent(new CustomEvent('auth:forbidden', {
                detail: { message: error.response?.data?.message || 'No tenés permisos para esta acción' }
            }));
        }

        // 419: CSRF token mismatch - recargar para regenerar
        if (status === 419) {
            window.dispatchEvent(new CustomEvent('auth:csrf-mismatch'));
            window.location.reload();
        }

        // 422: errores de validación - emitir evento con los errors
        if (status === 422) {
            window.dispatchEvent(new CustomEvent('validation:error', {
                detail: error.response?.data?.errors || {}
            }));
        }

        // 500+: error del servidor - loguear y notificar
        if (status >= 500) {
            console.error('[API Error]', error);
            window.dispatchEvent(new CustomEvent('server:error', {
                detail: { message: 'Error del servidor. Por favor intenta de nuevo.' }
            }));
        }

        return Promise.reject(error);
    }
);

// === Interceptor de requests: previene dobles submits ===
let inflightRequests = new Map();
window.axios.interceptors.request.use((config) => {
    const key = `${config.method}:${config.url}`;
    if (inflightRequests.has(key)) {
        config.signal = inflightRequests.get(key).signal;
    } else {
        const controller = new AbortController();
        inflightRequests.set(key, controller);
        config.signal = controller.signal;
        const cleanup = () => inflightRequests.delete(key);
        config.signal.addEventListener('abort', cleanup);
    }
    return config;
});

// === Configuración pública de broadcasting (realtime) ===
// Inyectada desde la Blade view; permite que useRealtimeComments sepa
// si debe usar Echo o degradarse a polling.
window.__broadcasting = window.__broadcasting || { driver: 'null', key: null, cluster: null };

// === Service Worker (Mejora 8.7) ===
// Se registra una vez, sin bloquear. Si el navegador no soporta, no pasa nada.
if (typeof window !== 'undefined' && 'serviceWorker' in navigator && window.location.protocol !== 'file:') {
    window.addEventListener('load', () => {
        import('./services/webPushService').then(({ registerServiceWorker }) => {
            registerServiceWorker().catch(() => { /* ignore */ });
        });
    });
}

export default window.axios;
