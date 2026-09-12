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
            class="fixed top-3 left-1/2 -translate-x-1/2 z-50 max-w-lg w-[calc(100%-2rem)] px-4 py-2.5 rounded-xl shadow-xl border flex items-center justify-between gap-3 text-xs font-medium"
            :class="
                isOffline
                    ? 'bg-amber-50 dark:bg-amber-950/80 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-200'
                    : 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200'
            "
            role="status"
            aria-live="polite"
        >
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-base flex-shrink-0">{{ isOffline ? '📡' : '✅' }}</span>
                <p class="truncate">
                    <span v-if="isOffline">
                        <strong>Modo sin conexión:</strong> Podés seguir registrando, todo se
                        sincronizará automáticamente.
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
import { ref, onMounted, onBeforeUnmount } from 'vue';

const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false);
const visible = ref(isOffline.value);
let hideTimeout = null;

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
