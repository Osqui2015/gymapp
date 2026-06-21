/**
 * Composable de comentarios de trainer en tiempo real.
 *
 * Usa Laravel Echo (Pusher/Soketi) si está configurado.
 * Si no hay driver de broadcasting configurado, degrada a polling cada 30s
 * sobre /api/trainer-comments para mantener la misma API de uso.
 *
 *   const { comments, unreadCount, markRead } = useRealtimeComments();
 *
 *   // Para trainers: cargar comentarios de un alumno específico:
 *   const { comments, send } = useRealtimeComments({ alumnoId: 42 });
 */

import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useToast } from './useToast';

let echoInstance = null;
let echoLoading = null;

async function getEcho() {
    if (echoInstance !== null) return echoInstance;
    if (echoLoading) return echoLoading;

    echoLoading = (async () => {
        try {
            const cfg = window.__broadcasting;
            if (!cfg || cfg.driver === 'null' || cfg.driver === null) return null;
            const { default: Echo } = await import('laravel-echo');
            const pusherModule = await import('pusher-js');
            window.Pusher = pusherModule.default;
            echoInstance = new Echo({
                broadcaster: 'pusher',
                key: cfg.key,
                cluster: cfg.cluster,
                forceTLS: true,
                authEndpoint: '/broadcasting/auth',
                withCredentials: true,
            });
            return echoInstance;
        } catch (e) {
            console.warn('[realtime] Echo no disponible:', e);
            return null;
        }
    })();
    return echoLoading;
}

export function useRealtimeComments(options = {}) {
    const { alumnoId = null, pollIntervalMs = 30000 } = options;
    const toast = useToast();

    const comments = ref([]);
    const loading = ref(false);
    const pollingTimer = ref(null);
    const channelUnsubscribers = [];

    const unreadCount = computed(
        () => comments.value.filter((c) => !c.read_at).length,
    );

    const load = async () => {
        loading.value = true;
        try {
            const url = alumnoId
                ? `/api/trainer-comments?alumno_id=${alumnoId}`
                : `/api/trainer-comments`;
            const { data } = await axios.get(url);
            comments.value = data.data || [];
        } catch (e) {
            console.warn('[realtime] load comments falló:', e);
        } finally {
            loading.value = false;
        }
    };

    const send = async ({ body, historialId = null }) => {
        if (!alumnoId) throw new Error('alumnoId requerido para enviar');
        const { data } = await axios.post('/api/trainer-comments', {
            alumno_id: alumnoId,
            historial_id: historialId,
            body,
        });
        // No hace falta push local: el backend disparará el broadcast
        return data.data;
    };

    const markRead = async (comment) => {
        if (comment.read_at) return;
        try {
            await axios.post(`/api/trainer-comments/${comment.id}/read`);
            comment.read_at = new Date().toISOString();
        } catch (e) {
            toast.apiError(e, 'No se pudo marcar como leído.');
        }
    };

    const startPolling = () => {
        if (pollingTimer.value) return;
        pollingTimer.value = setInterval(load, pollIntervalMs);
    };

    const stopPolling = () => {
        if (pollingTimer.value) {
            clearInterval(pollingTimer.value);
            pollingTimer.value = null;
        }
    };

    const subscribeBroadcast = async () => {
        const Echo = await getEcho();
        if (!Echo) return false;

        try {
            const myUserId = window.__user?.id;
            const channelId = alumnoId ?? myUserId;
            if (!channelId) return false;

            const channelName = `trainer-comments.${channelId}`;
            const channel = Echo.private(channelName);

            channel.listen('.comment.sent', (e) => {
                comments.value = [
                    {
                        id: e.id,
                        trainer: e.trainer,
                        body: e.body,
                        historial_id: e.historial_id,
                        created_at: e.created_at,
                        read_at: null,
                    },
                    ...comments.value,
                ];
                toast.info(`💬 ${e.trainer?.name || 'Tu trainer'} te dejó un comentario`);
            });

            channelUnsubscribers.push(() => {
                try { Echo.leave(channelName); } catch { /* ignore */ }
            });
            return true;
        } catch (e) {
            console.warn('[realtime] subscribe falló:', e);
            return false;
        }
    };

    onMounted(async () => {
        await load();
        const ok = await subscribeBroadcast();
        if (!ok) startPolling();
    });

    onBeforeUnmount(() => {
        stopPolling();
        channelUnsubscribers.forEach((fn) => fn());
    });

    return { comments, unreadCount, loading, load, send, markRead };
}
