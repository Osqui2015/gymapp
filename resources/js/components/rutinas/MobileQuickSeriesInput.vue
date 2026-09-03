<!--
  Bottom-sheet para carga rápida de series en mobile.

  Pensado para abrirse desde un FAB en RutinasAccordion.
  Muestra los ejercicios del día como tarjetas, cada una con inputs
  numéricos grandes para peso y reps de cada serie. Botón "✓" por serie
  marca como completada; doble-tap o Enter confirman.

  Emite:
    - close
    - save(records) -> [{ ejercicio_nombre, series_numero, peso, reps_realizadas, completado, ... }]
-->
<template>
    <Teleport to="body">
        <Transition name="sheet">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center md:items-center"
                role="dialog"
                aria-modal="true"
                :aria-label="`Carga rápida de series · ${dia || ''}`"
                @click.self="$emit('close')"
            >
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')"></div>

                <div
                    ref="sheetEl"
                    class="relative w-full md:max-w-2xl bg-white dark:bg-gray-900 rounded-t-3xl md:rounded-3xl shadow-2xl max-h-[92vh] flex flex-col"
                >
                    <!-- handle (drag affordance) -->
                    <div class="md:hidden flex justify-center pt-2">
                        <div class="w-12 h-1.5 rounded-full bg-gray-300 dark:bg-gray-700"></div>
                    </div>

                    <!-- header -->
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                                ⚡ Carga rápida
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ dia }}{{ rutinaNombre ? ` · ${rutinaNombre}` : '' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400"
                            aria-label="Cerrar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- body scrollable -->
                    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4 overscroll-contain">
                        <div
                            v-for="(ej, ei) in ejercicios"
                            :key="ej.id ?? ei"
                            class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-4"
                        >
                            <div class="flex items-baseline justify-between gap-3 mb-3">
                                <h3 class="font-bold text-gray-900 dark:text-white truncate">
                                    {{ ej.ejercicio_nombre || ej.nombre }}
                                    <span v-if="ej.superserie_grupo" class="ml-2 text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full">
                                        SS {{ ej.superserie_grupo }}
                                    </span>
                                </h3>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ ej.reps_min }}–{{ ej.reps_max }} reps
                                </span>
                            </div>

                            <div class="space-y-2">
                                <div
                                    v-for="serie in seriesDe(ej)"
                                    :key="serie.series_numero"
                                    class="flex items-center gap-2"
                                >
                                    <span class="w-10 text-center text-xs font-bold text-gray-500 dark:text-gray-400">
                                        #{{ serie.series_numero }}
                                    </span>

                                    <label class="flex-1 flex items-center bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1.5 focus-within:ring-2 focus-within:ring-indigo-500">
                                        <span class="text-[10px] uppercase tracking-wider text-gray-400 mr-1">kg</span>
                                        <input
                                            type="number"
                                            inputmode="decimal"
                                            step="0.5"
                                            v-model.number="serie.peso"
                                            @keyup.enter="marcarCompletada(serie)"
                                            class="w-full bg-transparent outline-none text-base font-semibold text-gray-900 dark:text-white"
                                            placeholder="0"
                                        />
                                    </label>

                                    <label class="flex-1 flex items-center bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 px-2 py-1.5 focus-within:ring-2 focus-within:ring-indigo-500">
                                        <span class="text-[10px] uppercase tracking-wider text-gray-400 mr-1">reps</span>
                                        <input
                                            type="number"
                                            inputmode="numeric"
                                            v-model.number="serie.reps_realizadas"
                                            @keyup.enter="marcarCompletada(serie)"
                                            class="w-full bg-transparent outline-none text-base font-semibold text-gray-900 dark:text-white"
                                            placeholder="0"
                                        />
                                    </label>

                                    <button
                                        type="button"
                                        @click="marcarCompletada(serie)"
                                        :class="[
                                            'w-10 h-10 rounded-xl flex items-center justify-center font-bold transition-all active:scale-95',
                                            serie.completado
                                                ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30'
                                                : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                                        ]"
                                        :aria-pressed="serie.completado"
                                        :aria-label="serie.completado ? 'Serie completada' : 'Marcar serie completada'"
                                    >
                                        <svg v-if="serie.completado" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else class="text-xs">✓</span>
                                    </button>
                                </div>

                                <!-- Nota libre por set (opcional, se muestra siempre para no esconderla) -->
                                <div class="flex items-center gap-2 pl-12">
                                    <span class="text-[10px] uppercase tracking-wider text-gray-400 whitespace-nowrap" title="Cómo te sentiste en este ejercicio">
                                        ✏️ nota
                                    </span>
                                    <input
                                        v-model="ej._nota_user"
                                        type="text"
                                        maxlength="500"
                                        placeholder="Opcional: cómo se sintió el ejercicio (ej: dolió el hombro, RPE 8, fácil)"
                                        class="flex-1 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-if="!ejercicios.length" class="text-center py-12 text-sm text-gray-500 dark:text-gray-400">
                            No hay ejercicios para este día.
                        </div>
                    </div>

                    <!-- footer -->
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex items-center gap-3 safe-area-inset">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            <span class="text-emerald-600 dark:text-emerald-400">{{ totalCompletadas }}</span>
                            / {{ totalSeries }} series
                        </div>
                        <div class="flex-1"></div>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="guardar"
                            :disabled="!totalCompletadas || saving"
                            class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
                        >
                            {{ saving ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    open: { type: Boolean, default: false },
    dia: { type: String, default: '' },
    rutinaNombre: { type: String, default: '' },
    ejercicios: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const toast = useToast();
const sheetEl = ref(null);

// estado local: copiamos los ejercicios y añadimos inputs por serie
const localEjercicios = ref([]);

const clonar = () => {
    localEjercicios.value = props.ejercicios.map((ej) => {
        const planSeries = Number(ej.series) || 3;
        const series = [];
        for (let i = 1; i <= planSeries; i++) {
            series.push({
                series_numero: i,
                peso: ej.peso_sugerido ?? null,
                reps_realizadas: null,
                completado: false,
            });
        }
        return { ...ej, _series: series, _nota_user: '' };
    });
};

watch(() => props.open, (val) => {
    if (val) {
        clonar();
        nextTick(() => {
            // focus trap básico: enfocar el sheet al abrir
            sheetEl.value?.focus?.();
            document.body.style.overflow = 'hidden';
        });
    } else {
        document.body.style.overflow = '';
    }
}, { immediate: true });

const seriesDe = (ej) => ej._series || [];

const totalCompletadas = computed(() =>
    localEjercicios.value.reduce(
        (acc, ej) => acc + seriesDe(ej).filter((s) => s.completado).length,
        0,
    ),
);

const totalSeries = computed(() =>
    localEjercicios.value.reduce((acc, ej) => acc + seriesDe(ej).length, 0),
);

const marcarCompletada = (serie) => {
    // si ya está marcada, la desmarcamos; si no, intentamos marcar
    if (serie.completado) {
        serie.completado = false;
        return;
    }
    if (serie.peso == null || serie.reps_realizadas == null) {
        toast.warning('Cargá peso y reps antes de marcar como hecha.');
        return;
    }
    serie.completado = true;
};

const guardar = () => {
    const records = [];
    localEjercicios.value.forEach((ej) => {
        const nota = (ej._nota_user || '').trim();
        seriesDe(ej).forEach((s) => {
            if (!s.completado) return;
            const rec = {
                ejercicio_nombre: ej.ejercicio_nombre || ej.nombre,
                series_numero: s.series_numero,
                peso: s.peso,
                reps_realizadas: s.reps_realizadas,
                reps_min: ej.reps_min,
                reps_max: ej.reps_max,
                descanso_min: ej.descanso_min,
                superserie_grupo: ej.superserie_grupo || null,
                completado: true,
            };
            // La nota se persiste en CADA set del ejercicio (así queda searchable
            // y los trainers pueden ver "qué se sintió esa semana en sentadilla").
            if (nota) rec.nota_user = nota;
            records.push(rec);
        });
    });
    if (!records.length) {
        toast.warning('Marcá al menos una serie antes de guardar.');
        return;
    }
    emit('save', { dia: props.dia, rutina_nombre: props.rutinaNombre, records });
};
</script>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.2s ease;
}
.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}
.sheet-enter-active > div:last-child,
.sheet-leave-active > div:last-child {
    transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.sheet-enter-from > div:last-child,
.sheet-leave-to > div:last-child {
    transform: translateY(100%);
}
@media (prefers-reduced-motion: reduce) {
    .sheet-enter-active,
    .sheet-leave-active,
    .sheet-enter-active > div:last-child,
    .sheet-leave-active > div:last-child {
        transition: none;
    }
}
.safe-area-inset {
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}
</style>
