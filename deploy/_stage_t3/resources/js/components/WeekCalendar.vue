<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    userId: { type: Number, default: null },
});

const loading = ref(true);
const error = ref(null);
const data = ref(null);
const weekOffset = ref(0);

const fetchData = async () => {
    loading.value = true;
    error.value = null;
    try {
        const weekStart = new Date();
        weekStart.setDate(weekStart.getDate() - weekStart.getDay() + 1 + weekOffset.value * 7);
        const dateStr = weekStart.toISOString().split('T')[0];
        const params = { week_start: dateStr };
        if (props.userId) params.user_id = props.userId;
        const res = await axios.get('/api/historial/week-summary', { params });
        data.value = res.data;
    } catch (e) {
        error.value = e?.response?.data?.message || 'No se pudo cargar';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

// Cuando el trainer/admin cambia de alumno hay que recargar: sin esto la
// tarjeta sigue mostrando la semana del alumno anterior.
watch(() => props.userId, fetchData);

const prevWeek = () => {
    weekOffset.value--;
    fetchData();
};
const nextWeek = () => {
    weekOffset.value++;
    fetchData();
};
const thisWeek = () => {
    weekOffset.value = 0;
    fetchData();
};

const weekLabel = computed(() => {
    if (!data.value) return '';
    const start = data.value.week_start.split('-').reverse().join('/');
    const end = data.value.week_end.split('-').reverse().join('/');
    return `${start} – ${end}`;
});

const goToHistorial = () => {
    window.location.href = '/historial';
};
</script>

<template>
    <div class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-secondary text-[20px]">calendar_view_week</span>
                <div>
                    <h3 class="font-headline-md text-headline-md text-on-surface leading-tight">Esta semana</h3>
                    <span class="text-label-sm text-outline">{{ weekLabel }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    @click="prevWeek"
                    aria-label="Semana anterior"
                    class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <button
                    v-if="weekOffset !== 0"
                    type="button"
                    @click="thisWeek"
                    class="px-2.5 h-8 rounded-lg bg-secondary-container/40 text-on-secondary-container font-label-sm text-label-sm hover:bg-secondary-container transition-colors cursor-pointer"
                >
                    Hoy
                </button>
                <button
                    type="button"
                    @click="nextWeek"
                    aria-label="Semana siguiente"
                    class="w-8 h-8 rounded-lg bg-surface-container hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
        </div>

        <div v-if="loading" class="py-6 text-center text-body-sm text-outline">Cargando…</div>
        <div v-else-if="error" class="py-4 text-center text-body-sm text-error">{{ error }}</div>
        <template v-else-if="data">
            <!-- 7 Day Pills -->
            <div class="grid grid-cols-7 gap-space-xs">
                <div
                    v-for="d in data.days"
                    :key="d.date"
                    :class="[
                        'p-2.5 rounded-xl flex flex-col items-center gap-1 transition-all',
                        d.completado || d.es_hoy
                            ? 'bg-secondary-container/40 shadow-[0_0_15px_rgba(99,102,241,0.2)]'
                            : 'bg-surface-container',
                    ]"
                    :title="
                        d.completado ? `${d.sets} sets · ${d.ejercicios.join(', ')}` : 'Descanso'
                    "
                >
                    <span
                        class="text-[10px] font-bold uppercase"
                        :class="d.completado || d.es_hoy ? 'text-secondary' : 'text-outline'"
                    >
                        {{ d.dia_semana_corto }}
                    </span>
                    <span
                        class="font-headline-md text-headline-md font-bold tabular-nums"
                        :class="d.completado || d.es_hoy ? 'text-on-surface' : 'text-on-surface-variant'"
                    >
                        {{ d.date.slice(-2) }}
                    </span>
                    <span
                        class="w-2 h-2 rounded-full"
                        :class="
                            d.completado
                                ? 'bg-tertiary shadow-[0_0_6px_#4edea3]'
                                : d.es_hoy
                                  ? 'bg-secondary'
                                  : 'w-1.5 h-1.5 bg-outline/30'
                        "
                    ></span>
                </div>
            </div>

            <!-- Weekly Micro Summary -->
            <div class="flex flex-wrap items-center justify-between gap-space-sm pt-2 px-1">
                <div class="flex items-center gap-space-lg">
                    <div>
                        <span class="text-[10px] text-outline uppercase tracking-wider block">Sets</span>
                        <span class="font-headline-md text-headline-md text-on-surface font-bold tabular-nums">
                            {{ data.totals.sets }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-outline uppercase tracking-wider block">Días</span>
                        <span class="font-headline-md text-headline-md text-on-surface font-bold tabular-nums">
                            {{ data.totals.dias_entrenados }}/7
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-outline uppercase tracking-wider block">Racha</span>
                        <span class="font-headline-md text-headline-md text-tertiary font-bold flex items-center gap-0.5 tabular-nums">
                            <span class="material-symbols-outlined text-[16px]">bolt</span>
                            {{ data.streak }}
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="goToHistorial"
                    class="text-label-md text-secondary hover:underline flex items-center gap-1 cursor-pointer"
                >
                    <span>Ver detalle</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
            </div>
        </template>
    </div>
</template>
