<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex flex-col bg-gray-950 text-white select-none overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Modo Entrenamiento Activo"
    >
        <!-- Top Bar: Cronómetro, Rutina y Controles -->
        <header
            class="px-4 py-3 bg-gray-900/90 border-b border-gray-800 flex items-center justify-between gap-3 backdrop-blur shrink-0"
        >
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    @click="$emit('minimize')"
                    class="p-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors cursor-pointer"
                    aria-label="Minimizar sesión"
                    title="Minimizar (continúa en segundo plano)"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>

                <div class="min-w-0">
                    <p
                        class="text-xs font-semibold text-indigo-400 uppercase tracking-wider truncate"
                    >
                        {{ store.session.dia }} · {{ store.session.rutina_nombre }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-block w-2.5 h-2.5 rounded-full"
                            :class="
                                store.isPaused
                                    ? 'bg-amber-400 animate-pulse'
                                    : 'bg-emerald-400 animate-pulse'
                            "
                        ></span>
                        <span class="text-lg font-mono font-bold tracking-tight">
                            {{ formattedTime }}
                        </span>
                        <span v-if="store.isPaused" class="text-xs font-bold text-amber-400">
                            (Pausado)
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Botón Pausa / Reanudar -->
                <button
                    type="button"
                    @click="togglePause"
                    class="p-2.5 rounded-xl border border-gray-700 hover:bg-gray-800 text-gray-200 transition-all cursor-pointer"
                    :title="store.isPaused ? 'Reanudar cronómetro' : 'Pausar cronómetro'"
                    :aria-label="store.isPaused ? 'Reanudar' : 'Pausar'"
                >
                    <svg
                        v-if="!store.isPaused"
                        class="w-5 h-5 text-amber-400"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 text-emerald-400"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>

                <!-- Botón Finalizar -->
                <button
                    type="button"
                    @click="handleFinalizar"
                    class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-950/40 transition-all cursor-pointer"
                >
                    Finalizar
                </button>
            </div>
        </header>

        <!-- Barra de Progreso Global -->
        <div
            class="w-full bg-gray-900 border-b border-gray-800 px-4 py-2 shrink-0 flex items-center justify-between text-xs text-gray-400"
        >
            <span>
                Series:
                <strong class="text-emerald-400">{{ store.totalSeriesCompletadas }}</strong> /
                {{ store.totalSeriesObjetivo }}
            </span>
            <div class="w-32 sm:w-48 h-2 bg-gray-800 rounded-full overflow-hidden mx-3">
                <div
                    class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 transition-all duration-300"
                    :style="{ width: `${store.progresoPorcentaje}%` }"
                ></div>
            </div>
            <span>{{ store.progresoPorcentaje }}%</span>
        </div>

        <!-- Contenedor Scrollable del Ejercicio -->
        <main class="flex-1 overflow-y-auto px-4 py-4 space-y-4 overscroll-contain">
            <!-- Selector de Ejercicios / Carrusel de navegación -->
            <div v-if="store.currentEjercicio" class="flex items-center justify-between gap-2">
                <button
                    type="button"
                    @click="store.prevEjercicio"
                    :disabled="store.session.currentEjercicioIndex === 0"
                    class="p-2 rounded-xl bg-gray-900 border border-gray-800 text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    aria-label="Ejercicio anterior"
                >
                    ◀
                </button>

                <div class="text-center min-w-0 flex-1">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        Ejercicio {{ store.session.currentEjercicioIndex + 1 }} de
                        {{ store.session.ejercicios.length }}
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white truncate">
                        {{ store.currentEjercicio.nombre }}
                    </h2>
                    <div class="flex items-center justify-center gap-2 mt-1 text-xs text-gray-400">
                        <span class="bg-gray-800 px-2 py-0.5 rounded-md">
                            🎯 {{ store.currentEjercicio.reps_min }}–{{
                                store.currentEjercicio.reps_max
                            }}
                            reps
                        </span>
                        <span class="bg-gray-800 px-2 py-0.5 rounded-md text-orange-300">
                            ⏱ {{ store.currentEjercicio.descanso_min }} min descanso
                        </span>
                        <span
                            v-if="store.currentEjercicio.superserie_grupo"
                            class="bg-indigo-900/60 text-indigo-300 px-2 py-0.5 rounded-md font-semibold"
                        >
                            SS {{ store.currentEjercicio.superserie_grupo }}
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="store.nextEjercicio"
                    :disabled="
                        store.session.currentEjercicioIndex >= store.session.ejercicios.length - 1
                    "
                    class="p-2 rounded-xl bg-gray-900 border border-gray-800 text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    aria-label="Siguiente ejercicio"
                >
                    ▶
                </button>
            </div>

            <!-- Focus Card: Serie Actual con Glove Mode -->
            <section
                v-if="store.currentEjercicio"
                class="bg-gray-900 rounded-2xl border border-gray-800 p-5 shadow-2xl space-y-5"
            >
                <div class="flex items-center justify-between border-b border-gray-800 pb-3">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        Configurar Serie #{{ store.session.currentSerieNumero }}
                    </span>

                    <!-- Selector de Tipo de Serie -->
                    <div class="inline-flex rounded-xl bg-gray-800 p-1 text-[11px] font-semibold">
                        <button
                            v-for="tipo in tiposSerie"
                            :key="tipo.id"
                            type="button"
                            @click="form.tipo_serie = tipo.id"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-all cursor-pointer',
                                form.tipo_serie === tipo.id
                                    ? tipo.activeClass
                                    : 'text-gray-400 hover:text-white',
                            ]"
                        >
                            {{ tipo.label }}
                        </button>
                    </div>
                </div>

                <!-- Controles Glove Mode: PESO -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                            Peso (kg)
                        </label>
                        <span class="text-xs text-indigo-400 font-semibold">Toques rápidos ±</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Botones decremento -->
                        <div class="grid grid-cols-3 gap-1 shrink-0">
                            <button
                                type="button"
                                @click="ajustarPeso(-5)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer"
                            >
                                -5
                            </button>
                            <button
                                type="button"
                                @click="ajustarPeso(-2.5)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer"
                            >
                                -2.5
                            </button>
                            <button
                                type="button"
                                @click="ajustarPeso(-1)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-rose-300 cursor-pointer"
                            >
                                -1
                            </button>
                        </div>

                        <!-- Display grande de peso -->
                        <div
                            class="flex-1 relative flex items-center justify-center bg-gray-950 border border-gray-700 rounded-2xl h-14"
                        >
                            <input
                                v-model.number="form.peso"
                                type="number"
                                inputmode="decimal"
                                step="0.5"
                                min="0"
                                class="w-full bg-transparent text-center text-3xl font-black text-white outline-none"
                                placeholder="0"
                            />
                            <span class="absolute right-3 text-xs font-bold text-gray-500 uppercase"
                                >kg</span
                            >
                        </div>

                        <!-- Botones incremento -->
                        <div class="grid grid-cols-3 gap-1 shrink-0">
                            <button
                                type="button"
                                @click="ajustarPeso(1)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer"
                            >
                                +1
                            </button>
                            <button
                                type="button"
                                @click="ajustarPeso(2.5)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer"
                            >
                                +2.5
                            </button>
                            <button
                                type="button"
                                @click="ajustarPeso(5)"
                                class="w-10 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-xs text-emerald-300 cursor-pointer"
                            >
                                +5
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Controles Glove Mode: REPETICIONES -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                            Repeticiones Realizadas
                        </label>
                        <span class="text-xs text-indigo-400 font-semibold"
                            >Objetivo: {{ store.currentEjercicio.reps_min }}–{{
                                store.currentEjercicio.reps_max
                            }}</span
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Decremento reps -->
                        <div class="grid grid-cols-2 gap-1 shrink-0">
                            <button
                                type="button"
                                @click="ajustarReps(-2)"
                                class="w-12 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-sm text-rose-300 cursor-pointer"
                            >
                                -2
                            </button>
                            <button
                                type="button"
                                @click="ajustarReps(-1)"
                                class="w-12 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-sm text-rose-300 cursor-pointer"
                            >
                                -1
                            </button>
                        </div>

                        <!-- Display grande de reps -->
                        <div
                            class="flex-1 relative flex items-center justify-center bg-gray-950 border border-gray-700 rounded-2xl h-14"
                        >
                            <input
                                v-model.number="form.reps"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="w-full bg-transparent text-center text-3xl font-black text-white outline-none"
                                placeholder="0"
                            />
                            <span class="absolute right-3 text-xs font-bold text-gray-500 uppercase"
                                >reps</span
                            >
                        </div>

                        <!-- Incremento reps -->
                        <div class="grid grid-cols-2 gap-1 shrink-0">
                            <button
                                type="button"
                                @click="ajustarReps(1)"
                                class="w-12 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-sm text-emerald-300 cursor-pointer"
                            >
                                +1
                            </button>
                            <button
                                type="button"
                                @click="ajustarReps(2)"
                                class="w-12 h-12 bg-gray-800 hover:bg-gray-700 active:scale-95 rounded-xl font-bold text-sm text-emerald-300 cursor-pointer"
                            >
                                +2
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Esfuerzo (RIR / RPE) -->
                <div class="bg-gray-950/60 rounded-xl p-3 border border-gray-800/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-400">Esfuerzo percibido:</span>
                        <div class="inline-flex rounded-lg bg-gray-800 p-0.5 text-xs">
                            <button
                                type="button"
                                @click="form.esfuerzo_tipo = 'rir'"
                                :class="
                                    form.esfuerzo_tipo === 'rir'
                                        ? 'bg-emerald-500 text-white font-bold'
                                        : 'text-gray-400'
                                "
                                class="px-2 py-0.5 rounded-md transition-colors"
                            >
                                RIR
                            </button>
                            <button
                                type="button"
                                @click="form.esfuerzo_tipo = 'rpe'"
                                :class="
                                    form.esfuerzo_tipo === 'rpe'
                                        ? 'bg-amber-500 text-white font-bold'
                                        : 'text-gray-400'
                                "
                                class="px-2 py-0.5 rounded-md transition-colors"
                            >
                                RPE
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-1 overflow-x-auto py-1">
                        <button
                            v-for="val in esfuerzoOptions"
                            :key="val"
                            type="button"
                            @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                            :class="[
                                'w-9 h-9 rounded-xl font-bold text-xs transition-all cursor-pointer flex items-center justify-center',
                                form.esfuerzo_valor === val
                                    ? form.esfuerzo_tipo === 'rir'
                                        ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/40 scale-105'
                                        : 'bg-amber-500 text-white shadow-md shadow-amber-500/40 scale-105'
                                    : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white',
                            ]"
                        >
                            {{ val }}
                        </button>
                    </div>
                </div>

                <!-- Botón Gigante: COMPLETAR SERIE -->
                <button
                    type="button"
                    @click="completarSerie"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-400 hover:to-teal-400 active:scale-[0.98] text-white text-lg font-black tracking-wide shadow-xl shadow-emerald-950/60 flex items-center justify-center gap-3 transition-all cursor-pointer"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span>COMPLETAR SERIE #{{ store.session.currentSerieNumero }}</span>
                </button>

                <!-- Botón Deshacer -->
                <div v-if="store.canUndo" class="text-center pt-1">
                    <button
                        type="button"
                        @click="deshacer"
                        class="text-xs font-semibold text-rose-400 hover:text-rose-300 underline underline-offset-4 cursor-pointer"
                    >
                        ↩ Deshacer última serie completada
                    </button>
                </div>
            </section>

            <!-- Lista de Series ya completadas en este ejercicio -->
            <section
                v-if="
                    store.currentEjercicio &&
                    store.currentEjercicio.sets &&
                    store.currentEjercicio.sets.length > 0
                "
                class="bg-gray-900 rounded-2xl border border-gray-800 p-4 space-y-2"
            >
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide">
                    Series registradas en esta sesión ({{ store.currentEjercicio.nombre }})
                </h3>
                <div class="space-y-1.5">
                    <div
                        v-for="(s, idx) in store.currentEjercicio.sets"
                        :key="idx"
                        class="flex items-center justify-between px-3 py-2 rounded-xl bg-gray-950/60 border border-gray-800 text-xs"
                    >
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-emerald-400">#{{ s.series_numero }}</span>
                            <span class="font-bold text-white">{{ s.peso }} kg</span>
                            <span class="text-gray-400">×</span>
                            <span class="font-bold text-white">{{ s.reps }} reps</span>
                            <span
                                v-if="s.tipo_serie && s.tipo_serie !== 'efectiva'"
                                class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-amber-900/60 text-amber-300"
                            >
                                {{ s.tipo_serie }}
                            </span>
                            <span
                                v-if="s.esfuerzo_valor != null"
                                class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-800 text-gray-300"
                            >
                                {{ s.esfuerzo_tipo?.toUpperCase() }} {{ s.esfuerzo_valor }}
                            </span>
                        </div>
                        <span class="text-[10px] text-gray-500 font-mono">
                            {{ formatSetTime(s.completed_at) }}
                        </span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import { useRestTimerStore } from '@/stores/restTimer';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['minimize', 'finish', 'discard']);

const store = useTrainingSessionStore();
const restTimer = useRestTimerStore();
const offline = useOfflineSeries();
const wakeLock = useWakeLock();

// Tipos de serie disponibles
const tiposSerie = [
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-emerald-500 text-white' },
    { id: 'calentamiento', label: 'Calentamiento', activeClass: 'bg-indigo-500 text-white' },
    { id: 'dropset', label: 'Drop Set', activeClass: 'bg-amber-500 text-white' },
    { id: 'al_fallo', label: 'Al Fallo', activeClass: 'bg-rose-500 text-white' },
];

const form = ref({
    peso: 0,
    reps: 0,
    tipo_serie: 'efectiva',
    esfuerzo_tipo: 'rir',
    esfuerzo_valor: null,
    nota_user: '',
});

// Opciones dinámicas de esfuerzo (RIR 0..5, RPE 6..10)
const esfuerzoOptions = computed(() => {
    return form.value.esfuerzo_tipo === 'rir' ? [0, 1, 2, 3, 4, 5] : [6, 7, 8, 9, 10];
});

// Formateo del cronómetro de la sesión
const formattedTime = computed(() => {
    const s = store.elapsed;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const ajustarPeso = (delta) => {
    const nuevo = Math.max(0, (Number(form.value.peso) || 0) + delta);
    form.value.peso = Math.round(nuevo * 2) / 2; // redondear a 0.5
};

const ajustarReps = (delta) => {
    form.value.reps = Math.max(0, (Number(form.value.reps) || 0) + delta);
};

const formatSetTime = (isoString) => {
    if (!isoString) return '';
    const d = new Date(isoString);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const togglePause = () => {
    if (store.isPaused) {
        store.resume();
    } else {
        store.pause();
    }
};

// Mantener valores del set previo del ejercicio si no hay cargados
watch(
    () => store.currentEjercicio,
    (ej) => {
        if (!ej) return;
        if (ej.sets && ej.sets.length > 0) {
            const last = ej.sets[ej.sets.length - 1];
            form.value.peso = last.peso;
            form.value.reps = last.reps;
        } else {
            // Predeterminado según objetivo
            form.value.reps = Number(ej.reps_min) || 8;
        }
    },
    { immediate: true }
);

const completarSerie = async () => {
    const ej = store.currentEjercicio;
    if (!ej) return;

    const currentSerieNum = store.session.currentSerieNumero;
    const pesoNum = Number(form.value.peso) || 0;
    const repsNum = Number(form.value.reps) || 0;

    // 1. Guardar en Pinia store
    store.recordSet({
        peso: pesoNum,
        reps: repsNum,
        tipo_serie: form.value.tipo_serie,
        esfuerzo_tipo: form.value.esfuerzo_tipo,
        esfuerzo_valor: form.value.esfuerzo_valor,
        nota_user: form.value.nota_user,
    });

    // 2. Haptic feedback
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate([80, 50, 80]);
    }

    // 3. Persistir en backend / IndexedDB offline
    try {
        await offline.recordSet({
            fecha: new Date().toISOString().split('T')[0],
            rutina_nombre: store.session.rutina_nombre,
            dia: store.session.dia,
            ejercicio_nombre: ej.nombre,
            series_numero: currentSerieNum,
            series_completadas: 1,
            reps_min: String(ej.reps_min || '8'),
            reps_max: String(ej.reps_max || '10'),
            reps_realizadas: repsNum,
            descanso_min: Number(ej.descanso_min || 1.5),
            peso: pesoNum,
            completado: true,
            tipo_serie: form.value.tipo_serie,
            sesion_uuid: store.session.id,
            esfuerzo_tipo: form.value.esfuerzo_tipo,
            esfuerzo_valor: form.value.esfuerzo_valor,
            nota_user: form.value.nota_user,
        });
    } catch (e) {
        console.warn('Error guardando serie en offline/API:', e);
    }

    // 4. Iniciar temporizador de descanso
    const descansoSegundos = Math.max(15, Math.round((Number(ej.descanso_min) || 1.5) * 60));
    restTimer.start(descansoSegundos, ej.nombre);
};

const deshacer = () => {
    const last = store.undoLastSet();
    if (last) {
        form.value.peso = last.peso;
        form.value.reps = last.reps;
        form.value.tipo_serie = last.tipo_serie;
        form.value.esfuerzo_tipo = last.esfuerzo_tipo || 'rir';
        form.value.esfuerzo_valor = last.esfuerzo_valor;
    }
};

const handleFinalizar = () => {
    emit('finish');
};

onMounted(() => {
    if (props.open) {
        wakeLock.requestWakeLock();
    }
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            wakeLock.requestWakeLock();
        } else {
            wakeLock.releaseWakeLock();
        }
    }
);

onUnmounted(() => {
    wakeLock.releaseWakeLock();
});
</script>
