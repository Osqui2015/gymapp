/**
 * Web Push service — funciones puras sin reactividad de Vue.
 *
 * Separado de `composables/useWebPush.js` para que se pueda usar tanto
 * desde el composable (con state reactivo) como desde `bootstrap.js`
 * (registro del SW al cargar la app, sin componente que lo monte).
 *
 * API:
 *   registerServiceWorker()          → ServiceWorkerRegistration | null
 *   subscribeToPush(vapidPublicKey)  → { ok, subscription?, reason? }
 *   unsubscribeFromPush()           → boolean
 *   urlBase64ToUint8Array(b64)      → Uint8Array (helper)
 *
 * Si en algún momento querés un fallback distinto (polling, SSE, etc.),
 * la firma se mantiene y solo cambiás la implementación.
 *
 * === CHECKLIST DE PRODUCCIÓN (Web Push) ===
 * Los Service Workers y la Web Push API SOLO funcionan en contextos seguros:
 *
 *   [✓] HTTPS configurado en el dominio de producción
 *       (Service Workers requieren "Secure Context" — HTTPS o localhost).
 *       Verificar con: `curl -vI https://tu-dominio/ 2>&1 | grep -i "SSL\|TLS"`
 *
 *   [✓] Certificado SSL válido (no auto-firmado).
 *       Let's Encrypt es gratis y soportado por Hostinger.
 *
 *   [✓] VAPID keys generadas y guardadas en .env
 *       `php artisan webpush:vapid` (en Windows usar scripts\webpush-vapid.ps1).
 *       Verificar: `grep VAPID .env` debe mostrar SUBJECT/PUBLIC/PRIVATE.
 *
 *   [✓] /sw.js accesible públicamente
 *       Verificar: `curl -I https://tu-dominio/sw.js` debe devolver 200.
 *
 *   [✓] /api/push/vapid-public-key accesible
 *       Verificar: `curl -I https://tu-dominio/api/push/vapid-public-key`.
 *
 *   [✓] Browser console limpia
 *       Abrir DevTools > Console > buscar errores de SW o Push.
 *
 * Si alguno falla, el subscribe() va a tirar "denied-or-error" o el SW
 * no se va a registrar — el `useWebPush` composable maneja esos casos
 * con toasts user-friendly.
 */
import axios from 'axios';

let registration = null;

// El listener de 'controllerchange' se registra UNA sola vez a nivel de módulo.
// registerServiceWorker() se llama desde varios lados (useWebPush,
// NotificationBell) y antes cada llamada agregaba su propio listener con un
// flag 'refreshing' local: un solo cambio de controller disparaba varias
// recargas y la primeraCancellationaba a las otras.
let controllerChangeHandlerInstalled = false;
let refreshing = false;

const installControllerChangeHandler = () => {
    if (controllerChangeHandlerInstalled) return;
    if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return;
    controllerChangeHandlerInstalled = true;

    // Cuando el nuevo SW toma el control, recargar la pagina para que use el
    // nuevo. Solo pasa cuando el usuario acepta actualizar (el SW ya no hace
    // skipWaiting() solo al instalarse).
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        window.location.reload();
    });
};

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const out = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i++) out[i] = rawData.charCodeAt(i);
    return out;
};

export async function registerServiceWorker() {
    if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return null;
    if (typeof location !== 'undefined' && location.protocol === 'file:') return null;

    try {
        registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
            updateViaCache: 'none',
        });
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            if (!newWorker) return;
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    console.info('[sw] nueva versión instalada, lista para activarse');
                    // Emitir evento para que la UI muestre un banner de "actualizar"
                    window.dispatchEvent(new CustomEvent('sw:update-available'));
                }
            });
        });

        // Cuando el nuevo SW toma el control, recargar la pagina para que use el nuevo.
        // Esto pasa despues de un skipWaiting automatico o un reload del user.
        installControllerChangeHandler();

        return registration;
    } catch (e) {
        console.warn('[sw] registro falló:', e);
        return null;
    }
}

/**
 * ¿La suscripción del navegador se creó con ESTA applicationServerKey?
 *
 * Importante tras rotar las claves VAPID: la suscripción vieja sigue
 * existiendo en el navegador pero el push service la rechaza con 404/410,
 * así que hay que compararla y rehacerla.
 *
 * @param {PushSubscription} sub
 * @param {string} vapidPublicKey Base64url de la clave pública del servidor.
 * @returns {boolean} true si coincide (o si no se puede comparar).
 */
export function suscriptcionUsaKey(sub, vapidPublicKey) {
    if (!sub || !vapidPublicKey) return true;
    try {
        const actual = sub.options?.applicationServerKey;
        if (!actual) return true; // no exponen la key: no se toca nada
        const nueva = urlBase64ToUint8Array(vapidPublicKey);
        if (actual.length !== nueva.length) return false;
        return Array.from(actual).every((byte, i) => byte === nueva[i]);
    } catch {
        return true;
    }
}

export async function subscribeToPush(vapidPublicKey) {
    if (!registration) await registerServiceWorker();
    if (!registration || !('pushManager' in registration)) {
        return { ok: false, reason: 'unsupported' };
    }

    let sub = await registration.pushManager.getSubscription();

    // Si la suscripción se creó con otra applicationServerKey (rotación de
    // claves VAPID) hay que deshacerla: con la key vieja el push service
    // rechaza todos los envíos (404/410) y el usuario se queda sin avisos
    // aunque la UI diga que tiene notificaciones activas.
    if (sub && !suscriptcionUsaKey(sub, vapidPublicKey)) {
        try {
            await sub.unsubscribe();
        } catch {
            /* ignore: seguimos con una suscripción nueva */
        }
        sub = null;
    }

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
    } catch {
        /* ignore */
    }
    return sub.unsubscribe();
}

export function getRegistration() {
    return registration;
}
