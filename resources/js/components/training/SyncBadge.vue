<!--
  SyncBadge — Indicador de estado de conexion / sincronizacion.

  Estados:
    - online (verde): "En linea" - todo se guarda directamente
    - offline (amarillo): "Sin conexion - se guarda en este dispositivo"
    - syncing (azul, animado): "Sincronizando X pendientes..."
    - pending (amarillo): "X cambios sin sincronizar"

  Se posiciona tipicamente como banner fijo arriba de la pantalla de entrenamiento.
  Ocupa poco espacio en mobile.

  Props:
    - pending: numero de items pendientes de sincronizar (opcional, lo provee useOfflineSeries)
-->
<template>
    <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="state !== 'hidden'"
            :class="[
                'fixed top-2 left-1/2 -translate-x-1/2 z-50',
                'flex items-center gap-2 px-3 py-1.5 rounded-full',
                'text-xs font-semibold shadow-lg backdrop-blur-sm',
                'border',
                stateClasses,
            ]"
            role="status"
            aria-live="polite"
        >
            <span
                :class="[
                    'w-2 h-2 rounded-full',
                    dotClasses,
                    state === 'syncing' ? 'animate-pulse' : '',
                ]"
            ></span>
            <span>{{ label }}</span>
        </div>
    </Transition>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    pending: { type: Number, default: 0 },
    syncing: { type: Boolean, default: false },
});

const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

const updateOnline = () => {
    online.value = typeof navigator !== 'undefined' ? navigator.onLine : true;
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('online', updateOnline);
        window.addEventListener('offline', updateOnline);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('online', updateOnline);
        window.removeEventListener('offline', updateOnline);
    }
});

const state = computed(() => {
    if (props.syncing) return 'syncing';
    if (!online.value) return 'offline';
    if (props.pending > 0) return 'pending';
    return 'hidden';
});

const label = computed(() => {
    if (props.syncing) return `Sincronizando ${props.pending}...`;
    if (!online.value) return 'Sin conexion - se guarda en este dispositivo';
    if (props.pending > 0)
        return `${props.pending} ${props.pending === 1 ? 'cambio' : 'cambios'} sin sincronizar`;
    return '';
});

const stateClasses = computed(() => {
    switch (state.value) {
        case 'offline':
            return 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-700';
        case 'pending':
            return 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-700';
        case 'syncing':
            return 'bg-indigo-50 text-indigo-800 border-indigo-300 dark:bg-indigo-900/40 dark:text-indigo-200 dark:border-indigo-700';
        default:
            return 'hidden';
    }
});

const dotClasses = computed(() => {
    switch (state.value) {
        case 'offline':
        case 'pending':
            return 'bg-amber-500';
        case 'syncing':
            return 'bg-indigo-500';
        default:
            return 'bg-emerald-500';
    }
});
</script>
