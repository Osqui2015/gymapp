<!--
  NotificationBell — campana de notificaciones con push + comentarios realtime.

  - Suscribe al usuario a Web Push (si lo permite el navegador).
  - Escucha comentarios realtime del trainer vía useRealtimeComments.
  - Muestra contador de no leídos + dropdown con lista.

  Props:
    - position: 'header' | 'floating' (default 'header')
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
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
                class="absolute right-0 mt-2 w-80 max-h-[480px] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-40"
            >
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white">Notificaciones</h3>
                    <button
                        v-if="pushSupported && !pushEnabled"
                        type="button"
                        @click="enablePush"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Activar push
                    </button>
                    <button
                        v-else-if="pushEnabled"
                        type="button"
                        @click="disablePush"
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:underline"
                    >
                        Desactivar push
                    </button>
                </div>

                <div v-if="comments.length === 0" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-3xl mb-2">🔔</div>
                    Sin notificaciones nuevas.
                </div>

                <ul v-else class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="c in comments.slice(0, 10)"
                        :key="c.id"
                        :class="[
                            'px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors',
                            !c.read_at ? 'bg-indigo-50/40 dark:bg-indigo-900/10' : '',
                        ]"
                        @click="markRead(c)"
                    >
                        <div class="flex items-start gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                {{ (c.trainer?.name || 'T')[0].toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-900 dark:text-white">
                                    <strong>{{ c.trainer?.name || 'Tu trainer' }}</strong>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ c.body }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(c.created_at) }}</p>
                            </div>
                            <span
                                v-if="!c.read_at"
                                class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0 mt-2"
                                aria-label="No leído"
                            ></span>
                        </div>
                    </li>
                </ul>

                <div v-if="comments.length > 10" class="px-4 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
                    y {{ comments.length - 10 }} más…
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useRealtimeComments } from '../composables/useRealtimeComments';
import { useToast } from '../composables/useToast';
import {
    registerServiceWorker,
    subscribeToPush,
    unsubscribeFromPush,
} from '../sw-register';

const props = defineProps({
    position: { type: String, default: 'header' }, // 'header' | 'floating'
});

const open = ref(false);
const rootEl = ref(null);
const pushEnabled = ref(false);
const pushSupported = ref(false);
const toast = useToast();

const { comments, unreadCount, markRead } = useRealtimeComments();

const toggle = () => { open.value = !open.value; };
const close = (e) => {
    if (!rootEl.value) return;
    if (!rootEl.value.contains(e.target)) open.value = false;
};

const formatDate = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'hace instantes';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} h`;
    return d.toLocaleDateString();
};

const enablePush = async () => {
    const reg = await registerServiceWorker();
    if (!reg) return;
    try {
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
        toast.info('Notificaciones push desactivadas.');
    }
};

const checkInitialPushState = async () => {
    const reg = await navigator.serviceWorker?.getRegistration?.();
    if (!reg) {
        pushSupported.value = false;
        return;
    }
    pushSupported.value = !!(reg.pushManager);
    const sub = await reg.pushManager.getSubscription();
    pushEnabled.value = !!sub;
};

onMounted(async () => {
    document.addEventListener('click', close);
    await checkInitialPushState();
});

onBeforeUnmount(() => {
    document.removeEventListener('click', close);
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
