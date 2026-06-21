import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// === Interceptor de respuestas: maneja errores globales de forma centralizada ===
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url || '';
        const isAuthEndpoint = url.includes('/login') || url.includes('/register') || url.includes('/logout');

        // 401: no autenticado (excepto si ya estamos en login/register)
        if (status === 401 && !isAuthEndpoint) {
            // Token expirado o sesión perdida
            window.dispatchEvent(new CustomEvent('auth:expired', { detail: { url } }));
            // Solo redirigir si no estamos ya en login
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
        // Limpiar cuando termina
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
    // Lazy import para no penalizar el initial bundle.
    window.addEventListener('load', () => {
        import('./sw-register').then(({ registerServiceWorker }) => {
            registerServiceWorker().catch(() => { /* ignore */ });
        });
    });
}

export default window.axios;
