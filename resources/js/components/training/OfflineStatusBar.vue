<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
    >
        <div
            v-if="visible"
            class="fixed top-3 left-1/2 -translate-x-1/2 z-50 max-w-lg w-[calc(100%-2rem)] px-4 py-2.5 rounded-xl shadow-xl border flex items-center justify-between gap-3 text-xs font-medium backdrop-blur-md"
            :class="
                isOffline
                    ? 'bg-amber-50/95 dark:bg-amber-950/90 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-200'
                    : isSyncing
                    ? 'bg-indigo-50/95 dark:bg-indigo-950/90 border-indigo-300 dark:border-indigo-700 text-indigo-900 dark:text-indigo-200'
                    : 'bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200'
            "
            role="status"
            aria-live="polite"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="text-base flex-shrink-0 flex items-center justify-center">
                    <span v-if="isOffline">📡</span>
                    <svg
                        v-else-if="isSyncing"
                        class="animate-spin w-4 h-4 text-indigo-600 dark:text-indigo-400"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span v-else>✅</span>
                </span>
                <p class="truncate">
                    <span v-if="isOffline">
                        <strong>Modo sin conexión:</strong> Podés seguir registrando, todo se
                        sincronizará automáticamente.
                        <span
                            v-if="pendingCount > 0"
                            class="ml-1 inline-block bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-100 text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                        >
                            {{ pendingCount }} pendiente{{ pendingCount > 1 ? 's' : '' }}
                        </span>
                    </span>
                    <span v-else-if="isSyncing">
                        <strong>Sincronizando:</strong> Subiendo series guardadas sin conexión...
                    </span>
                    <span v-else>
                        <strong>Conexión restablecida:</strong> Datos sincronizados.
                    </span>
                </p>
            </div>
            <button
                type="button"
                @click="dismiss"
                class="p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 flex-shrink-0 transition-colors"
                aria-label="Cerrar aviso de conexión"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useOfflineSeries } from '../../composables/useOfflineSeries';

const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false);
const visible = ref(isOffline.value);
let hideTimeout = null;

const { pendingCount, isSyncing } = useOfflineSeries();

// Si entran series en cola mientras está offline o sincronizando, asegurar visibilidad
watch(isSyncing, (syncing) => {
    if (syncing) {
        visible.value = true;
        if (hideTimeout) clearTimeout(hideTimeout);
    } else if (!isOffline.value) {
        // Al terminar el sync, mostrar el feedback de éxito brevemente
        visible.value = true;
        if (hideTimeout) clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            visible.value = false;
        }, 4000);
    }
});

const onOffline = () => {
    isOffline.value = true;
    visible.value = true;
    if (hideTimeout) clearTimeout(hideTimeout);
};

const onOnline = () => {
    isOffline.value = false;
    visible.value = true;
    if (hideTimeout) clearTimeout(hideTimeout);
    // Mostrar feedback verde brevemente y ocultar
    hideTimeout = setTimeout(() => {
        visible.value = false;
    }, 4000);
};

const dismiss = () => {
    visible.value = false;
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('offline', onOffline);
        window.addEventListener('online', onOnline);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('offline', onOffline);
        window.removeEventListener('online', onOnline);
    }
    if (hideTimeout) clearTimeout(hideTimeout);
});
</script>
