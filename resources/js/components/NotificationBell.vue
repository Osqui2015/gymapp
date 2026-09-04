<!--
  NotificationBell — campana de notificaciones in-app persistentes + push.

  - Carga notificaciones del backend vía useNotificationStore.
  - Muestra contador de no leídas + dropdown con lista.
  - Polling cada 60s para refrescar.
  - Suscribe al usuario a Web Push (botón Activar/Desactivar).

  Posición: 'header' | 'floating' (default 'header')
-->
<template>
    <div class="relative" ref="rootEl">
        <button
            type="button"
            @click="toggle"
            :class="[
                'relative inline-flex items-center justify-center rounded-full transition-colors',
                position === 'floating'
                    ? 'w-12 h-12 bg-white dark:bg-gray-800 shadow-lg hover:bg-gray-50 dark:hover:bg-gray-700'
                    : 'w-10 h-10 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300',
            ]"
            :aria-label="`Notificaciones (${unreadCount} no leídas)`"
            :aria-expanded="open"
            aria-haspopup="true"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v1m6 0H9" />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <Transition name="dropdown">
            <div
                v-if="open"
                class="absolute right-0 mt-2 w-96 max-h-[520px] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-40"
            >
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white">Notificaciones</h3>
                    <div class="flex items-center gap-3 text-xs">
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllRead"
                            class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            Marcar todas leídas
                        </button>
                        <button
                            v-if="pushSupported && !pushEnabled"
                            @click="enablePush"
                            class="text-gray-500 dark:text-gray-400 hover:underline"
                        >
                            🔔 Activar push
                        </button>
                        <button
                            v-else-if="pushEnabled"
                            @click="disablePush"
                            class="text-gray-500 dark:text-gray-400 hover:underline"
                        >
                            🔕 Push
                        </button>
                    </div>
                </div>

                <!-- Lista -->
                <div v-if="loading && items.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto mb-2"></div>
                    Cargando…
                </div>

                <div v-else-if="items.length === 0" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-4xl mb-2">🔔</div>
                    <p class="font-medium mb-1">Sin notificaciones</p>
                    <p class="text-xs">Te avisaremos cuando pase algo.</p>
                </div>

                <ul v-else class="max-h-[420px] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="n in items"
                        :key="n.id"
                        :class="[
                            'px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group',
                            !n.read_at ? 'bg-indigo-50/40 dark:bg-indigo-900/10' : '',
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                :class="[
                                    'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0',
                                    iconBgFor(n.type)
                                ]"
                            >
                                {{ iconFor(n.type) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ n.data.title }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mt-0.5">
                                    {{ n.data.body }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(n.created_at) }}</p>
                            </div>
                            <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    v-if="!n.read_at"
                                    @click="notifStore.markRead(n.id)"
                                    class="p-1 rounded text-gray-400 hover:text-indigo-500"
                                    title="Marcar como leída"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="notifStore.remove(n.id)"
                                    class="p-1 rounded text-gray-400 hover:text-red-500"
                                    title="Eliminar"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <span
                                v-if="!n.read_at"
                                class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0 mt-2"
                                aria-label="No leída"
                            ></span>
                        </div>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

<script setup>
const { formatDate } = useFormatters();
    import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '../stores/notification';
import { useToast } from '../composables/useToast';
import { useFormatters } from '@/composables/useFormatters';
import {
    registerServiceWorker,
    subscribeToPush,
    unsubscribeFromPush,
} from '../services/webPushService';

const props = defineProps({
    position: { type: String, default: 'header' }, // 'header' | 'floating'
});

const open = ref(false);
const rootEl = ref(null);
const pushEnabled = ref(false);
const pushSupported = ref(false);

const notifStore = useNotificationStore();
const { items, unreadCount, isLoading: loading } = storeToRefs(notifStore);

const toast = useToast();
let pollTimer = null;

const POLL_INTERVAL_MS = 60_000; // 60 segundos

const toggle = () => { open.value = !open.value; };

const close = (e) => {
    if (!rootEl.value) return;
    if (!rootEl.value.contains(e.target)) open.value = false;
};

const formatTimeAgo = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'hace instantes';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `hace ${Math.floor(diff / 86400)} d`;
    return formatDate(d);
};

const markAllRead = async () => {
    const updated = await notifStore.markAllRead();
    if (updated > 0) toast.success(`${updated} notificaciones marcadas como leídas`);
};

const iconFor = (type) => {
    switch (type) {
        case 'trainer_comment': return '💬';
        case 'membership_expiring': return '⚠️';
        case 'milestone': return '🏆';
        case 'rutina_asignada': return '📋';
        default: return '🔔';
    }
};

const iconBgFor = (type) => {
    switch (type) {
        case 'trainer_comment': return 'bg-gradient-to-br from-indigo-500 to-purple-600';
        case 'membership_expiring': return 'bg-gradient-to-br from-amber-500 to-red-500';
        case 'milestone': return 'bg-gradient-to-br from-yellow-400 to-orange-500';
        case 'rutina_asignada': return 'bg-gradient-to-br from-green-500 to-emerald-600';
        default: return 'bg-gradient-to-br from-gray-500 to-gray-600';
    }
};

const enablePush = async () => {
    const reg = await registerServiceWorker();
    if (!reg) return;
    try {
        const axios = (await import('axios')).default;
        const { data } = await axios.get('/api/push/vapid-public-key');
        if (!data.vapid_public_key) {
            toast.warning('El servidor no tiene VAPID configurado.');
            return;
        }
        const result = await subscribeToPush(data.vapid_public_key);
        if (result.ok) {
            pushEnabled.value = true;
            toast.success('Notificaciones push activadas.');
        } else if (result.reason === 'denied-or-error') {
            toast.warning('Permiso de notificaciones denegado.');
        }
    } catch (e) {
        toast.apiError(e, 'No se pudo activar push.');
    }
};

const disablePush = async () => {
    const ok = await unsubscribeFromPush();
    if (ok) {
        pushEnabled.value = false;
        toast.info('Push desactivado.');
    }
};

const checkInitialPushState = async () => {
    const reg = await navigator.serviceWorker?.getRegistration?.();
    if (!reg) { pushSupported.value = false; return; }
    pushSupported.value = !!(reg.pushManager);
    const sub = await reg.pushManager.getSubscription();
    pushEnabled.value = !!sub;
};

onMounted(async () => {
    document.addEventListener('click', close);
    await Promise.all([notifStore.fetch(), checkInitialPushState()]);
    // Polling
    pollTimer = setInterval(() => notifStore.fetch(), POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', close);
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
