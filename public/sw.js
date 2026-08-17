/* eslint-disable no-restricted-globals */
/**
 * Service Worker — GymApp
 *
 * Responsabilidades:
 *   - Push notifications (Web Push API)
 *   - Offline support: cache de assets estáticos + página /offline
 *   - Background sync opcional (futuro)
 *
 * Estrategias de caching:
 *   - GET /assets/*, /build/*, /icons/*, /favicon.ico: CacheFirst (1 año)
 *   - GET /api/*: NetworkFirst con fallback a cache (5 min)
 *   - Navegación (HTML): NetworkFirst con fallback a /offline
 *   - POST/PUT/DELETE: siempre van a la red, sin cache
 */

const VERSION = 'v2';
const STATIC_CACHE = `gymapp-static-${VERSION}`;
const RUNTIME_CACHE = `gymapp-runtime-${VERSION}`;
const API_CACHE = `gymapp-api-${VERSION}`;

const PRECACHE_URLS = [
    '/',
    '/offline',
    '/favicon.ico',
];

// === Lifecycle ===
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            cache.addAll(PRECACHE_URLS).catch(() => {
                /* si las rutas no existen en este deploy, no rompemos install */
            }),
        ),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((k) => ![STATIC_CACHE, RUNTIME_CACHE, API_CACHE].includes(k))
                    .map((k) => caches.delete(k)),
            );
            await self.clients.claim();
        })(),
    );
});

// === Helpers ===
const isStaticAsset = (url) => {
    return /\.(css|js|woff2?|ttf|eot|ico|png|jpg|jpeg|svg|webp|gif)$/i.test(url.pathname) ||
           url.pathname.startsWith('/build/') ||
           url.pathname.startsWith('/icons/');
};

const isApiRequest = (url) => url.pathname.startsWith('/api/');

const isNavigation = (request) =>
    request.mode === 'navigate' || (request.method === 'GET' && request.headers.get('accept')?.includes('text/html'));

// === Fetch handler ===
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo GET se cachea
    if (request.method !== 'GET') return;

    // Ignorar requests de otros orígenes
    if (url.origin !== self.location.origin) return;

    // Estrategia 1: Assets estáticos → CacheFirst
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
            }),
        );
        return;
    }

    // Estrategia 2: API → NetworkFirst (5 min de fallback a cache)
    if (isApiRequest(url)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(API_CACHE).then((cache) =>
                            cache.put(request, clone),
                        );
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => {
                    if (cached) return cached;
                    return new Response(
                        JSON.stringify({ error: 'offline', message: 'Sin conexión' }),
                        { status: 503, headers: { 'Content-Type': 'application/json' } },
                    );
                })),
        );
        return;
    }

    // Estrategia 3: Navegación HTML → NetworkFirst con fallback a /offline
    if (isNavigation(request)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match('/offline')),
                ),
        );
        return;
    }
});

// === Push ===
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'GymApp', body: event.data.text() };
    }

    const title = payload.title || 'GymApp';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: payload.badge || '/favicon.ico',
        image: payload.image,
        data: payload.data || {},
        tag: payload.tag || 'gymapp-default',
        renotify: payload.renotify !== false,
        requireInteraction: !!payload.requireInteraction,
        actions: payload.actions || [],
        vibrate: payload.vibrate || [100, 50, 100],
        timestamp: Date.now(),
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        (async () => {
            const allClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            for (const client of allClients) {
                if ('focus' in client) {
                    await client.focus();
                    if ('navigate' in client) {
                        try { await client.navigate(url); } catch { /* ignore cross-origin */ }
                    }
                    return;
                }
            }
            if (self.clients.openWindow) {
                await self.clients.openWindow(url);
            }
        })(),
    );
});

self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        (async () => {
            if (!self.registration.pushManager) return;
            try {
                const newSub = await self.registration.pushManager.subscribe(event.oldSubscription?.options);
                await fetch('/api/push/subscription', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(newSub.toJSON()),
                });
            } catch (e) {
                console.warn('[sw] pushsubscriptionchange failed:', e);
            }
        })(),
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});
