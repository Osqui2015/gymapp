/**
 * useWebPush — composable para manejar suscripciones Web Push.
 *
 * Wrapper reactivo sobre `services/webPushService.js` (que tiene las
 * funciones puras). Permite que cualquier componente de UI consulte
 * "¿está activo?", "activá" o "desactivá" sin tener que conocer el
 * service worker.
 *
 *   const { pushSupported, pushEnabled, permission, enable, disable, refresh } = useWebPush();
 *
 * Devuelve refs reactivas (se usan directo en templates:
 * `:disabled="!pushSupported"`). Las funciones `enable` / `disable`
 * resuelven con `{ ok, reason?, error? }` para que el caller pueda
 * dar feedback específico (toasts, analytics, etc.).
 */
import { ref, onMounted } from 'vue';
import axios from 'axios';
import {
    registerServiceWorker,
    subscribeToPush,
    unsubscribeFromPush,
} from '../services/webPushService';

const pushSupported = ref(false);
const pushEnabled = ref(false);
const permission = ref(typeof Notification !== 'undefined' ? Notification.permission : 'default');
const initializing = ref(false);

async function refresh() {
    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            pushSupported.value = false;
            return;
        }
        const reg = await navigator.serviceWorker.getRegistration();
        pushSupported.value = !!(reg && reg.pushManager);
        if (reg?.pushManager) {
            const sub = await reg.pushManager.getSubscription();
            pushEnabled.value = !!sub;
        }
        if (typeof Notification !== 'undefined') {
            permission.value = Notification.permission;
        }
    } catch (e) {
        console.warn('[useWebPush] refresh falló:', e);
    }
}

async function enable() {
    if (initializing.value) return { ok: false, reason: 'busy' };
    initializing.value = true;
    try {
        const reg = await registerServiceWorker();
        if (!reg) return { ok: false, reason: 'unsupported' };

        const { data } = await axios.get('/api/push/vapid-public-key');
        if (!data?.vapid_public_key) {
            return { ok: false, reason: 'no-vapid-key' };
        }

        const result = await subscribeToPush(data.vapid_public_key);
        if (result.ok) {
            pushEnabled.value = true;
            permission.value = 'granted';
        }
        return result;
    } finally {
        initializing.value = false;
    }
}

async function disable() {
    const ok = await unsubscribeFromPush();
    if (ok) pushEnabled.value = false;
    return { ok };
}

export function useWebPush({ autoInit = true } = {}) {
    if (autoInit) {
        onMounted(refresh);
    }
    return {
        pushSupported,
        pushEnabled,
        permission,
        initializing,
        refresh,
        enable,
        disable,
    };
}
