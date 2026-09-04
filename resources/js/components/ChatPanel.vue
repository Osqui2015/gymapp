<!--
  ChatPanel — UI completa de mensajería 1-a-1.

  Layout:
    ┌─────────────────────────────────────────────┐
    │ Header con título + botón cerrar             │
    ├──────────────┬──────────────────────────────┤
    │              │                              │
    │ Conversa-    │  Mensajes                    │
    │ ciones       │  (con scroll, autoreload)    │
    │ (lista)      │                              │
    │              │  [input + send button]       │
    │              │                              │
    └──────────────┴──────────────────────────────┘

  Props:
    - embedded: si true, no muestra el wrapper (para usar dentro de otra vista)

  Eventos:
    - close: emite cuando el user quiere cerrar el panel
-->
<template>
    <div :class="['flex flex-col bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden', !embedded && 'h-[600px]']">
        <!-- Header -->
        <div v-if="!embedded" class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                💬 Mensajes
                <span v-if="store.totalUnread > 0" class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white">
                    {{ store.totalUnread }}
                </span>
            </h3>
            <button
                v-if="onClose"
                @click="onClose"
                class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                aria-label="Cerrar"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Body: lista + chat -->
        <div class="flex-1 flex min-h-0">
            <!-- Lista de conversaciones -->
            <div :class="['border-r border-gray-200 dark:border-gray-700 flex flex-col', showChat ? 'hidden md:flex md:w-72' : 'flex w-full md:w-72']">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="refreshConversations"
                        :disabled="store.isLoading"
                        class="w-full text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold disabled:opacity-50"
                    >
                        {{ store.isLoading ? 'Cargando...' : '🔄 Actualizar' }}
                    </button>
                </div>
                <div v-if="store.isLoading && store.conversations.length === 0" class="p-4 text-center text-sm text-gray-500">
                    <div class="animate-spin w-5 h-5 mx-auto mb-2 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
                    Cargando...
                </div>
                <div v-else-if="store.conversations.length === 0" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div class="text-3xl mb-2">💬</div>
                    <p>Sin conversaciones.</p>
                    <p class="text-xs mt-1">Los mensajes que recibas aparecerán acá.</p>
                </div>
                <ul v-else class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="conv in store.conversations"
                        :key="conv.other_user.id"
                        @click="openChat(conv.other_user.id)"
                        :class="[
                            'px-3 py-3 cursor-pointer transition-colors flex items-start gap-3',
                            store.activeUserId === conv.other_user.id
                                ? 'bg-indigo-50 dark:bg-indigo-950/30'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/30',
                        ]"
                    >
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                            {{ (conv.other_user.name || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ conv.other_user.name }}</p>
                                <span v-if="conv.unread_count > 0" class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white flex-shrink-0">
                                    {{ conv.unread_count }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ lastMessagePreview(conv.last_message) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Chat activo -->
            <div :class="['flex-1 flex flex-col min-w-0', !showChat && 'hidden md:flex']">
                <div v-if="!store.activeUserId" class="flex-1 flex items-center justify-center p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <div>
                        <div class="text-5xl mb-3">💬</div>
                        <p class="font-medium">Seleccioná una conversación</p>
                        <p class="text-xs mt-1">O iniciá una desde el perfil de un alumno</p>
                    </div>
                </div>
                <template v-else>
                    <!-- Header del chat -->
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3 bg-white dark:bg-gray-800">
                        <button
                            @click="backToList"
                            class="md:hidden p-1 rounded text-gray-500 hover:text-gray-700 dark:hover:text-gray-200"
                            aria-label="Volver a la lista"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm">
                            {{ otherUserInitial }}
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ otherUserName }}</p>
                    </div>

                    <!-- Mensajes -->
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50 dark:bg-gray-900/30">
                        <div v-if="store.activeMessages.length === 0" class="text-center text-sm text-gray-500 py-12">
                            <div class="text-4xl mb-2">👋</div>
                            <p>¡Decile algo a {{ otherUserName }}!</p>
                        </div>
                        <div
                            v-for="msg in store.activeMessages"
                            :key="msg.id"
                            :class="['flex', isMine(msg) ? 'justify-end' : 'justify-start']"
                        >
                            <div
                                :class="[
                                    'max-w-[75%] px-3 py-2 rounded-2xl text-sm shadow-sm',
                                    isMine(msg)
                                        ? 'bg-indigo-600 text-white rounded-br-sm'
                                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-bl-sm',
                                ]"
                            >
                                <p class="whitespace-pre-line break-words">{{ msg.body }}</p>
                                <p :class="['text-[10px] mt-1', isMine(msg) ? 'text-indigo-100' : 'text-gray-400']">
                                    {{ formatTime(msg.created_at) }}
                                    <span v-if="isMine(msg) && msg.read_at"> · leído</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <form @submit.prevent="send" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-end gap-2">
                        <textarea
                            v-model="newMessage"
                            @keydown.enter.exact.prevent="send"
                            placeholder="Escribí un mensaje..."
                            rows="1"
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none max-h-24"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="!newMessage.trim() || store.isSending"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg font-semibold text-sm shadow-sm transition-colors"
                        >
                            <span v-if="store.isSending">...</span>
                            <span v-else>Enviar</span>
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useMessagesStore } from '../stores/messages';
import { useAuthStore } from '../stores/auth';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateShort } = useFormatters();

const props = defineProps({
    embedded: { type: Boolean, default: false },
    defaultUserId: { type: Number, default: null },
});
const emit = defineEmits(['close']);
const onClose = props.embedded ? null : () => emit('close');

const store = useMessagesStore();
const auth = useAuthStore();

const newMessage = ref('');
const showChat = ref(false);
const messagesContainer = ref(null);

let pollTimer = null;

const myId = computed(() => auth.id);
const otherUserName = computed(() => {
    const conv = store.conversations.find((c) => c.other_user.id === store.activeUserId);
    return conv?.other_user?.name || 'Conversación';
});
const otherUserInitial = computed(() => {
    return (otherUserName.value || '?').charAt(0).toUpperCase();
});

const isMine = (msg) => msg.sender_id === myId.value;

const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const today = new Date().toDateString() === d.toDateString();
    return today
        ? formatTime(d)
        : formatDateShort(d);
};

const lastMessagePreview = (msg) => {
    if (!msg) return 'Sin mensajes';
    const prefix = msg.sender_id === myId.value ? 'Vos: ' : '';
    return prefix + (msg.body?.length > 40 ? msg.body.slice(0, 40) + '…' : msg.body);
};

const openChat = async (userId) => {
    showChat.value = true;
    await store.openConversation(userId);
    await store.markConversationRead(userId);
    await scrollToBottom();
};

const backToList = () => {
    showChat.value = false;
    store.closeConversation();
};

const send = async () => {
    const body = newMessage.value;
    if (!body.trim()) return;
    try {
        await store.sendMessage(body);
        newMessage.value = '';
        await nextTick();
        await scrollToBottom();
    } catch (e) {
        // el store ya loguea
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const refreshConversations = () => store.fetchConversations();

onMounted(async () => {
    await store.fetchConversations();
    if (props.defaultUserId) {
        await openChat(props.defaultUserId);
    }
    // Polling cada 15s mientras el componente está montado
    pollTimer = setInterval(() => {
        if (store.activeUserId) {
            store.openConversation(store.activeUserId);
        }
        store.fetchConversations();
    }, 15_000);
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>
