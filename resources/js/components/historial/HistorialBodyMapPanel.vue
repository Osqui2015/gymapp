<template>
    <div v-show="activeTab === 'body_map'" class="space-y-4">
        <div
            class="bg-[#131924] rounded-xl border border-[#232D42] shadow-lg overflow-hidden"
        >
            <div class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-2.5 rounded bg-indigo-500"></div>
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-wide uppercase">
                            Mapa Corporal de Telemetría Muscular
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Visualizá qué músculos entrenás, cuáles están fatigados y cuál es tu mejor 1RM
                        </p>
                    </div>
                </div>
                <div
                    class="inline-flex rounded-lg bg-[#101521] border border-[#232D42] p-1 text-xs font-semibold gap-1"
                >
                    <button
                        v-for="m in ['balance', 'fatigue', 'strength']"
                        :key="m"
                        type="button"
                        @click="bodyMapMode = m"
                        :class="[
                            'px-3.5 py-1.5 rounded-md transition-colors cursor-pointer',
                            bodyMapMode === m
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-400 hover:text-white',
                        ]"
                    >
                        {{
                            m === 'balance'
                                ? 'Volumen'
                                : m === 'fatigue'
                                  ? 'Fatiga'
                                  : 'Fuerza'
                        }}
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div
                    v-if="bodyMapLoading"
                    class="flex items-center justify-center py-16 text-slate-400"
                >
                    <svg class="animate-spin w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24">
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
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        ></path>
                    </svg>
                </div>

                <div
                    v-else-if="!bodyMapData || bodyMapData.historiales.length === 0"
                    class="text-center py-12 text-slate-400"
                >
                    <p class="text-sm font-semibold text-white">No hay entrenamientos en los últimos 90 días.</p>
                    <p class="text-xs mt-1">
                        Empezá a registrar sesiones y vas a ver tu mapa acá.
                    </p>
                </div>

                <BodyMap
                    v-else
                    :levels="bodyMapLevels"
                    :mode="bodyMapMode"
                    :muscle-labels="muscleLabels"
                    :initial-gender="bodyMapGender"
                    @muscle-click="$emit('muscle-click', $event)"
                />
            </div>

            <div
                v-if="bodyMapData && bodyMapData.historiales.length > 0"
                class="px-4 py-3 bg-[#101521] border-t border-[#232D42] flex items-center justify-between text-xs text-slate-400 font-mono"
            >
                <span>Ventana: últimos 90 días</span>
                <span class="text-indigo-400 font-semibold">
                    {{ bodyMapData.historiales.length }} sets · {{ muscleCount }} músculos mapeados
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import BodyMap from '../BodyMap.vue';
import { useMuscleLoad } from '../../composables/useMuscleLoad';

const props = defineProps({
    activeTab: { type: String, default: 'matrix' },
    bodyMapData: { type: Object, default: () => ({ historiales: [], musculos: [] }) },
    bodyMapLoading: { type: Boolean, default: false },
});

defineEmits(['muscle-click']);

const bodyMapMode = ref('balance'); // 'balance' | 'fatigue' | 'strength'
const bodyMapGender = ref('male');

const bodyMapHistorial = computed(() => props.bodyMapData.historiales);
const bodyMapEjercicios = computed(() => props.bodyMapData.ejercicios || {});
const { levelsFor: bodyMapLevelsFor } = useMuscleLoad(bodyMapHistorial, {
    ejercicios: bodyMapEjercicios,
});
const bodyMapLevels = computed(() => bodyMapLevelsFor(bodyMapMode.value));

const muscleLabels = computed(() => {
    const out = {};
    for (const m of props.bodyMapData.musculos || []) {
        out[m.slug] = m.nombre_es;
    }
    return out;
});

const muscleCount = computed(() => {
    const used = new Set();
    const mapa = bodyMapEjercicios.value;
    for (const h of props.bodyMapData.historiales || []) {
        const musculos =
            h.ejercicio?.musculos ||
            (h.ejercicio_id != null ? mapa[h.ejercicio_id]?.musculos : null) ||
            [];
        for (const m of musculos) {
            used.add(m.musculo_slug);
        }
    }
    return used.size;
});
</script>
