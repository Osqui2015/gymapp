<template>
    <div v-if="loading" class="space-y-4">
        <div class="grid gap-3 grid-cols-2">
            <BaseSkeleton variant="stat-card" :count="4" />
        </div>
        <div
            class="bg-[#171b26] rounded-2xl border border-slate-800 p-6 shadow-sm"
        >
            <BaseSkeleton variant="text" :count="6" />
        </div>
    </div>

    <div v-else-if="errorHistorial" class="space-y-2">
        <EmptyState
            emoji="📡"
            title="No se pudo cargar el historial"
            :description="errorHistorial"
            cta-text="Reintentar"
            @cta="$emit('retry')"
        />
    </div>

    <div v-else-if="isTrainerOrAdmin && alumnos.length === 0">
        <EmptyState
            emoji="👥"
            title="No tienes alumnos asignados"
            description="Cuando un coordinador te asigne alumnos, vas a poder ver su historial de entrenamiento acá."
        />
    </div>

    <div v-else-if="isTrainerOrAdmin && !selectedAlumnoId">
        <EmptyState
            emoji="👆"
            title="Selecciona un alumno"
            description="Elegí un alumno del selector de arriba para ver su historial."
        />
    </div>
</template>

<script setup>
import BaseSkeleton from '../BaseSkeleton.vue';
import EmptyState from '../EmptyState.vue';

defineProps({
    loading: { type: Boolean, default: false },
    errorHistorial: { type: String, default: null },
    isTrainerOrAdmin: { type: Boolean, default: false },
    alumnos: { type: Array, default: () => [] },
    selectedAlumnoId: { type: [Number, String], default: null },
});

defineEmits(['retry']);
</script>
