<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import ObsidianPill from './common/obsidian/ObsidianPill.vue';

const data = ref(null);
const loading = ref(true);

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/dashboard/today');
        data.value = res.data;
    } catch (err) {
        // Silenciar — el dashboard igual funciona
        data.value = null;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

const greeting = computed(() => {
    if (!data.value) return '';
    return `${data.value.hoy.saludo}${data.value.hoy.nombre ? ', ' + data.value.hoy.nombre.split(' ')[0] : ''}!`;
});

const quickLabel = computed(() => {
    const q = data.value?.quick;
    if (q === 'empezar') return 'Empezar entrenamiento';
    if (q === 'continuar') return 'Continuar';
    if (q === 'descanso') return 'Día de descanso';
    if (q === 'nueva_rutina') return 'Elegir rutina';
    return 'Empezar';
});

const quickColor = computed(() => {
    const q = data.value?.quick;
    if (q === 'continuar') return 'bg-amber-600 hover:bg-amber-700';
    if (q === 'descanso') return 'bg-emerald-600 hover:bg-emerald-700';
    if (q === 'nueva_rutina') return 'bg-indigo-600 hover:bg-indigo-700';
    return 'bg-indigo-600 hover:bg-indigo-700';
});

const startWorkout = () => {
    const q = data.value?.quick;
    if (q === 'nueva_rutina') {
        window.location.href = '/rutinas';
    } else {
        // scrollear al day-selector del dashboard
        const el = document.querySelector('[data-tour="series-list"]');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const goHistorial = () => {
    window.location.href = '/historial';
};
</script>

<template>
    <div
        v-if="!loading && data"
        class="obs-hero relative"
    >
        <div class="relative z-10 flex flex-col gap-4">
            <div class="flex items-center gap-2 flex-wrap">
                <ObsidianPill variant="neutral" icon="📅">
                    {{ data.hoy.dia_semana_es }}
                </ObsidianPill>
                <ObsidianPill
                    v-if="data.stats.streak > 0"
                    variant="orange"
                    icon="🔥"
                >
                    {{ data.stats.streak }} {{ data.stats.streak === 1 ? 'día' : 'días' }}
                </ObsidianPill>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl md:text-3xl font-black tracking-tight truncate">
                        {{ greeting }}
                    </h2>

                    <div
                        v-if="data.rutina"
                        class="mt-2 flex flex-wrap items-center gap-2"
                    >
                        <ObsidianPill variant="violet" icon="🏋️">
                            {{ data.rutina.nombre }}
                        </ObsidianPill>
                        <ObsidianPill variant="emerald" icon="🎯">
                            {{ data.rutina.dia_actual }}
                        </ObsidianPill>
                    </div>
                    <p v-else class="mt-2 text-sm text-white/80">
                        No tenés una rutina activa. Empezá eligiendo una.
                    </p>
                </div>

                <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                    <button
                        type="button"
                        @click="startWorkout"
                        :class="[
                            'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-lg transition-colors backdrop-blur-sm',
                            quickColor,
                        ]"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M6.4 4.6A1 1 0 005 6v8a1 1 0 001.6.8l5-4a1 1 0 000-1.6l-5-4a1 1 0 00-.2-.2zM14 5a1 1 0 10-2 0v10a1 1 0 102 0V5z"
                            />
                        </svg>
                        {{ quickLabel }}
                    </button>
                    <button
                        type="button"
                        @click="goHistorial"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white/80 transition-colors hover:bg-white/10"
                    >
                        Ver historial →
                    </button>
                </div>
            </div>

            <div
                v-if="data.rutina && data.stats.total_sets_30d > 0"
                class="grid grid-cols-3 gap-3 border-t border-white/20 pt-3 text-center"
            >
                <div>
                    <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-white/60">
                        Sets 30d
                    </p>
                    <p class="mt-0.5 text-lg font-black tabular-nums">
                        {{ data.stats.total_sets_30d }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-white/60">
                        Último
                    </p>
                    <p class="mt-0.5 text-lg font-black">
                        {{
                            data.stats.days_since_last_workout === 0
                                ? 'Hoy'
                                : data.stats.days_since_last_workout === 1
                                  ? 'Ayer'
                                  : `Hace ${data.stats.days_since_last_workout}d`
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-[0.12em] font-bold text-white/60">
                        Racha
                    </p>
                    <p class="mt-0.5 text-lg font-black">🔥 {{ data.stats.streak }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
