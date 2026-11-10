<script setup>
import { ref, shallowRef, computed, watch, onMounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useOnboarding } from '../composables/useOnboarding';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';
import { hoyLocal } from '@/utils/fecha';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import SyncBadge from './training/SyncBadge.vue';
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';
import OnboardingTour from './OnboardingTour.vue';
import confetti from 'canvas-confetti';
import { useRestTimerStore } from '../stores/restTimer';
import ActiveWorkoutModal from './training/ActiveWorkoutModal.vue';
import WorkoutSummaryModal from './training/WorkoutSummaryModal.vue';
import DashboardActiveSessionBanner from './dashboard/DashboardActiveSessionBanner.vue';
import DashboardQuickActions from './dashboard/DashboardQuickActions.vue';
import DashboardRoutineHeroCard from './dashboard/DashboardRoutineHeroCard.vue';
import DashboardDaySelector from './dashboard/DashboardDaySelector.vue';
import DashboardKeyMetrics from './dashboard/DashboardKeyMetrics.vue';
import DashboardExerciseList from './dashboard/DashboardExerciseList.vue';
import DashboardTelemetryCharts from './dashboard/DashboardTelemetryCharts.vue';
import DashboardSessionActions from './dashboard/DashboardSessionActions.vue';
import DashboardFooter from './dashboard/DashboardFooter.vue';
import DashboardDaySummarySheet from './dashboard/DashboardDaySummarySheet.vue';

const rutinaStore = useRutinaStore();

// === Modo entrenamiento ===
const offline = useOfflineSeries();
const wake = useWakeLock();
const session = useTrainingSessionStore();

// Series que el server rechazó de forma permanente: no se reintentan solas,
// el usuario decide.
const reintentarSeriesFallidas = async () => {
    for (const fallida of [...offline.failedSeries.value]) {
        await offline.retryFailed(fallida.client_id);
    }
};

const descartarSeriesFallidas = async () => {
    for (const fallida of [...offline.failedSeries.value]) {
        await offline.dismissFailed(fallida.client_id);
    }
};

onMounted(() => {
    if (session.isActive && wake.supported) {
        wake.requestWakeLock();
    }
});

// Onboarding tour: 5 steps por el dashboard, se muestra la primera vez
const onboarding = useOnboarding('dashboard-tour', [
    {
        selector: '[data-tour="rutina-header"]',
        title: 'Tu rutina actual',
        body: 'Acá ves el nombre, nivel y días de tu rutina activa. Tocá el botón para comenzar a entrenar.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="day-selector"]',
        title: 'Selector de día',
        body: 'Cambiá entre los días de tu rutina. Cada día tiene ejercicios diferentes.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="stats"]',
        title: 'Stats del día',
        body: 'Tu progreso en tiempo real: series completadas vs pendientes, peso levantado, reps.',
        position: 'bottom',
    },
    {
        selector: '[data-tour="series-list"]',
        title: 'Tus series',
        body: 'Revisá el detalle de cada serie del día y su progreso.',
        position: 'top',
    },
    {
        selector: '[data-tour="telemetry-charts"]',
        title: 'Telemetría y PRs',
        body: 'Analizá la evolución de tus cargas máximas y récords diarios.',
        position: 'top',
    },
]);

const toast = useToast();
const showSuccess = (m) => toast.success(m);
const showError = (m) => toast.error(m);
const showWarning = (m) => toast.warning(m);

const filasSerie = ref([]);
const bloquesDelDia = ref([]);
const historialRutina = ref([]);
const diaActual = shallowRef('Día 1');
const cicloInicio = shallowRef(null);
const todosLosDias = ref([]);

const showActiveWorkoutModal = shallowRef(false);
const showWorkoutSummaryModal = shallowRef(false);
const isInitialLoading = shallowRef(true);
const expandirTodos = shallowRef(false);
const activeInlineSet = ref(null);

const toggleInlineSet = (set) => {
    if (activeInlineSet.value === set) {
        activeInlineSet.value = null;
        return;
    }
    if ((set.peso === null || set.peso === undefined) && set.previous_record?.peso != null) {
        set.peso = Number(set.previous_record.peso);
    }
    if (
        (set.reps_realizadas === null || set.reps_realizadas === undefined) &&
        (set.previous_record?.reps_realizadas != null || set.reps_min != null)
    ) {
        set.reps_realizadas = Number(set.previous_record?.reps_realizadas ?? set.reps_min ?? 8);
    }
    activeInlineSet.value = set;
};

const stepInlinePeso = (set, delta) => {
    const cur = parseFloat(set.peso ?? set.previous_record?.peso ?? 0) || 0;
    set.peso = Math.max(0, Math.round((cur + delta) * 100) / 100);
};

const stepInlineReps = (set, delta) => {
    const cur = parseInt(set.reps_realizadas ?? set.previous_record?.reps_realizadas ?? set.reps_min ?? 0, 10) || 0;
    set.reps_realizadas = Math.max(0, cur + delta);
};

/**
 * Escritura de un campo del set abierto desde el editor inline.
 * El set lo posee el padre, así que el subcomponente nos delega la mutación.
 */
const updateInlineField = (set, campo, valor) => {
    if (!set) return;
    set[campo] = valor;
};

const copyInlineFromPrev = (set) => {
    const hist = valorHistoricoSet(set);
    if (!hist) return;
    if (hist.peso !== null && hist.peso !== undefined) set.peso = Number(hist.peso);
    if (hist.reps !== null && hist.reps !== undefined) set.reps_realizadas = Number(hist.reps);
};

const completeInlineSet = async (set, ejSets) => {
    if (set.peso === null || set.peso === undefined || set.peso === '') {
        set.peso = Number(set.previous_record?.peso ?? 0);
    }
    if (set.reps_realizadas === null || set.reps_realizadas === undefined || set.reps_realizadas === '') {
        set.reps_realizadas = Number(set.previous_record?.reps_realizadas ?? set.reps_min ?? 8);
    }
    set.completado = true;
    await guardarFila(set, false);
    const nextPending = (ejSets || []).find((s) => !s.completado && s !== set);
    activeInlineSet.value = nextPending || null;
};

// === Header / Hero Computeds ===
const userFirstName = computed(() => {
    const n = (window.__user?.name || '').trim();
    if (!n) return 'Oscar';
    return n.split(' ')[0];
});

const diaSemanaEs = computed(() => {
    if (dashboardToday.value?.hoy?.dia_semana_es) {
        return dashboardToday.value.hoy.dia_semana_es.toUpperCase();
    }
    const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
    return dias[new Date().getDay()];
});

const fechaCorta = computed(() => {
    const d = dashboardToday.value?.hoy?.fecha
        ? new Date(dashboardToday.value.hoy.fecha + 'T00:00:00')
        : new Date();
    const meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    return `${d.getDate()} ${meses[d.getMonth()]}`;
});

const anioActual = computed(() => {
    const d = dashboardToday.value?.hoy?.fecha
        ? new Date(dashboardToday.value.hoy.fecha + 'T00:00:00')
        : new Date();
    return d.getFullYear();
});

const nombreRutina = computed(() => {
    const r = dashboardToday.value?.rutina?.nombre;
    return r || rutinaStore.seleccionada?.nivel || 'Personalizada';
});

const semanaCicloLabel = computed(() => {
    if (!cicloInicio.value) return 'Semana 1';
    const inicio = new Date(cicloInicio.value + 'T00:00:00');
    const hoy = new Date();
    const diffDias = Math.max(0, Math.floor((hoy.getTime() - inicio.getTime()) / 86400000));
    const semana = Math.min(Math.floor(diffDias / 7) + 1, 12);
    return `Semana ${semana}`;
});

// === Stats del hero ===
const stats = ref({
    streak: 0,
    longestStreak: 0,
    totalSets30d: 0,
    trend30d: 0,
});

const dashboardToday = ref(null);

const cargarDashboardToday = async () => {
    try {
        const r = await axios.get('/api/dashboard/today');
        dashboardToday.value = r.data;
        stats.value.streak = r.data?.stats?.streak ?? 0;
        stats.value.totalSets30d = r.data?.stats?.total_sets_30d ?? 0;
    } catch (e) {
        console.warn('dashboard/today falló, fallback a /api/stats/resumen', e);
    }
    try {
        const r2 = await axios.get('/api/stats/resumen');
        stats.value.longestStreak = r2.data?.longest_streak ?? 0;
        stats.value.streak = stats.value.streak || r2.data?.current_streak || 0;
    } catch (e) {
        console.warn('stats/resumen no se pudo cargar', e);
    }
};

const cargarTrend30d = () => {
    const h = historialRutina.value || [];
    if (!h.length) return;
    const hoy = new Date();
    const hace30 = new Date(hoy.getTime() - 30 * 86400000);
    const hace60 = new Date(hoy.getTime() - 60 * 86400000);
    const en30 = h.filter((r) => {
        const f = new Date(r.fecha);
        return f >= hace30 && f <= hoy && r.completado;
    }).length;
    const en30Prev = h.filter((r) => {
        const f = new Date(r.fecha);
        return f >= hace60 && f < hace30 && r.completado;
    }).length;
    if (en30Prev === 0) {
        stats.value.trend30d = en30 > 0 ? 100 : 0;
    } else {
        stats.value.trend30d = Math.round(((en30 - en30Prev) / en30Prev) * 100);
    }
};

// === "Último entreno" labels ===
const daysSinceLastWorkout = computed(() => dashboardToday.value?.stats?.days_since_last_workout);

const ultimoEntrenoLabel = computed(() => {
    const d = daysSinceLastWorkout.value;
    if (d === null || d === undefined) return '—';
    if (d === 0) return 'Hoy';
    if (d === 1) return 'Ayer';
    if (d >= 2 && d < 7) return `Hace ${d}d`;
    if (d >= 7 && d < 30) return `Hace ${Math.floor(d / 7)}sem`;
    return `Hace ${Math.floor(d / 30)}m`;
});

const ultimoEntrenoSub = computed(() => {
    const d = daysSinceLastWorkout.value;
    if (d === null || d === undefined) return 'Sin registros aún';
    if (d === 0) return 'Entrenaste hoy';
    if (d === 1) return 'Día de descanso OK';
    if (d >= 2 && d <= 3) return 'Volvé a la carga';
    if (d >= 4 && d <= 6) return 'Pasaron varios días';
    return 'Hacés rato que no entrás';
});

const ultimoEntrenoAlerta = computed(() => {
    const d = daysSinceLastWorkout.value;
    return d === null || d === undefined || d >= 4;
});

// === Día / grupo muscular ===
const diaIndex = computed(() => todosLosDias.value.indexOf(diaActual.value));
const esUltimoDia = computed(() => diaIndex.value === todosLosDias.value.length - 1);

const diaActualGrupo = computed(() => {
    const m = String(diaActual.value).match(/(?:\(|:)\s*([^):]+)\s*\)?/);
    if (m) return m[1].trim();
    const i = diaIndex.value;
    const fallback = ['Torso', 'Pierna', 'Full Body', 'Torso', 'Pierna'];
    return fallback[i] || '';
});

const diaGrupoPara = (dia) => {
    const m = String(dia).match(/(?:\(|:)\s*([^):]+)\s*\)?/);
    if (m) return m[1].trim();
    const i = todosLosDias.value.indexOf(dia);
    const fallback = ['Torso', 'Pierna', 'Full Body', 'Torso', 'Pierna'];
    return fallback[i] || '';
};

const formatDiaTabLabel = (dia) => {
    const str = String(dia);
    if (str.includes('(') || str.includes(':')) return str;
    const grupo = diaGrupoPara(dia);
    return grupo ? `${str} (${grupo})` : str;
};

const tituloHeroDia = computed(() => {
    const str = String(diaActual.value);
    const grupo = diaActualGrupo.value;
    if (!grupo) return str;
    if (str.includes('(')) {
        return `${str}: ${grupo}`;
    }
    if (str.includes(':')) {
        return str;
    }
    return `${str} (${grupo}): ${grupo}`;
});

// === Datos históricos (ciclo anterior) ===
const valorHistoricoSet = (set) => {
    const prev = set?.previous_record;
    if (!prev) return null;
    const reps = prev.reps_realizadas ?? null;
    const peso = prev.peso ?? null;
    if (reps === null && peso === null) return null;
    return { reps, peso };
};

const setSinRegistroActual = (set) =>
    (set.reps_realizadas === null || set.reps_realizadas === undefined) &&
    (set.peso === null || set.peso === undefined);

const esSetHistorico = (set) => setSinRegistroActual(set) && !!valorHistoricoSet(set);

const formatPeso2Dec = (val) => {
    const n = Number(val);
    if (!Number.isFinite(n)) return String(val);
    return n.toFixed(2);
};

const inferirMusculo = (nombre) => {
    const n = String(nombre || '').toLowerCase();
    if (n.includes('banca') || n.includes('apertura') || n.includes('pecho') || n.includes('pectoral')) {
        return 'Pectoral Mayor';
    }
    if (n.includes('jalón') || n.includes('remo') || n.includes('dominada') || n.includes('espalda')) {
        return 'Dorsal Ancho';
    }
    if (n.includes('lateral') || n.includes('militar') || n.includes('hombro')) {
        return 'Deltoides Lateral';
    }
    if (n.includes('bíceps') || n.includes('biceps') || n.includes('curl')) {
        return 'Bíceps Braquial';
    }
    if (n.includes('tríceps') || n.includes('triceps') || n.includes('extensión de tríceps')) {
        return 'Tríceps Braquial';
    }
    if (n.includes('prensa') || n.includes('sentadilla') || n.includes('cuádriceps') || n.includes('cuadriceps')) {
        return 'Cuádriceps';
    }
    if (n.includes('femoral') || n.includes('peso muerto') || n.includes('isquio')) {
        return 'Isquiotibiales';
    }
    return '';
};

// === Ejercicios del día (agrupados por nombre) ===
const ejerciciosDelDia = computed(() => {
    const filasDelDia = filasSerie.value.filter((f) => f.dia === diaActual.value);
    const map = new Map();
    filasDelDia.forEach((f) => {
        if (!map.has(f.ejercicio_nombre)) {
            map.set(f.ejercicio_nombre, []);
        }
        map.get(f.ejercicio_nombre).push(f);
    });
    return Array.from(map.entries()).map(([nombre, sets], idx) => {
        sets.sort((a, b) => (a.series_numero || 0) - (b.series_numero || 0));
        const completadas = sets.filter((s) => s.completado).length;
        const first = sets[0];
        const repsLabel =
            first?.reps_min && first?.reps_max ? `${first.reps_min}-${first.reps_max} reps` : '';
        const rawPeso = first?.peso ?? first?.previous_record?.peso ?? null;
        const ultimoPesoLabel =
            rawPeso !== null && rawPeso !== undefined && rawPeso !== ''
                ? `Último: ${formatPeso2Dec(rawPeso)} kg`
                : '';
        return {
            nombre,
            sets,
            completadas,
            total: sets.length,
            estaCompleto: completadas === sets.length && sets.length > 0,
            tieneAlgunaCompletada: completadas > 0,
            repsLabel,
            ultimoPesoLabel,
            musculoLabel: idx === 0 ? inferirMusculo(nombre) : '',
            // Plan de series estructurado (bloques / rest-pause / progresión).
            notas: first?.notas || null,
            prescripcion: first?.prescripcion || null,
        };
    });
});

const ejerciciosCompletadosCount = computed(
    () => ejerciciosDelDia.value.filter((e) => e.estaCompleto).length
);

const formatSetLabel = (set) => {
    if (!set) return '—';
    const hist = valorHistoricoSet(set);
    const tieneActual = !setSinRegistroActual(set);
    const r = tieneActual ? set.reps_realizadas ?? set.reps_min ?? '' : hist?.reps ?? '';
    const p = tieneActual ? set.peso ?? '' : hist?.peso ?? '';
    const esFallo =
        String(set.esfuerzo_tipo || '').toLowerCase() === 'fallo' ||
        String(set?.previous_record?.esfuerzo_tipo || '').toLowerCase() === 'fallo';

    if (r && p !== '' && p !== null && Number(p) > 0) {
        return `${r} × ${formatPeso2Dec(p)} kg`;
    }
    if (p !== '' && p !== null && Number(p) > 0) {
        return `${formatPeso2Dec(p)} kg`;
    }
    if (r) {
        return esFallo ? `${r} reps (Fallo)` : `${r} reps`;
    }
    if (tieneActual) return '—';
    return set.reps_min ? `${set.reps_min} reps` : '—';
};

// === Duración y calorías estimadas ===
const duracionEstimada = computed(() => {
    const totalSets = filasSerie.value.filter((f) => f.dia === diaActual.value).length;
    if (!totalSets) return 0;
    const mins = Math.round((totalSets * (45 + 90)) / 60);
    return Math.max(mins, 10);
});

const caloriasEstimadas = computed(() => {
    const totalSets = seriesTotales.value;
    if (!totalSets) return 0;
    return Math.round(totalSets * 17.42);
});

const estadoUltimoRegistroHero = computed(() => {
    if (seriesCompletadas.value > 0 || daysSinceLastWorkout.value === 0) {
        return 'Último registro completado hoy';
    }
    if (ultimoEntrenoLabel.value && ultimoEntrenoLabel.value !== '—') {
        return `Último registro: ${ultimoEntrenoLabel.value}`;
    }
    return 'Listo para comenzar la sesión';
});

const diasSiguienteLabel = computed(() => {
    const i = diaIndex.value;
    if (i < 0 || i >= todosLosDias.value.length - 1) return '';
    return todosLosDias.value[i + 1];
});

// === Métricas de la sesión ===
const seriesTotales = computed(() => filasSerie.value.length);
const seriesCompletadas = computed(() => filasSerie.value.filter((f) => f.completado).length);
const seriesPendientes = computed(() =>
    Math.max(seriesTotales.value - seriesCompletadas.value, 0)
);

const pesoRegistrado = computed(() =>
    filasSerie.value
        .reduce((t, f) => {
            const p = Number(f.peso ?? f.previous_record?.peso);
            return Number.isFinite(p) ? t + p : t;
        }, 0)
        .toFixed(1)
);

const pesoPromedio = computed(() => {
    const pesos = filasSerie.value
        .map((f) => Number(f.peso ?? f.previous_record?.peso))
        .filter((p) => Number.isFinite(p) && p > 0);
    if (!pesos.length) return '0.0';
    return (pesos.reduce((t, p) => t + p, 0) / pesos.length).toFixed(1);
});

const repsRegistradas = computed(() =>
    filasSerie.value.reduce((t, f) => {
        const r = Number(f.reps_realizadas ?? f.previous_record?.reps_realizadas);
        return Number.isFinite(r) ? t + r : t;
    }, 0)
);

const progresoDia = computed(() =>
    seriesTotales.value ? Math.round((seriesCompletadas.value / seriesTotales.value) * 100) : 0
);

// === Sesión activa y acciones ===
const getRutinaNombre = () => rutinaStore.seleccionada?.nivel || '';

const abrirModoEntrenamiento = async () => {
    if (
        session.isActive &&
        (!session.session.ejercicios || session.session.ejercicios.length === 0)
    ) {
        session.discard();
    }

    if (!session.isActive) {
        if (!filasSerie.value.length && rutinaStore.seleccionada) {
            await fetchRutinasDelDia();
        }

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
                    notas: f.notas || null,
                    prescripcion: f.prescripcion || null,
                });
            }
            exercisesMap.get(f.ejercicio_nombre).series_objetivo += 1;
        });

        const ejerciciosList = Array.from(exercisesMap.values());

        if (ejerciciosList.length > 0) {
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
    }
    showActiveWorkoutModal.value = true;
};

const onFinishActiveWorkout = () => {
    showActiveWorkoutModal.value = false;
    showWorkoutSummaryModal.value = true;
};

const onWorkoutSummarySaved = async () => {
    showWorkoutSummaryModal.value = false;
    showSuccess('🎉 ¡Sesión guardada exitosamente!');
    await fetchHistorialRutina();
    await fetchRutinasDelDia();
    await cargarDashboardToday();
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
    } catch {
        // Silencioso si no hay red
    }
    showWarning('Sesión descartada.');
};

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
            cicloInicio.value = response.data.ciclo_inicio || null;
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

/**
 * Series de un ejercicio de la rutina.
 *  - `series = 0` → bloque informativo (ej. movilidad / calentamiento del
 *    día): no genera series, no cuenta para el total del día y no bloquea
 *    el avance de la sesión.
 *  - `series = null` → rutina vieja sin el dato: caemos en 1 (como antes).
 */
const seriesDeRutina = (rutina) => {
    const s = rutina?.series;
    if (s === null || s === undefined || s === '') return 1;
    return Number(s) || 0;
};

const construirFilasSerie = (rutinasDelDia) => {
    // Bloques del día (series = 0): no son series, no generan filas ni
    // cuentan para el total, pero los mostramos arriba de la lista para que
    // no se pierdan (ej. la movilidad articular del Día 1).
    bloquesDelDia.value = (rutinasDelDia || [])
        .filter((r) => Number(r.series) === 0)
        .map((r) => ({
            id: r.id,
            nombre: r.ejercicio_nombre,
            notas: r.notas || '',
        }));

    const historialCicloActual = cicloInicio.value
        ? historialRutina.value.filter((r) => (r.fecha || '').slice(0, 10) >= cicloInicio.value)
        : historialRutina.value;

    const registrosAnteriores = cicloInicio.value
        ? new Map(
              historialRutina.value
                  .filter((r) => r.dia === diaActual.value)
                  .filter((r) => (r.fecha || '').slice(0, 10) < cicloInicio.value)
                  .sort((a, b) => {
                      const fa = (a.fecha || '').slice(0, 10);
                      const fb = (b.fecha || '').slice(0, 10);
                      if (fa !== fb) return fb.localeCompare(fa);
                      return (b.id || 0) - (a.id || 0);
                  })
                  .reduce((acc, r) => {
                      const key = `${r.ejercicio_nombre}-${r.series_numero}`;
                      if (!acc.has(key)) acc.set(key, r);
                      return acc;
                  }, new Map())
          )
        : new Map();

    const registros = new Map(
        historialCicloActual
            .filter((r) => r.dia === diaActual.value)
            .map((r) => [`${r.ejercicio_nombre}-${r.series_numero}`, r])
    );

    const matchDay = (rDia, targetDia) => {
        if (!rDia || !targetDia) return false;
        if (rDia === targetDia) return true;
        const a = rDia.trim().toLowerCase();
        const b = targetDia.trim().toLowerCase();
        return a === b || a.startsWith(b) || b.startsWith(a);
    };

    const filteredRutinas = rutinasDelDia.filter((r) => matchDay(r.dia, diaActual.value));
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
            const totalSeries = seriesDeRutina(rutina);
            for (let index = 0; index < totalSeries; index++) {
                const serieNumero = index + 1;
                const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                const anterior = registrosAnteriores.get(
                    `${rutina.ejercicio_nombre}-${serieNumero}`
                );
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
                    esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                    esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                    notas: rutina.notas || null,
                    prescripcion: rutina.prescripcion || null,
                    previous_record: anterior
                        ? {
                              peso: anterior.peso ?? null,
                              reps_realizadas: anterior.reps_realizadas ?? null,
                              esfuerzo_tipo: anterior.esfuerzo_tipo ?? null,
                              esfuerzo_valor: anterior.esfuerzo_valor ?? null,
                          }
                        : null,
                });
            }
        } else {
            const exercises = block.exercises;
            const maxSeries = Math.max(...exercises.map((r) => seriesDeRutina(r)));
            for (let index = 0; index < maxSeries; index++) {
                const serieNumero = index + 1;
                exercises.forEach((rutina) => {
                    const totalSeries = seriesDeRutina(rutina);
                    if (serieNumero <= totalSeries) {
                        const registro = registros.get(`${rutina.ejercicio_nombre}-${serieNumero}`);
                        const anterior = registrosAnteriores.get(
                            `${rutina.ejercicio_nombre}-${serieNumero}`
                        );
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
                            esfuerzo_tipo: registro?.esfuerzo_tipo ?? null,
                            esfuerzo_valor: registro?.esfuerzo_valor ?? null,
                            notas: rutina.notas || null,
                            prescripcion: rutina.prescripcion || null,
                            previous_record: anterior
                                ? {
                                      peso: anterior.peso ?? null,
                                      reps_realizadas: anterior.reps_realizadas ?? null,
                                      esfuerzo_tipo: anterior.esfuerzo_tipo ?? null,
                                      esfuerzo_valor: anterior.esfuerzo_valor ?? null,
                                  }
                                : null,
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

        if (diasUnicos.length > 0 && !diasUnicos.includes(diaActual.value)) {
            const match = diasUnicos.find(
                (d) =>
                    d.toLowerCase().startsWith(diaActual.value.toLowerCase()) ||
                    diaActual.value.toLowerCase().startsWith(d.toLowerCase())
            );
            if (match) {
                diaActual.value = match;
            }
        }

        await fetchHistorialRutina();
        construirFilasSerie(response.data);
        cargarTrend30d();
    } catch (error) {
        console.error('Error:', error);
    } finally {
        isInitialLoading.value = false;
    }
};

/**
 * Payload que espera /api/historial/guardar para una fila del dashboard.
 * Compartido entre el guardado individual y el guardado en lote.
 */
const payloadDeFila = (fila) => ({
    fecha: hoyLocal(),
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
    esfuerzo_tipo: fila.esfuerzo_tipo || null,
    esfuerzo_valor: fila.esfuerzo_valor ?? null,
});

/**
 * Guarda TODAS las filas en un único request.
 *
 * Antes se hacía Promise.all(filas.map(guardarFila)): N POST simultáneos que,
 * en el hosting compartido, agotaban las conexiones a MySQL y devolvían 500
 * ("[2002] Operation not permitted") en todos.
 */
const guardarFilasEnLote = async (filas) => {
    const result = await offline.recordSetBatch(filas.map(payloadDeFila));
    if (result.status === 'rejected') {
        throw new Error(result.error || 'El servidor rechazó las series');
    }
    return result;
};

const guardarFila = async (fila, silencioso = false) => {
    try {
        const result = await offline.recordSet(payloadDeFila(fila));
        if (result.status === 'queued' && !silencioso) {
            showError?.(
                'Sin conexion: guardado en este dispositivo, se sincroniza al volver online.'
            );
        } else if (result.status === 'rejected' && !silencioso) {
            // El server la rechazó de forma permanente: la serie NO se guarda.
            // Queda marcada como fallida y se avisa (antes se perdía en silencio).
            showError?.(`No se pudo guardar la serie: ${result.error}`);
        } else if (result.status === 'lost' && !silencioso) {
            showError?.(
                'No se pudo guardar: estas sin conexion y tu navegador no soporta guardado offline.'
            );
        }
        if (!silencioso && fila.completado && deberiaIniciarTemporizador(fila)) {
            iniciarTemporizador(fila);
        }

        if (!silencioso && fila.completado && fila.peso && Number(fila.peso) > 0) {
            const pesoActual = Number(fila.peso);
            const maxHistorico = historialRutina.value
                .filter(
                    (r) =>
                        r.ejercicio_nombre === fila.ejercicio_nombre &&
                        r.completado &&
                        r.peso &&
                        Number(r.peso) > 0
                )
                .filter((r) => {
                    if (!cicloInicio.value) return true;
                    return (r.fecha || '').slice(0, 10) < cicloInicio.value;
                })
                .reduce((max, r) => Math.max(max, Number(r.peso) || 0), 0);

            if (pesoActual > maxHistorico) {
                const repsTxt =
                    fila.reps_realizadas != null ? ` × ${fila.reps_realizadas} reps` : '';
                showSuccess?.(
                    `🏆 ¡NUEVO PR en ${fila.ejercicio_nombre}! ${pesoActual} kg${repsTxt}`
                );
                triggerConfetti();
            }
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
    prepararResumenDelDia();
    showDaySummary.value = true;
};

const showDaySummary = shallowRef(false);
const daySummary = ref({
    diaLabel: '',
    siguienteLabel: '',
    tieneSiguiente: false,
    completadas: 0,
    total: 0,
    volumen: 0,
    prs: 0,
    mejorSet: null,
});

const formatVolumen = (v) => {
    if (!v) return '0';
    return v >= 1000 ? (v / 1000).toFixed(1) + 'k' : Math.round(v).toString();
};

const prepararResumenDelDia = () => {
    const idxActual = todosLosDias.value.indexOf(diaActual.value);
    const esUltimo = idxActual === todosLosDias.value.length - 1;
    const filasDelDia = filasSerie.value.filter((f) => f.dia === diaActual.value);
    const completadas = filasDelDia.filter((f) => f.completado);
    const volumen = completadas.reduce((t, f) => {
        const p = Number(f.peso) || 0;
        const r = Number(f.reps_realizadas) || 0;
        return t + p * r;
    }, 0);

    let prsCount = 0;
    if (cicloInicio.value) {
        completadas.forEach((f) => {
            if (!f.peso) return;
            const maxAntes = historialRutina.value
                .filter(
                    (r) =>
                        r.ejercicio_nombre === f.ejercicio_nombre &&
                        r.completado &&
                        r.peso &&
                        Number(r.peso) > 0 &&
                        (r.fecha || '').slice(0, 10) < cicloInicio.value
                )
                .reduce((max, r) => Math.max(max, Number(r.peso) || 0), 0);
            if (Number(f.peso) > maxAntes) prsCount++;
        });
    }

    let mejor = null;
    completadas.forEach((f) => {
        const vol = (Number(f.peso) || 0) * (Number(f.reps_realizadas) || 0);
        if (!mejor || vol > mejor.vol) {
            mejor = {
                ejercicio: f.ejercicio_nombre,
                peso: f.peso,
                reps: f.reps_realizadas,
                vol,
            };
        }
    });

    daySummary.value = {
        diaLabel: diaActual.value,
        siguienteLabel: esUltimo ? '' : todosLosDias.value[idxActual + 1],
        tieneSiguiente: !esUltimo,
        completadas: completadas.length,
        total: filasDelDia.length,
        volumen,
        prs: prsCount,
        mejorSet: mejor,
    };
};

const avanzarAlSiguienteDia = async () => {
    showDaySummary.value = false;
    const idxActual = todosLosDias.value.indexOf(diaActual.value);
    if (idxActual < todosLosDias.value.length - 1) {
        diaActual.value = todosLosDias.value[idxActual + 1];
        await guardarProgreso();
        fetchRutinasDelDia();
    }
};

const finalizarRutinaDesdeResumen = async () => {
    showDaySummary.value = false;
    await finalizarRutina();
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
        const result = await guardarFilasEnLote(filasSerie.value);
        if (result.status === 'queued') {
            showWarning(
                'No se pudo conectar con el servidor: la sesión quedó guardada en este dispositivo y se sincroniza sola.'
            );
            return;
        }
        showSuccess('✓ Sesión guardada correctamente');
    } catch (error) {
        console.error('Error:', error);
        showError('No se pudo guardar la sesión. Intenta de nuevo.');
    }
};

const finalizarRutina = async () => {
    if (!rutinaStore.seleccionada) return;
    try {
        await guardarFilasEnLote(filasSerie.value);
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

const irARutinas = () => {
    window.location.href = '/rutinas';
};

onMounted(async () => {
    rutinaStore.hidratar();
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        await fetchRutinasDelDia();
    }
    await cargarDashboardToday();

    if (rutinaStore.seleccionada && onboarding.shouldShow()) {
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

const refreshDashboard = async () => {
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        await fetchRutinasDelDia();
    }
    await cargarDashboardToday();
};
const { isRefreshing, pullOffset } = usePullToRefresh(window, refreshDashboard);

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

<template>
    <div
        class="min-h-screen w-full bg-background font-body-md text-on-surface antialiased selection:bg-secondary-container selection:text-on-secondary-container overflow-x-hidden"
    >
        <SyncBadge
            :pending="offline.pendingCount.value"
            :syncing="offline.isSyncing.value"
            :failed="offline.failedCount.value"
            :error-message="offline.lastError.value"
            @retry="reintentarSeriesFallidas"
            @dismiss="descartarSeriesFallidas"
        />

        <!-- Indicador pull-to-refresh (mobile) -->
        <div
            v-show="pullOffset > 4 || isRefreshing"
            :style="{ height: pullOffset + 'px' }"
            class="md:hidden flex items-center justify-center overflow-hidden transition-[height] duration-150 max-w-md mx-auto"
            aria-live="polite"
            role="status"
        >
            <div class="flex flex-col items-center gap-1 text-xs font-semibold text-outline">
                <span class="material-symbols-outlined text-secondary animate-spin text-[20px]">
                    progress_activity
                </span>
                <span>{{ isRefreshing ? 'Actualizando…' : 'Deslizá hacia abajo' }}</span>
            </div>
        </div>

        <template v-if="rutinaStore.seleccionada">
            <div
                class="max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-lg"
            >
                <div class="flex flex-col w-full">
                    <!-- Subtle ambient canvas glow -->
                    <div class="relative w-full">
                        <div
                            class="absolute -top-32 left-1/2 -translate-x-1/2 w-[720px] h-[360px] bg-secondary-container/15 rounded-full blur-[140px] pointer-events-none"
                        ></div>

                        <!-- Active session banner -->
                        <DashboardActiveSessionBanner
                            @continue="abrirModoEntrenamiento"
                            @discard="descartarSesion"
                        />

                        <!-- Header / Welcome Banner -->
                        <div
                            class="relative flex flex-col md:flex-row md:items-end justify-between gap-space-lg mb-space-xl"
                        >
                            <div class="flex flex-col gap-space-xs">
                                <div class="flex items-center gap-space-sm">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-space-sm py-0.5 rounded-full bg-surface-container-high text-secondary font-label-sm text-label-sm uppercase tracking-wider"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"
                                        ></span>
                                        {{ diaSemanaEs }}, {{ fechaCorta }}
                                    </span>
                                    <span class="font-body-sm text-body-sm text-outline">
                                        • Temporada {{ anioActual }}
                                    </span>
                                </div>
                                <h1
                                    class="font-headline-xl text-headline-xl text-on-surface tracking-tight font-bold"
                                >
                                    ¡A entrenar,
                                    <span class="text-secondary">{{ userFirstName }}</span
                                    >!
                                </h1>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    Tu constancia define tus resultados. Hoy completás el ciclo
                                    semanal de fuerza.
                                </p>
                            </div>

                            <!-- Quick Actions Header -->
                            <DashboardQuickActions
                                @start-workout="abrirModoEntrenamiento"
                                @change-routine="cambiarRutina"
                            />
                        </div>

                        <!-- Routine Hero Card -->
                        <DashboardRoutineHeroCard
                            :nombre-rutina="nombreRutina"
                            :titulo-hero-dia="tituloHeroDia"
                            :ejercicios-count="ejerciciosDelDia.length"
                            :series-totales="seriesTotales"
                            :duracion-estimada="duracionEstimada"
                            :calorias-estimadas="caloriasEstimadas"
                            :estado-ultimo-registro-hero="estadoUltimoRegistroHero"
                            :series-completadas="seriesCompletadas"
                            :progreso-dia="progresoDia"
                            @start-workout="abrirModoEntrenamiento"
                        />

                        <!-- Plan Selection Segmented Pills -->
                        <DashboardDaySelector
                            :dias="todosLosDias"
                            :dia-actual="diaActual"
                            :semana-ciclo-label="semanaCicloLabel"
                            :format-dia-tab-label="formatDiaTabLabel"
                            @select-day="cambiarDia"
                        />

                        <!-- Key Metrics Grid -->
                        <DashboardKeyMetrics
                            :stats="stats"
                            :ultimo-entreno-label="ultimoEntrenoLabel"
                            :ultimo-entreno-sub="ultimoEntrenoSub"
                            :ultimo-entreno-alerta="ultimoEntrenoAlerta"
                            :series-completadas="seriesCompletadas"
                            :series-totales="seriesTotales"
                            :series-pendientes="seriesPendientes"
                            :peso-registrado="pesoRegistrado"
                            :peso-promedio="pesoPromedio"
                            :reps-registradas="repsRegistradas"
                        />

                        <!-- Workout Exercises Section -->
                        <DashboardExerciseList
                            :ejercicios-del-dia="ejerciciosDelDia"
                            :ejercicios-completados-count="ejerciciosCompletadosCount"
                            :bloques-del-dia="bloquesDelDia"
                            :is-initial-loading="isInitialLoading"
                            :expandir-todos="expandirTodos"
                            :active-inline-set="activeInlineSet"
                            :es-set-historico="esSetHistorico"
                            :format-set-label="formatSetLabel"
                            :valor-historico-set="valorHistoricoSet"
                            @toggle-expand="expandirTodos = !expandirTodos"
                            @toggle-inline-set="toggleInlineSet"
                            @close-inline-set="activeInlineSet = null"
                            @copy-from-previous="copyInlineFromPrev"
                            @step-weight="stepInlinePeso"
                            @step-reps="stepInlineReps"
                            @complete-set="completeInlineSet"
                            @update-field="updateInlineField"
                        />

                        <!-- Session Actions Bar -->
                        <DashboardSessionActions
                            :dia-index="diaIndex"
                            :es-ultimo-dia="esUltimoDia"
                            :dias-siguiente-label="diasSiguienteLabel"
                            @previous-day="diaAnterior"
                            @save-session="guardarSesion"
                            @next-day="siguienteDia"
                            @finish-routine="finalizarRutina"
                        />

                        <!-- Analytics & Telemetry Charts Section (Ubicado después de las acciones para no entorpecer el entrenamiento) -->
                        <div class="mt-space-lg">
                            <DashboardTelemetryCharts
                                :historial-rutina="historialRutina"
                                :ejercicios-del-dia="ejerciciosDelDia"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Pro Telemetry -->
            <DashboardFooter :anio-actual="anioActual" />
        </template>

        <EmptyStateIllustrated
            v-else
            variant="no-rutinas"
            title="No hay rutina seleccionada"
            description="Elegí una rutina para empezar a registrar tus series y llevar el control de tu progreso."
            cta-text="Seleccionar Rutina"
            cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
            @cta="irARutinas"
        />

        <!-- Onboarding tour -->
        <OnboardingTour :tour="onboarding" />

        <!-- Modales de Modo Entrenamiento Activo -->
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

        <!-- Bottom sheet: resumen al cambiar de día -->
        <DashboardDaySummarySheet
            v-if="showDaySummary"
            :summary="daySummary"
            :format-volumen="formatVolumen"
            @close="showDaySummary = false"
            @next-day="avanzarAlSiguienteDia"
            @finish-routine="finalizarRutinaDesdeResumen"
        />
    </div>
</template>

