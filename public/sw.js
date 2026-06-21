/* eslint-disable no-restricted-globals */
/**
 * Service Worker — GymApp
 *
 * Responsabilidades:
 *   - Push notifications (Web Push API)
 *   - Background sync opcional (futuro)
 *
 * Activación: skipWaiting + clientsClaim para que el SW tome control
 * de las pestañas abiertas sin recargar manualmente.
 */

const VERSION = 'v1';
const STATIC_CACHE = `gymapp-static-${VERSION}`;
const RUNTIME_CACHE = `gymapp-runtime-${VERSION}`;

const PRECACHE_URLS = [
    '/',
    '/offline',
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
                    .filter((k) => k !== STATIC_CACHE && k !== RUNTIME_CACHE)
                    .map((k) => caches.delete(k)),
            );
            await self.clients.claim();
        })(),
    );
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
        icon: payload.icon || '/favicon.ico',
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
            // si ya hay una pestaña abierta, enfocar y navegar
            for (const client of allClients) {
                if ('focus' in client) {
                    await client.focus();
                    if ('navigate' in client) {
                        try { await client.navigate(url); } catch { /* ignore cross-origin */ }
                    }
                    return;
                }
            }
            // si no hay pestaña, abrir una nueva
            if (self.clients.openWindow) {
                await self.clients.openWindow(url);
            }
        })(),
    );
});

// === Push subscription change (renovar endpoint si expira) ===
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        (async () => {
            if (!self.registration.pushManager) return;
            try {
                const newSub = await self.registration.pushManager.subscribe(event.oldSubscription?.options);
                // notificar al backend
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

// === Mensaje desde la app (skipWaiting forzado, etc.) ===
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});
