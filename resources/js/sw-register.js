/**
 * Registro del Service Worker + manejo de suscripción push.
 *
 * Se llama una vez desde bootstrap.js.
 * Es seguro de importar en navegadores sin soporte: detecta capability
 * y degrada silenciosamente.
 */
import axios from 'axios';

let registration = null;

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const out = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i++) out[i] = rawData.charCodeAt(i);
    return out;
};

export async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return null;
    if (location.protocol === 'file:') return null; // no aplica en dev local file://

    try {
        registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none',
        });
        // listen for updates
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            if (!newWorker) return;
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // nueva versión instalada: pedir reload
                    console.info('[sw] nueva versión instalada, lista para activarse');
                }
            });
        });
        return registration;
    } catch (e) {
        console.warn('[sw] registro falló:', e);
        return null;
    }
}

export async function subscribeToPush(vapidPublicKey) {
    if (!registration) await registerServiceWorker();
    if (!registration || !('pushManager' in registration)) {
        return { ok: false, reason: 'unsupported' };
    }

    // ya suscrito?
    let sub = await registration.pushManager.getSubscription();
    if (!sub) {
        if (!vapidPublicKey) return { ok: false, reason: 'no-vapid-key' };
        try {
            sub = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
        } catch (e) {
            console.warn('[push] subscribe falló:', e);
            return { ok: false, reason: 'denied-or-error', error: e };
        }
    }

    try {
        await axios.post('/api/push/subscription', sub.toJSON(), { withCredentials: true });
        return { ok: true, subscription: sub };
    } catch (e) {
        return { ok: false, reason: 'backend-error', error: e };
    }
}

export async function unsubscribeFromPush() {
    if (!registration) return false;
    const sub = await registration.pushManager.getSubscription();
    if (!sub) return true;
    try {
        await axios.delete('/api/push/subscription', {
            data: { endpoint: sub.endpoint },
            withCredentials: true,
        });
    } catch { /* ignore */ }
    return sub.unsubscribe();
}

export function getRegistration() {
    return registration;
}
