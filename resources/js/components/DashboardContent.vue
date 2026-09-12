<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 pb-28 md:py-8 md:pb-8">
        <SyncBadge :pending="offline.pendingCount.value" :syncing="offline.isSyncing.value" />
        <Breadcrumbs
            :items="[{ label: 'Inicio' }]"
            class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto"
        />

        <!-- Indicador pull-to-refresh (mobile) -->
        <div
            v-show="pullOffset > 4 || isRefreshing"
            :style="{ height: pullOffset + 'px' }"
            class="md:hidden flex items-center justify-center overflow-hidden transition-[height] duration-150 max-w-6xl mx-auto"
            aria-live="polite"
            role="status"
        >
            <div
                class="flex flex-col items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400"
            >
                <svg
                    class="w-5 h-5 animate-spin text-indigo-600"
                    v-if="isRefreshing"
                    fill="none"
                    stroke="currentColor"
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
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    ></path>
                </svg>
                <svg
                    class="w-5 h-5 text-indigo-600"
                    v-else
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                    />
                </svg>
                <span>{{ isRefreshing ? 'Actualizando…' : 'Deslizá hacia abajo' }}</span>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <template v-if="rutinaStore.seleccionada">
                <div data-tour="home-hero" class="mb-4">
                    <HomeHero />
                </div>

                <!-- Banner de Sesión de Entrenamiento Activa -->
                <div
                    v-if="session.isActive"
                    class="mb-4 bg-gradient-to-r from-emerald-900/90 via-teal-900/90 to-indigo-900/90 border border-emerald-500/40 rounded-2xl p-4 text-white shadow-xl flex flex-wrap items-center justify-between gap-3 animate-fade-in"
                >
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-3.5 w-3.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"
                            ></span>
                        </span>
                        <div>
                            <p class="text-sm font-black tracking-tight flex items-center gap-2">
                                <span>Entrenamiento en curso · {{ formattedActiveTime }}</span>
                                <span
                                    v-if="session.isPaused"
                                    class="text-[10px] font-bold bg-amber-500/30 text-amber-300 px-1.5 py-0.5 rounded"
                                    >Pausado</span
                                >
                            </p>
                            <p class="text-xs text-emerald-200/80">
                                {{ session.currentEjercicio?.nombre || 'Sesión iniciada' }} ·
                                {{ session.totalSeriesCompletadas }}/{{
                                    session.totalSeriesObjetivo
                                }}
                                series
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="abrirModoEntrenamiento"
                            class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-bold shadow-md cursor-pointer transition-all active:scale-95"
                        >
                            ⚡ Continuar
                        </button>
                        <button
                            type="button"
                            @click="descartarSesion"
                            class="px-3 py-2 rounded-xl bg-gray-800/80 hover:bg-gray-700 text-gray-400 hover:text-rose-300 text-xs font-semibold cursor-pointer transition-all"
                        >
                            Descartar
                        </button>
                    </div>
                </div>

                <!-- CTA Card Modo Entrenamiento Activo -->
                <div
                    v-else
                    class="mb-4 bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
                >
                    <div>
                        <h3
                            class="text-sm sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                        >
                            <span>⚡ Modo Entrenamiento Activo</span>
                            <span
                                class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300"
                                >Focus Mode</span
                            >
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Seguimiento guiado serie por serie, botones táctiles aumentados y
                            descanso automático.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="abrirModoEntrenamiento"
                        class="shrink-0 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                    >
                        <span>▶ Iniciar Sesión de Hoy</span>
                    </button>
                </div>

                <div data-tour="rutina-header">
                    <DashboardRutinaHeader
                        :nivel="rutinaStore.seleccionada.nivel"
                        :dias="rutinaStore.seleccionada.dias"
                        :dia-actual="diaActual"
                        @cambiar="cambiarRutina"
                    />
                </div>

                <div data-tour="stats">
                    <DashboardStats
                        :series-totales="seriesTotales"
                        :series-completadas="seriesCompletadas"
                        :series-pendientes="seriesPendientes"
                        :peso-registrado="pesoRegistrado"
                        :peso-promedio="pesoPromedio"
                        :reps-registradas="repsRegistradas"
                        :progreso-dia="progresoDia"
                    />
                </div>

                <!-- Day selector -->
                <div class="mb-6" data-tour="day-selector">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button
                            v-for="dia in todosLosDias"
                            :key="dia"
                            @click="cambiarDia(dia)"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-all',
                                diaActual === dia
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700',
                            ]"
                        >
                            {{ dia }}
                        </button>
                    </div>
                </div>

                <div data-tour="series-list">
                    <DashboardSeriesList
                        :filas-serie="filasSerie"
                        :dia-index="diaIndex"
                        :texto-boton-siguiente="textoBotonSiguiente"
                        :boton-siguiente-class="botonSiguienteClass"
                        @guardar="guardarFila"
                        @dia-anterior="diaAnterior"
                        @guardar-sesion="guardarSesion"
                        @siguiente-dia="siguienteDia"
                    />
                </div>

                <!-- Botón "Guardar sesión" fijo abajo en mobile -->
                <div
                    class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200/80 dark:border-gray-700 bg-gray-950/95 backdrop-blur md:hidden pb-[env(safe-area-inset-bottom)]"
                >
                    <div class="max-w-6xl mx-auto px-4 py-3">
                        <button
                            @click="guardarSesion"
                            class="w-full rounded-xl bg-slate-700 hover:bg-slate-800 text-white px-4 py-3 text-sm font-semibold shadow-lg shadow-slate-950/30"
                        >
                            Guardar sesión
                        </button>
                    </div>
                </div>
            </template>

            <EmptyStateIllustrated
                v-else
                variant="no-rutinas"
                title="No hay rutina seleccionada"
                description="Elegí una rutina para empezar a registrar tus series y llevar el control de tu progreso."
                cta-text="Seleccionar Rutina"
                cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
                @cta="window.location.href = '/rutinas'"
            />

            <DashboardHeatmap :historial="historialRutina" class="mt-6" data-tour="heatmap" />

            <!-- Gráfico semanal de peso (Chart.js) -->
            <DashboardWeeklyChart
                :historial="historialRutina"
                class="mt-6"
                data-tour="weekly-chart"
            />
        </div>

        <!-- Onboarding tour (auto-start en primera visita) -->
        <OnboardingTour :tour="onboarding" />

        <!-- Modales de Modo Entrenamiento Activo (Nivel 4) -->
        <ActiveWorkoutModal
            :open="showActiveWorkoutModal"
            @minimize="showActiveWorkoutModal = false"
            @finish="onFinishActiveWorkout"
        />

        <WorkoutSummaryModal
            :open="showWorkoutSummaryModal"
            @cancel="
                showWorkoutSummaryModal = false;
                showActiveWorkoutModal = true;
            "
            @saved="onWorkoutSummarySaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useOnboarding } from '../composables/useOnboarding';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import SyncBadge from './training/SyncBadge.vue';
import EmptyState from './EmptyState.vue'; // legacy, reemplazado por EmptyStateIllustrated gradualmente
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';
import OnboardingTour from './OnboardingTour.vue';
import confetti from 'canvas-confetti';
import Breadcrumbs from './Breadcrumbs.vue';
import HomeHero from './HomeHero.vue';
import DashboardRutinaHeader from './dashboard/DashboardRutinaHeader.vue';
import DashboardStats from './dashboard/DashboardStats.vue';
import DashboardSeriesList from './dashboard/DashboardSeriesList.vue';
import DashboardHeatmap from './dashboard/DashboardHeatmap.vue';
import DashboardWeeklyChart from './dashboard/DashboardWeeklyChart.vue';
import { useRestTimerStore } from '../stores/restTimer';
import ActiveWorkoutModal from './training/ActiveWorkoutModal.vue';
import WorkoutSummaryModal from './training/WorkoutSummaryModal.vue';

const rutinaStore = useRutinaStore();

// === Modo entrenamiento (Oleada 1) ===
// - useOfflineSeries: registra series online/offline con sync automatico
// - useWakeLock: mantiene la pantalla encendida durante el entrenamiento
// - useTrainingSessionStore: persistencia de la sesion actual (recovery al cerrar)
// - SyncBadge: indicador visual de estado
const offline = useOfflineSeries();
const wake = useWakeLock();
const session = useTrainingSessionStore();

onMounted(() => {
    // Solo pedimos wake lock si el user tiene una sesion activa (entrenando).
    if (session.isActive && wake.supported) {
        wake.request();
    }
});

// Onboarding tour: 5 steps por el dashboard, se muestra la primera vez
const onboarding = useOnboarding('dashboard-tour', [
    {
        selector: '[data-tour="rutina-header"]',
        title: 'Tu rutina actual',
        body: 'Acá ves el nombre, nivel y días de tu rutina activa. Tocá el nombre para cambiarla.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="stats"]',
        title: 'Stats del día',
        body: 'Tu progreso en tiempo real: series completadas vs pendientes, peso levantado, reps.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="day-selector"]',
        title: 'Selector de día',
        body: 'Cambiá entre los días de tu rutina. Cada día tiene ejercicios diferentes.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="series-list"]',
        title: 'Tus series',
        body: 'Registrá cada serie acá. Marcá como completada cuando termines, y guardá al final.',
        position: 'top',
    },
    {
        selector: '[data-tour="heatmap"]',
        title: 'Tu constancia',
        body: 'Acá ves todos los días que entrenaste. ¡La constancia es la clave!',
        position: 'top',
    },
]);
const toast = useToast();
const showSuccess = (m) => toast.success(m);
const showError = (m) => toast.error(m);
const showWarning = (m) => toast.warning(m);

const filasSerie = ref([]);
const historialRutina = ref([]);
const diaActual = ref('Día 1');
const todosLosDias = ref([]);

const showActiveWorkoutModal = ref(false);
const showWorkoutSummaryModal = ref(false);

const formattedActiveTime = computed(() => {
    const s = session.elapsed;
    const hrs = Math.floor(s / 3600);
    const mins = Math.floor((s % 3600) / 60);
    const secs = s % 60;
    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const abrirModoEntrenamiento = async () => {
    if (!session.isActive) {
        // Agrupar filasSerie por ejercicio único
        const exercisesMap = new Map();
        filasSerie.value.forEach((f) => {
            if (!exercisesMap.has(f.ejercicio_nombre)) {
                exercisesMap.set(f.ejercicio_nombre, {
                    nombre: f.ejercicio_nombre,
                    series_objetivo: 0,
                    reps_min: f.reps_min,
                    reps_max: f.reps_max,
                    descanso_min: f.descanso_min,
                    superserie_grupo: f.superserie_grupo,
                });
            }
            exercisesMap.get(f.ejercicio_nombre).series_objetivo += 1;
        });

        const ejerciciosList = Array.from(exercisesMap.values());

        session.start({
            rutina_nombre: getRutinaNombre(),
            dia: diaActual.value,
            ejercicios: ejerciciosList,
        });

        try {
            await axios.post('/api/sesiones/iniciar', {
                uuid: session.session.id,
                rutina_nombre: getRutinaNombre(),
                dia: diaActual.value,
                started_at: session.session.startedAt,
                series_totales: filasSerie.value.length,
            });
        } catch (e) {
            console.warn('Sesión iniciada offline:', e);
        }
    }
    showActiveWorkoutModal.value = true;
};

const onFinishActiveWorkout = () => {
    showActiveWorkoutModal.value = false;
    showWorkoutSummaryModal.value = true;
};

const onWorkoutSummarySaved = async (resumen) => {
    showWorkoutSummaryModal.value = false;
    showSuccess('🎉 ¡Sesión guardada exitosamente!');
    await fetchHistorialRutina();
    await fetchRutinasDelDia();
};

const descartarSesion = async () => {
    const ok = await toast.confirm(
        '¿Seguro que querés descartar la sesión actual? Se perderá el tiempo y progreso activo de esta sesión.',
        { confirmLabel: 'Sí, descartar', cancelLabel: 'Continuar' }
    );
    if (!ok) return;

    const uuid = session.session.id;
    session.discard();
    try {
        if (uuid) {
            await axios.delete(`/api/sesiones/${uuid}`);
        }
    } catch (e) {
        // Silencioso si no hay red
    }
    showWarning('Sesión descartada.');
};

const diaIndex = computed(() => todosLosDias.value.indexOf(diaActual.value));
const esUltimoDia = computed(() => diaIndex.value === todosLosDias.value.length - 1);

const seriesTotales = computed(() => filasSerie.value.length);
const seriesCompletadas = computed(() => filasSerie.value.filter((f) => f.completado).length);
const seriesPendientes = computed(() => Math.max(seriesTotales.value - seriesCompletadas.value, 0));

const pesoRegistrado = computed(() =>
    filasSerie.value
        .reduce((t, f) => {
            const p = Number(f.peso);
            return Number.isFinite(p) ? t + p : t;
        }, 0)
        .toFixed(1)
);

const pesoPromedio = computed(() => {
    const pesos = filasSerie.value
        .map((f) => Number(f.peso))
        .filter((p) => Number.isFinite(p) && p > 0);
    if (!pesos.length) return '0.0';
    return (pesos.reduce((t, p) => t + p, 0) / pesos.length).toFixed(1);
});

const repsRegistradas = computed(() =>
    filasSerie.value.reduce((t, f) => {
        const r = Number(f.reps_realizadas);
        return Number.isFinite(r) ? t + r : t;
    }, 0)
);

const progresoDia = computed(() =>
    seriesTotales.value ? Math.round((seriesCompletadas.value / seriesTotales.value) * 100) : 0
);

const textoBotonSiguiente = computed(() => {
    if (esUltimoDia.value) {
        return seriesCompletadas.value === seriesTotales.value
            ? '🎉 Finalizar Rutina'
            : '⚠️ Terminar e Iniciar';
    }
    return seriesPendientes.value > 0 ? '⚠️ Siguiente Día →' : 'Siguiente Día →';
});

const botonSiguienteClass = computed(() => {
    if (esUltimoDia.value && seriesPendientes.value > 0) return 'bg-orange-500 hover:bg-orange-600';
    if (!esUltimoDia.value && seriesPendientes.value > 0)
        return 'bg-yellow-500 hover:bg-yellow-600';
    return 'bg-green-600 hover:bg-green-700';
});

const getRutinaNombre = () => rutinaStore.seleccionada?.nivel || '';

const triggerConfetti = () => {
    const duration = 3000;
    const end = Date.now() + duration;
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
    (function frame() {
        confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors });
        confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
};

const fetchUserRutina = async () => {
    try {
        const response = await axios.get('/api/user-rutina');
        if (response.data) {
            const nivelCompleto = `${response.data.nivel} ${response.data.modalidad}`;
            rutinaStore.seleccionar(nivelCompleto, 'Todos los días');
            diaActual.value = response.data.dia_actual || 'Día 1';
        } else {
            rutinaStore.limpiar();
        }
    } catch (error) {
        console.error('Error:', error);
    }
};

const fetchHistorialRutina = async () => {
    if (!rutinaStore.seleccionada) {
        historialRutina.value = [];
        return;
    }
    try {
        const response = await axios.get('/api/historial', {
            params: { rutina_nombre: getRutinaNombre() },
        });
        historialRutina.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Error:', error);
        historialRutina.value = [];
    }
};

const construirFilasSerie = (rutinasDelDia) => {
    const registros = new Map(
        historialRutina.value
            .filter((r) => r.dia === diaActual.value)
            .map((r) => [`${r.ejercicio_nombre}-${r.series_numero}`, r])
    );

    const filteredRutinas = rutinasDelDia.filter((r) => r.dia === diaActual.value);
    const blocks = [];
    const processedSuperseries = new Set();

    filteredRutinas.forEach((rutina) => {
        if (rutina.superserie_grupo) {
            if (!processedSuperseries.has(rutina.superserie_grupo)) {
                processedSuperseries.add(rutina.superserie_grupo);
                const supersetExercises = filteredRutinas.filter(
                    (r) => r.superserie_grupo === rutina.superserie_grupo
                );
                blocks.push({
                    isSuperset: true,
                    grupo: rutina.superserie_grupo,
                    exercises: supersetExercises,
                });
            }
        } else {
            blocks.push({ isSuperset: false, exercise: rutina });
        }
    });

    const allSets = [];
    blocks.forEach((block) => {
        if (!block.isSuperset) {
            const rutina = block.exercise;
            const totalSeries = Number(rutina.series) || 1;
            for (let index = 0; index < totalSeries; index++) {
                const serieNumero = index + 1;
                const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                allSets.push({
                    uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
                    rutina_nombre: getRutinaNombre(),
                    dia: diaActual.value,
                    ejercicio_nombre: rutina.ejercicio_nombre,
                    series_numero: serieNumero,
                    series_completadas:
                        registro?.series_completadas ?? (registro?.completado ? 1 : 0),
                    reps_min: rutina.reps_min,
                    reps_max: rutina.reps_max,
                    reps_realizadas: registro?.reps_realizadas ?? null,
                    descanso_min: rutina.descanso_min,
                    peso: registro?.peso ?? null,
                    completado: registro?.completado ?? false,
                    superserie_grupo: null,
                    // Fase 3
                    esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                    esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                    notas: rutina.notas || null,
                });
            }
        } else {
            const exercises = block.exercises;
            const maxSeries = Math.max(...exercises.map((r) => Number(r.series) || 1));
            for (let index = 0; index < maxSeries; index++) {
                const serieNumero = index + 1;
                exercises.forEach((rutina) => {
                    const totalSeries = Number(rutina.series) || 1;
                    if (serieNumero <= totalSeries) {
                        const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                        allSets.push({
                            uid: `${rutina.id}-${diaActual.value}-${serieNumero}`,
                            rutina_nombre: getRutinaNombre(),
                            dia: diaActual.value,
                            ejercicio_nombre: rutina.ejercicio_nombre,
                            series_numero: serieNumero,
                            series_completadas:
                                registro?.series_completadas ?? (registro?.completado ? 1 : 0),
                            reps_min: rutina.reps_min,
                            reps_max: rutina.reps_max,
                            reps_realizadas: registro?.reps_realizadas ?? null,
                            descanso_min: rutina.descanso_min,
                            peso: registro?.peso ?? null,
                            completado: registro?.completado ?? false,
                            superserie_grupo: block.grupo,
                            // Fase 3
                            esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                            esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                            notas: rutina.notas || null,
                        });
                    }
                });
            }
        }
    });

    filasSerie.value = allSets;
};

const fetchRutinasDelDia = async () => {
    if (!rutinaStore.seleccionada) return;
    try {
        const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
        const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);
        const response = await axios.get('/api/rutinas', { params: { nivel, modalidad } });
        const diasUnicos = [...new Set(response.data.map((r) => r.dia))].sort();
        todosLosDias.value = diasUnicos;
        await fetchHistorialRutina();
        construirFilasSerie(response.data);
    } catch (error) {
        console.error('Error:', error);
    }
};

const guardarFila = async (fila, silencioso = false) => {
    try {
        // === Modo offline (Oleada 1) ===
        // Antes: axios.post directo, fallaba si no habia red.
        // Ahora: usa useOfflineSeries, que encola en IndexedDB si no hay red y
        // sincroniza automaticamente al volver online.
        const result = await offline.recordSet({
            fecha: new Date().toISOString().split('T')[0],
            rutina_nombre: fila.rutina_nombre,
            dia: fila.dia,
            ejercicio_nombre: fila.ejercicio_nombre,
            series_numero: fila.series_numero,
            series_completadas: fila.completado ? 1 : 0,
            reps_min: fila.reps_min,
            reps_max: fila.reps_max,
            reps_realizadas:
                fila.reps_realizadas === '' || fila.reps_realizadas == null
                    ? null
                    : Number(fila.reps_realizadas),
            descanso_min: fila.descanso_min,
            peso: fila.peso === '' || fila.peso == null ? null : Number(fila.peso),
            completado: fila.completado,
            superserie_grupo: fila.superserie_grupo,
            // Fase 3: esfuerzo RIR/RPE
            esfuerzo_tipo: fila.esfuerzo_tipo || null,
            esfuerzo_valor: fila.esfuerzo_valor ?? null,
        });
        if (result.status === 'queued' && !silencioso) {
            showError?.(
                'Sin conexion: guardado en este dispositivo, se sincroniza al volver online.'
            );
        } else if (result.status === 'lost' && !silencioso) {
            showError?.(
                'No se pudo guardar: estas sin conexion y tu navegador no soporta guardado offline.'
            );
        }
        if (!silencioso && fila.completado && deberiaIniciarTemporizador(fila)) {
            iniciarTemporizador(fila);
        }
    } catch (error) {
        if (!silencioso) {
            console.error('Error:', error);
            showError('No se pudo guardar la serie. Intenta de nuevo.');
        }
        throw error;
    }
};

const guardarProgreso = async () => {
    try {
        // Esta funcion solo persiste el `dia_actual` (cambiaste de dia en el
        // dashboard). NO re-selecciona la rutina, asi que usamos el endpoint
        // dedicado en vez de /api/user-rutina (que pide rutina_id y source
        // of truth es la FK, ver D1 migracion 2026_08_17).
        await axios.post('/api/user-rutina/dia', {
            dia_actual: diaActual.value,
        });
    } catch (error) {
        console.error('Error:', error);
    }
};

const cambiarDia = async (dia) => {
    diaActual.value = dia;
    await guardarProgreso();
    await fetchRutinasDelDia();
};

const siguienteDia = async () => {
    if (seriesPendientes.value > 0) {
        const ok = await toast.confirm(
            `Tenés ${seriesPendientes.value} series sin completar. ¿Querés avanzar de todas formas?`,
            { confirmLabel: 'Avanzar', cancelLabel: 'Seguir acá' }
        );
        if (!ok) return;
    }
    if (diaIndex.value < todosLosDias.value.length - 1) {
        diaActual.value = todosLosDias.value[diaIndex.value + 1];
        await guardarProgreso();
        fetchRutinasDelDia();
    } else {
        await finalizarRutina();
    }
};

const diaAnterior = async () => {
    if (diaIndex.value > 0) {
        diaActual.value = todosLosDias.value[diaIndex.value - 1];
        await guardarProgreso();
        fetchRutinasDelDia();
    }
};

const cambiarRutina = async () => {
    const ok = await toast.confirm('¿Estás seguro de cambiar de rutina?', {
        confirmLabel: 'Sí, cambiar',
        cancelLabel: 'Cancelar',
    });
    if (!ok) return;
    rutinaStore.limpiar();
    filasSerie.value = [];
    historialRutina.value = [];
    diaActual.value = 'Día 1';
    window.location.href = '/rutinas';
};

const guardarSesion = async () => {
    if (!filasSerie.value.length) {
        showWarning('No hay ejercicios para guardar.');
        return;
    }
    try {
        await Promise.all(filasSerie.value.map((f) => guardarFila(f, true)));
        showSuccess('✓ Sesión guardada correctamente');
    } catch (error) {
        console.error('Error:', error);
        showError('No se pudo guardar la sesión. Intenta de nuevo.');
    }
};

const finalizarRutina = async () => {
    if (!rutinaStore.seleccionada) return;
    try {
        await Promise.all(filasSerie.value.map((f) => guardarFila(f, true)));
        const nivel = rutinaStore.seleccionada.nivel.split(' ')[0];
        const modalidad = rutinaStore.seleccionada.nivel.substring(nivel.length + 1);
        const response = await axios.post('/api/historial/finalizar-rutina', { nivel, modalidad });
        diaActual.value = response.data.dia_actual || 'Día 1';
        await guardarProgreso();
        await fetchRutinasDelDia();
        triggerConfetti();
        showSuccess('🎉 ¡Felicidades! Has completado la rutina. Se reinició al Día 1.');
    } catch (error) {
        console.error('Error:', error);
        showError('No se pudo finalizar la rutina. Intenta de nuevo.');
    }
};

onMounted(async () => {
    rutinaStore.hidratar();
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        fetchRutinasDelDia();
    }

    // Onboarding tour: solo se muestra la primera vez (localStorage)
    if (rutinaStore.seleccionada && onboarding.shouldShow()) {
        // Pequeño delay para que el DOM termine de renderizar
        setTimeout(() => onboarding.start(), 600);
    }
});

watch(
    () => rutinaStore.seleccionada,
    (newVal) => {
        if (newVal) {
            fetchHistorialRutina();
            fetchRutinasDelDia();
        }
    }
);

// === Mejora 1.9: Pull-to-refresh ===
const refreshDashboard = async () => {
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        await fetchRutinasDelDia();
    }
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, refreshDashboard);

// === Timer de descanso global (Pinia useRestTimerStore) ===
const restTimer = useRestTimerStore();

const deberiaIniciarTemporizador = (fila) => {
    if (!fila.superserie_grupo) return true;
    const setsEnRonda = filasSerie.value.filter(
        (f) =>
            f.superserie_grupo === fila.superserie_grupo && f.series_numero === fila.series_numero
    );
    return setsEnRonda.every((f) => f.completado);
};

const iniciarTemporizador = (fila) => {
    const descansoMinutos = parseFloat(fila.descanso_min) || 1.5;
    const totalSegundos = Math.round(descansoMinutos * 60);
    const nombreLabel = fila.superserie_grupo
        ? `Descanso Superserie ${fila.superserie_grupo}`
        : fila.ejercicio_nombre;
    restTimer.start({
        exerciseName: nombreLabel,
        durationSeconds: totalSegundos,
    });
};
</script>
