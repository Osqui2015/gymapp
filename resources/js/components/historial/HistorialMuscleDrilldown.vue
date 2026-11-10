<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="selectedMuscleSlug"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <div
                    class="bg-[#131924] border border-[#232D42] rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto text-slate-200"
                >
                    <div
                        class="sticky top-0 bg-[#18202F] border-b border-[#232D42] px-6 py-4 flex items-start justify-between gap-4 z-10"
                    >
                        <div>
                            <p
                                class="text-[11px] uppercase tracking-wider text-indigo-400 font-bold"
                            >
                                Ejercicios que trabajan
                            </p>
                            <h2
                                class="text-xl font-extrabold text-white mt-0.5"
                            >
                                {{
                                    muscleDrilldown.data?.musculo?.nombre_es ||
                                    muscleLabels[selectedMuscleSlug] ||
                                    selectedMuscleSlug
                                }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-shrink-0 p-2 rounded-lg text-slate-400 hover:text-white hover:bg-[#232D42] cursor-pointer"
                            aria-label="Cerrar"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div
                            v-if="muscleDrilldown.loading"
                            class="flex items-center justify-center py-12"
                        >
                            <svg
                                class="animate-spin w-8 h-8 text-indigo-400"
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
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                ></path>
                            </svg>
                        </div>

                        <div
                            v-else-if="muscleDrilldown.error"
                            class="text-center py-8 text-rose-400 text-xs"
                        >
                            {{ muscleDrilldown.error }}
                        </div>

                        <div
                            v-else-if="muscleDrilldown.data?.ejercicios?.length === 0"
                            class="text-center py-8 text-slate-400 text-xs"
                        >
                            No hay ejercicios asignados a este músculo.
                        </div>

                        <div v-else>
                            <p class="text-xs text-slate-400 font-mono mb-3">
                                {{ muscleDrilldown.data.ejercicios.length }} ejercicios
                                · ordenados por volumen reciente (30d)
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="ej in muscleDrilldown.data.ejercicios"
                                    :key="ej.id"
                                    class="rounded-xl bg-[#101521] border border-[#232D42] transition-colors"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 p-3 text-left hover:bg-[#18202F] rounded-xl cursor-pointer"
                                        @click="$emit('toggle-ejercicio', ej)"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="font-bold text-white text-xs truncate"
                                            >
                                                {{ ej.nombre }}
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-400 mt-0.5"
                                            >
                                                {{ ej.equipamiento }}
                                            </p>
                                        </div>
                                        <div class="text-right flex-shrink-0 font-mono">
                                            <p
                                                class="text-xs font-bold text-indigo-400"
                                            >
                                                {{ ej.sets_30d }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-400"
                                                    >sets</span
                                                >
                                            </p>
                                            <p
                                                v-if="ej.max_peso_30d"
                                                class="text-[11px] text-amber-400 mt-0.5"
                                            >
                                                ★ {{ ej.max_peso_30d.toFixed(1) }} kg
                                            </p>
                                        </div>
                                        <svg
                                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                                            :class="{
                                                'rotate-180':
                                                    selectedEjercicioId === ej.id,
                                            }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>
                                    <div
                                        v-if="selectedEjercicioId === ej.id"
                                        class="border-t border-[#232D42] p-3"
                                    >
                                        <OneRmChart
                                            :ejercicio-nombre="ej.nombre"
                                            :user-id="selectedAlumnoId"
                                            :formula="rmFormula"
                                        />
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import OneRmChart from '../OneRmChart.vue';

defineProps({
    selectedMuscleSlug: { type: String, default: null },
    muscleDrilldown: {
        type: Object,
        default: () => ({ loading: false, error: null, data: null }),
    },
    muscleLabels: { type: Object, default: () => ({}) },
    selectedEjercicioId: { type: [Number, String, null], default: null },
    selectedAlumnoId: { type: [Number, String], default: null },
    rmFormula: { type: String, default: 'epley' },
});

defineEmits(['close', 'toggle-ejercicio']);
</script>
