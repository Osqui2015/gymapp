<template>
    <div
        v-if="activeTab === 'matrix' && sesionesParaMostrar.length > 0"
        class="md:hidden flex flex-col gap-2.5"
    >
        <div class="flex items-center justify-between px-0.5">
            <h2 class="font-headline-md text-headline-md text-on-surface">Sesiones Recientes</h2>
            <span class="text-label-sm text-outline">
                {{ sesionesParaMostrar.length }} sesiones
            </span>
        </div>
        <HistorialSesionCard
            v-for="(s, i) in sesionesParaMostrar.slice(0, 6)"
            :key="i"
            :fecha="s.fecha"
            :fecha-highlight="s.fechaHighlight"
            :fecha-label="s.fechaLabel"
            :duracion="s.duracion"
            :series="s.series"
            :tonelaje="s.tonelaje"
            :rir-prom="s.rirProm"
            :ejercicios="s.ejercicios"
            :expanded="expandedSesionIndex === i"
            :show-actions="expandedSesionIndex === i"
            @toggle="$emit('toggle', i)"
            @ver-detalles="$emit('ver-detalles', s)"
            @repetir-sesion="$emit('repetir-sesion', s)"
        />
    </div>
</template>

<script setup>
import HistorialSesionCard from './HistorialSesionCard.vue';

defineProps({
    activeTab: { type: String, default: 'matrix' },
    sesionesParaMostrar: { type: Array, default: () => [] },
    expandedSesionIndex: { type: [Number, null], default: null },
});

defineEmits(['toggle', 'ver-detalles', 'repetir-sesion']);
</script>
