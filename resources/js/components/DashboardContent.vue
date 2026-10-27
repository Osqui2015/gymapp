<script setup>
import { ref, shallowRef, computed, watch, onMounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useOnboarding } from '../composables/useOnboarding';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useWakeLock } from '@/composables/useWakeLock';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import SyncBadge from './training/SyncBadge.vue';
import PlanSeriesChips from './training/PlanSeriesChips.vue';
import { tienePlan } from '@/utils/rutinaPrescripcion';
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';
import OnboardingTour from './OnboardingTour.vue';
import confetti from 'canvas-confetti';
import SkeletonLoader from './common/SkeletonLoader.vue';
import { useRestTimerStore } from '../stores/restTimer';
import ActiveWorkoutModal from './training/ActiveWorkoutModal.vue';
import WorkoutSummaryModal from './training/WorkoutSummaryModal.vue';

const rutinaStore = useRutinaStore();

// === Modo entrenamiento ===
const offline = useOfflineSeries();
const wake = useWakeLock();
const session = useTrainingSessionStore();

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
const expandirTodos = shallowRef(true);
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

// === Telemetry Chart 1: Peso máximo por día (30d) en ejercicio principal ===
const chartEjercicioNombre = computed(() => {
    const first = ejerciciosDelDia.value[0]?.nombre;
    return first || 'Press de banca';
});

const chartSeries = computed(() => {
    const h = historialRutina.value || [];
    const hoy = new Date();
    const desde = new Date(hoy.getTime() - 30 * 86400000);
    const N = 7;
    const puntos = [];
    for (let i = 0; i < N; i++) {
        const f = new Date(desde.getTime() + ((hoy.getTime() - desde.getTime()) * i) / (N - 1));
        puntos.push({ fecha: f, peso: 0 });
    }

    h.forEach((r) => {
        if (!r.completado || !r.peso || Number(r.peso) <= 0) return;
        const fr = new Date(r.fecha);
        if (fr < desde || fr > hoy) return;
        let mejorI = 0;
        let mejorDelta = Infinity;
        for (let i = 0; i < N; i++) {
            const delta = Math.abs(puntos[i].fecha - fr);
            if (delta < mejorDelta) {
                mejorDelta = delta;
                mejorI = i;
            }
        }
        puntos[mejorI].peso = Math.max(puntos[mejorI].peso, Number(r.peso));
    });

    const labels = puntos.map((p) => {
        const dd = String(p.fecha.getDate()).padStart(2, '0');
        const mm = String(p.fecha.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}`;
    });

    return { points: puntos, labels };
});

const maxPesoChart = computed(() => {
    const pts = chartSeries.value.points;
    if (!pts.length) return 80;
    const max = Math.max(...pts.map((p) => p.peso || 0));
    return max > 0 ? max : 80;
});

const buildSmoothSvgCurve = (coords, baselineY = 170) => {
    if (!coords.length) return { line: '', area: '' };
    if (coords.length === 1) {
        const p = coords[0];
        const line = `M 20 ${p.y} L 480 ${p.y}`;
        const area = `${line} L 480 ${baselineY} L 20 ${baselineY} Z`;
        return { line, area };
    }
    let line = `M ${coords[0].x} ${coords[0].y}`;
    for (let i = 1; i < coords.length; i++) {
        const prev = coords[i - 1];
        const cur = coords[i];
        const dx = (cur.x - prev.x) * 0.45;
        const cp1x = Math.round(prev.x + dx);
        const cp2x = Math.round(cur.x - dx);
        line += ` C ${cp1x} ${prev.y}, ${cp2x} ${cur.y}, ${cur.x} ${cur.y}`;
    }
    const last = coords[coords.length - 1];
    const first = coords[0];
    const area = `${line} L ${last.x} ${baselineY} L ${first.x} ${baselineY} Z`;
    return { line, area };
};

const chart1Telemetry = computed(() => {
    const { points, labels } = chartSeries.value;
    const max = Math.max(...points.map((p) => p.peso), 0);
    if (max <= 0) {
        // Fallback curva de telemetría cuando aún no hay puntos en los últimos 30d
        const fallbackNodes = [
            { x: 20, y: 145, peak: false, muted: false },
            { x: 95, y: 90, peak: false, muted: false },
            { x: 165, y: 30, peak: true, muted: false },
            { x: 235, y: 150, peak: false, muted: false },
            { x: 360, y: 150, peak: false, muted: true },
            { x: 480, y: 150, peak: false, muted: true },
        ];
        const { line, area } = buildSmoothSvgCurve(fallbackNodes, 170);
        return { nodes: fallbackNodes, line, area, labels };
    }
    const min = Math.min(...points.map((p) => p.peso));
    const rango = Math.max(max - min, 1);
    let peakAssigned = false;
    const nodes = points.map((p, i) => {
        const x = Math.round(20 + (460 * i) / (points.length - 1));
        const y = Math.round(150 - ((p.peso - min) / rango) * 120);
        const isPeak = !peakAssigned && p.peso === max && max > 0;
        if (isPeak) peakAssigned = true;
        return {
            x,
            y,
            value: p.peso,
            peak: isPeak,
            muted: p.peso === 0,
        };
    });
    const { line, area } = buildSmoothSvgCurve(nodes, 170);
    return { nodes, line, area, labels };
});

// === Telemetry Chart 2: PR diario a lo largo del tiempo (30d) ===
const chart2Telemetry = computed(() => {
    const porDia = new Map();
    (historialRutina.value || []).forEach((row) => {
        const fecha = row.fecha || (row.created_at ? row.created_at.split('T')[0] : null);
        if (!fecha) return;
        const peso = Number(row.peso) || 0;
        if (peso <= 0) return;
        if (!porDia.has(fecha) || peso > porDia.get(fecha)) {
            porDia.set(fecha, peso);
        }
    });

    const sorted = [...porDia.entries()].sort((a, b) => a[0].localeCompare(b[0])).slice(-7);
    if (sorted.length < 2) {
        const fallbackNodes = [
            { x: 20, y: 140, isPeak: false, isAccent: false },
            { x: 90, y: 70, isPeak: false, isAccent: false },
            { x: 190, y: 145, isPeak: false, isAccent: false },
            { x: 270, y: 140, isPeak: false, isAccent: false },
            { x: 370, y: 70, isPeak: false, isAccent: true },
            { x: 430, y: 62, isPeak: false, isAccent: false },
            { x: 480, y: 30, isPeak: true, isAccent: false },
        ];
        const { line, area } = buildSmoothSvgCurve(fallbackNodes, 170);
        return {
            nodes: fallbackNodes,
            line,
            area,
            labels: ['03/09', '09/09', '12/09', '23/09', '30/09'],
        };
    }

    const pesos = sorted.map(([, p]) => p);
    const max = Math.max(...pesos);
    const min = Math.min(...pesos);
    const rango = Math.max(max - min, 1);

    const nodes = sorted.map(([, peso], i) => {
        const x = Math.round(20 + (460 * i) / (sorted.length - 1));
        const y = Math.round(145 - ((peso - min) / rango) * 115);
        return {
            x,
            y,
            value: peso,
            isPeak: i === sorted.length - 1 || (peso === max && i > 0),
            isAccent: i === Math.floor(sorted.length * 0.65),
        };
    });

    const labels = sorted.map(([fecha]) => {
        const iso = typeof fecha === 'string' && fecha.includes('T') ? fecha.split('T')[0] : fecha;
        const parts = String(iso).split('-');
        if (parts.length >= 3) {
            return `${parts[2]}/${parts[1]}`;
        }
        return iso;
    });

    const { line, area } = buildSmoothSvgCurve(nodes, 170);
    return { nodes, line, area, labels };
});

// === Sesión activa y acciones ===
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

const guardarFila = async (fila, silencioso = false) => {
    try {
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
        <SyncBadge :pending="offline.pendingCount.value" :syncing="offline.isSyncing.value" />

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
                        <div
                            v-if="session.isActive"
                            class="relative mb-space-lg overflow-hidden rounded-xl border border-tertiary/40 bg-surface-container-low p-space-md text-on-surface shadow-xl flex flex-wrap items-center justify-between gap-3 animate-fadeIn"
                        >
                            <div class="flex items-center gap-space-md">
                                <span class="relative flex h-3.5 w-3.5">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-tertiary opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex rounded-full h-3.5 w-3.5 bg-tertiary"
                                    ></span>
                                </span>
                                <div>
                                    <p
                                        class="font-label-lg text-label-lg text-on-surface flex items-center gap-2"
                                    >
                                        <span
                                            >Entrenamiento en curso ·
                                            {{ formattedActiveTime }}</span
                                        >
                                        <span
                                            v-if="session.isPaused"
                                            class="font-label-sm text-label-sm bg-secondary-container/60 text-secondary px-2 py-0.5 rounded-full"
                                        >
                                            Pausado
                                        </span>
                                    </p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                                        {{
                                            session.currentEjercicio?.nombre || 'Sesión iniciada'
                                        }}
                                        · {{ session.totalSeriesCompletadas }}/{{
                                            session.totalSeriesObjetivo
                                        }}
                                        series
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-space-sm">
                                <button
                                    type="button"
                                    @click="abrirModoEntrenamiento"
                                    class="px-space-md py-space-sm rounded-lg bg-tertiary text-on-tertiary font-label-md text-label-md font-bold shadow-md active:scale-95 transition-all flex items-center gap-1"
                                >
                                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                                    <span>Continuar</span>
                                </button>
                                <button
                                    type="button"
                                    @click="descartarSesion"
                                    title="Descartar entrenamiento en curso"
                                    aria-label="Descartar entrenamiento en curso"
                                    class="px-space-sm py-space-sm rounded-lg bg-surface-container-high hover:bg-error-container text-on-surface-variant hover:text-error font-label-md text-label-md transition-all"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

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
                            <div class="flex items-center gap-space-md">
                                <button
                                    type="button"
                                    @click="abrirModoEntrenamiento"
                                    class="group flex items-center gap-2 px-space-md py-space-sm rounded-full bg-surface-container-high hover:bg-surface-bright text-on-surface transition-all duration-200"
                                >
                                    <span
                                        class="material-symbols-outlined text-secondary text-[18px] group-hover:scale-110 transition-transform"
                                        >bolt</span
                                    >
                                    <span class="font-label-md text-label-md">Focus Mode</span>
                                </button>
                                <button
                                    type="button"
                                    @click="cambiarRutina"
                                    class="flex items-center gap-1.5 text-secondary hover:text-on-secondary-container font-label-md text-label-md transition-colors px-space-sm py-space-sm"
                                >
                                    <span class="material-symbols-outlined text-[18px]"
                                        >swap_horiz</span
                                    >
                                    <span>Cambiar rutina</span>
                                </button>
                            </div>
                        </div>

                        <!-- Routine Hero Card -->
                        <section
                            class="relative rounded-xl bg-surface-container-low p-space-lg md:p-space-xl mb-space-xl overflow-hidden shadow-xl"
                            data-purpose="active-workout-hero"
                            data-tour="rutina-header"
                        >
                            <div
                                class="absolute -right-24 -bottom-24 w-80 h-80 bg-primary-container/10 rounded-full blur-[100px] pointer-events-none"
                            ></div>
                            <div
                                class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-space-xl mb-space-xl"
                            >
                                <div class="flex flex-col gap-space-sm max-w-2xl">
                                    <div class="flex items-center gap-space-sm">
                                        <span
                                            class="px-space-md py-0.5 rounded-full bg-secondary-container/60 text-secondary font-label-sm text-label-sm tracking-wide uppercase font-semibold"
                                        >
                                            {{ nombreRutina }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 text-tertiary font-label-sm text-label-sm"
                                        >
                                            <span class="material-symbols-outlined text-[14px]"
                                                >check_circle</span
                                            >
                                            Plan Óptimo
                                        </span>
                                    </div>
                                    <h2
                                        class="font-headline-xl text-headline-xl text-on-surface font-bold tracking-tight"
                                    >
                                        {{ tituloHeroDia }}
                                    </h2>
                                    <div
                                        class="flex flex-wrap items-center gap-space-md text-on-surface-variant font-label-md text-label-md"
                                    >
                                        <span class="flex items-center gap-1">
                                            <span
                                                class="material-symbols-outlined text-[16px] text-secondary"
                                                >fitness_center</span
                                            >
                                            {{ ejerciciosDelDia.length }} ejercicios
                                        </span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1">
                                            <span
                                                class="material-symbols-outlined text-[16px] text-secondary"
                                                >repeat</span
                                            >
                                            {{ seriesTotales }} series
                                        </span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1">
                                            <span
                                                class="material-symbols-outlined text-[16px] text-secondary"
                                                >timer</span
                                            >
                                            ~{{ duracionEstimada }} min
                                        </span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1 text-tertiary">
                                            <span class="material-symbols-outlined text-[16px]"
                                                >local_fire_department</span
                                            >
                                            {{ caloriasEstimadas }} kcal est.
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-end gap-space-md min-w-[240px]"
                                >
                                    <button
                                        type="button"
                                        @click="abrirModoEntrenamiento"
                                        class="w-full sm:w-auto px-space-xl py-space-md rounded-full bg-gradient-to-r from-primary-container to-secondary-container hover:from-primary hover:to-secondary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-[0_0_24px_rgba(99,102,241,0.45)] hover:shadow-[0_0_32px_rgba(139,92,246,0.6)] transition-all duration-300"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[20px]"
                                            :style="{ fontVariationSettings: '\'FILL\' 1' }"
                                            >play_arrow</span
                                        >
                                        <span>{{
                                            session.isActive
                                                ? 'Continuar entrenamiento'
                                                : 'Comenzar entrenamiento'
                                        }}</span>
                                    </button>
                                    <span
                                        class="font-label-sm text-label-sm text-outline text-center lg:text-right"
                                    >
                                        {{ estadoUltimoRegistroHero }}
                                    </span>
                                </div>
                            </div>

                            <!-- Session Progress Bar -->
                            <div
                                class="flex flex-col gap-space-xs pt-space-md bg-surface-container/50 -mx-space-lg md:-mx-space-xl -mb-space-lg md:-mb-space-xl px-space-lg md:px-space-xl pb-space-lg"
                            >
                                <div class="flex items-center justify-between text-on-surface">
                                    <span
                                        class="font-label-md text-label-md text-on-surface-variant flex items-center gap-2"
                                    >
                                        <span
                                            class="material-symbols-outlined text-tertiary text-[16px]"
                                            >verified</span
                                        >
                                        Progreso de la sesión
                                    </span>
                                    <span class="font-label-lg text-label-lg font-bold text-tertiary">
                                        {{ seriesCompletadas }} / {{ seriesTotales }} series ({{
                                            progresoDia
                                        }}%)
                                    </span>
                                </div>
                                <div
                                    class="w-full h-2.5 rounded-full bg-surface-container-highest overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-secondary via-primary-container to-tertiary transition-all duration-700"
                                        :style="{ width: progresoDia + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </section>

                        <!-- Plan Selection Segmented Pills -->
                        <div
                            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-md mb-space-lg"
                            data-tour="day-selector"
                        >
                            <div
                                class="flex flex-wrap items-center gap-space-xs p-1 rounded-xl bg-surface-container-low"
                            >
                                <button
                                    v-for="dia in todosLosDias"
                                    :key="dia"
                                    type="button"
                                    @click="cambiarDia(dia)"
                                    :class="[
                                        'px-space-md py-space-xs rounded-lg font-label-md text-label-md transition-all',
                                        diaActual === dia
                                            ? 'bg-secondary-container text-on-secondary-container font-semibold shadow-sm'
                                            : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container',
                                    ]"
                                >
                                    {{ formatDiaTabLabel(dia) }}
                                </button>
                            </div>
                            <div
                                class="flex items-center gap-space-xs text-outline font-label-sm text-label-sm"
                            >
                                <span class="material-symbols-outlined text-[16px] text-outline"
                                    >tune</span
                                >
                                <span
                                    >Ciclo de hipertrofia muscular •
                                    {{ semanaCicloLabel }}</span
                                >
                            </div>
                        </div>

                        <!-- Key Metrics Grid -->
                        <section
                            class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-space-md mb-space-xl"
                            data-tour="stats"
                        >
                            <!-- Sets 30 Días -->
                            <div
                                class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <div
                                    class="flex items-center justify-between gap-space-xs mb-space-sm"
                                >
                                    <span
                                        class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                                        >Sets 30 Días</span
                                    >
                                    <span
                                        v-if="stats.trend30d !== 0"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-full font-label-sm text-label-sm"
                                        :class="
                                            stats.trend30d >= 0
                                                ? 'bg-tertiary/10 text-tertiary'
                                                : 'bg-error/10 text-error'
                                        "
                                    >
                                        {{ stats.trend30d > 0 ? '+' : '' }}{{ stats.trend30d }}%
                                    </span>
                                </div>
                                <div>
                                    <div
                                        class="font-headline-lg text-headline-lg font-bold text-on-surface"
                                    >
                                        {{ stats.totalSets30d }}
                                    </div>
                                    <div class="font-body-sm text-body-sm text-tertiary">
                                        Volumen alto sostenido
                                    </div>
                                </div>
                            </div>

                            <!-- Último Entreno -->
                            <div
                                class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <div
                                    class="flex items-center justify-between gap-space-xs mb-space-sm"
                                >
                                    <span
                                        class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                                        >Último Entreno</span
                                    >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                        :class="ultimoEntrenoAlerta ? 'text-error' : 'text-tertiary'"
                                    >
                                        {{ ultimoEntrenoAlerta ? 'warning' : 'check_circle' }}
                                    </span>
                                </div>
                                <div>
                                    <div
                                        class="font-headline-lg text-headline-lg font-bold text-on-surface"
                                    >
                                        {{ ultimoEntrenoLabel }}
                                    </div>
                                    <div
                                        class="font-body-sm text-body-sm"
                                        :class="ultimoEntrenoAlerta ? 'text-error' : 'text-tertiary'"
                                    >
                                        {{ ultimoEntrenoSub }}
                                    </div>
                                </div>
                            </div>

                            <!-- Racha Actual -->
                            <div
                                class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <div
                                    class="flex items-center justify-between gap-space-xs mb-space-sm"
                                >
                                    <span
                                        class="font-label-sm text-label-sm uppercase tracking-wider text-outline"
                                        >Racha Actual</span
                                    >
                                    <span
                                        class="material-symbols-outlined text-secondary text-[18px]"
                                        >local_fire_department</span
                                    >
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <div>
                                        <div
                                            class="font-headline-lg text-headline-lg font-bold text-on-surface"
                                        >
                                            {{ stats.streak }}
                                            <span
                                                class="font-body-md text-body-md text-outline font-normal"
                                                >días</span
                                            >
                                        </div>
                                        <div
                                            class="font-body-sm text-body-sm text-on-surface-variant"
                                        >
                                            Récord previo: {{ stats.longestStreak }} días
                                        </div>
                                    </div>
                                    <div
                                        class="hidden sm:flex items-center gap-1 text-outline font-label-sm text-label-sm"
                                    >
                                        <span>Objetivo: 4/sem</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Series del Día -->
                            <div
                                class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <span
                                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                                    >Series del Día</span
                                >
                                <div>
                                    <div
                                        class="font-headline-lg text-headline-lg font-bold text-tertiary"
                                    >
                                        {{ seriesCompletadas }}/{{ seriesTotales }}
                                    </div>
                                    <div class="font-body-sm text-body-sm text-outline">
                                        {{ seriesPendientes }} pendientes
                                    </div>
                                </div>
                            </div>

                            <!-- Peso Registrado -->
                            <div
                                class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <span
                                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                                    >Peso Registrado</span
                                >
                                <div>
                                    <div
                                        class="font-headline-lg text-headline-lg font-bold text-on-surface"
                                    >
                                        {{ pesoRegistrado }}
                                        <span class="font-body-sm text-body-sm text-outline"
                                            >kg</span
                                        >
                                    </div>
                                    <div class="font-body-sm text-body-sm text-outline">
                                        Volumen de la sesión
                                    </div>
                                </div>
                            </div>

                            <!-- Peso Promedio -->
                            <div
                                class="col-span-1 md:col-span-1 lg:col-span-1 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <span
                                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                                    >Peso Prom.</span
                                >
                                <div>
                                    <div
                                        class="font-headline-md text-headline-md font-bold text-on-surface"
                                    >
                                        {{ pesoPromedio }}
                                        <span class="font-body-sm text-body-sm text-outline"
                                            >kg</span
                                        >
                                    </div>
                                    <div class="font-body-sm text-body-sm text-outline">
                                        Por serie
                                    </div>
                                </div>
                            </div>

                            <!-- Reps Realizadas -->
                            <div
                                class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col justify-between p-space-md rounded-xl bg-surface-container-low shadow-sm"
                            >
                                <span
                                    class="font-label-sm text-label-sm uppercase tracking-wider text-outline mb-space-xs"
                                    >Reps Realizadas</span
                                >
                                <div>
                                    <div
                                        class="font-headline-lg text-headline-lg font-bold text-secondary"
                                    >
                                        {{ repsRegistradas }}
                                    </div>
                                    <div class="font-body-sm text-body-sm text-outline">
                                        Repeticiones del día
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Workout Exercises Section -->
                        <section
                            class="flex flex-col gap-space-md mb-space-xl"
                            data-purpose="todays-exercise-list"
                            data-tour="series-list"
                        >
                            <!-- Section Header with Controls -->
                            <div class="flex items-center justify-between pb-space-xs">
                                <div class="flex items-center gap-space-sm">
                                    <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                                    <h3
                                        class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight"
                                    >
                                        EJERCICIOS DE HOY
                                    </h3>
                                    <span
                                        class="px-2 py-0.5 rounded-full bg-surface-container text-tertiary font-label-sm text-label-sm font-semibold"
                                    >
                                        {{ ejerciciosCompletadosCount }}/{{
                                            ejerciciosDelDia.length
                                        }}
                                        completados
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="expandirTodos = !expandirTodos"
                                    class="flex items-center gap-1.5 px-space-md py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface font-label-sm text-label-sm transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[16px]">
                                        {{ expandirTodos ? 'unfold_less' : 'unfold_more' }}
                                    </span>
                                    <span>{{ expandirTodos ? 'Colapsar' : 'Expandir' }}</span>
                                </button>
                            </div>

                            <!-- Bloques del día (movilidad / calentamiento): no son
                                 series, por eso no aparecen como ejercicio, pero
                                 hay que hacerlos antes de la barra. -->
                            <div
                                v-if="bloquesDelDia.length"
                                class="flex flex-col gap-2 rounded-xl border border-amber-500/25 bg-amber-500/5 px-space-md py-space-sm"
                                data-testid="bloques-del-dia"
                            >
                                <div
                                    v-for="bloque in bloquesDelDia"
                                    :key="bloque.id"
                                    class="flex items-start gap-2"
                                >
                                    <span class="material-symbols-outlined text-[16px] text-amber-400 mt-px">timer</span>
                                    <div class="min-w-0">
                                        <span class="text-label-sm text-label-sm font-bold text-amber-300 uppercase tracking-wide">
                                            {{ bloque.nombre }}
                                        </span>
                                        <p class="text-[11px] leading-relaxed text-on-surface-variant mt-0.5">
                                            {{ bloque.notas }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="isInitialLoading" class="space-y-3">
                                <SkeletonLoader variant="card" />
                                <SkeletonLoader variant="card" />
                                <SkeletonLoader variant="card" />
                            </div>

                            <!-- Exercise Cards Container -->
                            <div v-else class="flex flex-col gap-space-md transition-all">
                                <article
                                    v-for="(ej, idx) in ejerciciosDelDia"
                                    :key="ej.nombre"
                                    class="p-space-md md:p-space-lg rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors shadow-sm"
                                >
                                    <div
                                        class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md"
                                    >
                                        <div class="flex items-center gap-space-md">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-surface-container-highest flex items-center justify-center font-label-md text-label-md text-on-surface font-bold shrink-0"
                                            >
                                                {{ idx + 1 }}
                                            </div>
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-space-sm">
                                                    <h4
                                                        class="font-headline-md text-headline-md text-on-surface font-bold"
                                                    >
                                                        {{ ej.nombre }}
                                                    </h4>
                                                    <span
                                                        v-if="ej.estaCompleto"
                                                        class="material-symbols-outlined text-tertiary text-[18px]"
                                                        >verified</span
                                                    >
                                                </div>
                                                <div
                                                    class="font-body-sm text-body-sm text-outline flex flex-wrap items-center gap-space-xs"
                                                >
                                                    <span v-if="ej.repsLabel">{{
                                                        ej.repsLabel
                                                    }}</span>
                                                    <span
                                                        v-if="ej.repsLabel && ej.ultimoPesoLabel"
                                                        >•</span
                                                    >
                                                    <span v-if="ej.ultimoPesoLabel">{{
                                                        ej.ultimoPesoLabel
                                                    }}</span>
                                                    <template v-if="ej.musculoLabel">
                                                        <span>•</span>
                                                        <span class="text-tertiary">{{
                                                            ej.musculoLabel
                                                        }}</span>
                                                    </template>
                                                </div>

                                                <!-- Plan de series: bloques, rest-pause y progresión -->
                                                <PlanSeriesChips
                                                    :ejercicio="ej"
                                                    tema="auto"
                                                    mostrar-consejo
                                                />

                                                <!-- Notas crudas cuando el ejercicio no tiene plan
                                                     (ej. "3x8 (estocadas, sin RIR objetivo)") -->
                                                <p
                                                    v-if="!tienePlan(ej) && ej.notas"
                                                    class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400 italic"
                                                    :title="ej.notas"
                                                >
                                                    💡 {{ ej.notas }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center gap-space-sm justify-between lg:justify-end"
                                        >
                                            <span
                                                class="px-2.5 py-1 rounded-full bg-surface-container-high font-label-sm text-label-sm font-semibold"
                                                :class="
                                                    ej.estaCompleto
                                                        ? 'text-tertiary'
                                                        : 'text-secondary'
                                                "
                                            >
                                                {{ ej.completadas }}/{{ ej.sets.length }} series
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Sets Chips Grid (Interactive Tap-to-Edit) -->
                                    <div
                                        v-show="expandirTodos"
                                        class="mt-space-md pt-space-md bg-surface-container-lowest/40 -mx-space-md -mb-space-md md:-mx-space-lg md:-mb-space-lg p-space-md md:p-space-md rounded-b-xl space-y-3"
                                    >
                                        <div
                                            :class="[
                                                'grid gap-space-sm',
                                                ej.sets.length === 3
                                                    ? 'grid-cols-2 sm:grid-cols-3'
                                                    : 'grid-cols-2 sm:grid-cols-4',
                                            ]"
                                        >
                                            <button
                                                v-for="(set, sIdx) in ej.sets"
                                                :key="sIdx"
                                                type="button"
                                                @click="toggleInlineSet(set)"
                                                :title="
                                                    esSetHistorico(set)
                                                        ? 'Tocá para editar o completar serie (valor previo disponible)'
                                                        : 'Tocá para editar peso y repeticiones'
                                                "
                                                :class="[
                                                    'flex flex-col px-space-md py-2 rounded-lg text-left transition-all cursor-pointer relative group/set',
                                                    activeInlineSet === set
                                                        ? 'ring-2 ring-secondary bg-surface-container-high'
                                                        : set.completado
                                                          ? 'bg-tertiary/10 hover:bg-tertiary/15'
                                                          : esSetHistorico(set)
                                                            ? 'bg-surface-container-high/50 border border-dashed border-outline-variant/50 hover:border-secondary/60'
                                                            : 'bg-surface-container hover:bg-surface-container-high',
                                                ]"
                                            >
                                                <div class="flex items-center justify-between w-full">
                                                    <span
                                                        class="font-label-sm text-label-sm"
                                                        :class="
                                                            set.completado
                                                                ? 'text-tertiary'
                                                                : esSetHistorico(set)
                                                                  ? 'text-secondary'
                                                                  : 'text-outline'
                                                        "
                                                    >
                                                        SET {{ sIdx + 1 }}
                                                    </span>
                                                    <span
                                                        v-if="set.completado"
                                                        class="material-symbols-outlined text-[14px] text-tertiary"
                                                    >
                                                        check_circle
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="material-symbols-outlined text-[13px] text-outline/60 group-hover/set:text-secondary transition-colors"
                                                    >
                                                        edit
                                                    </span>
                                                </div>
                                                <span
                                                    class="font-label-md text-label-md text-on-surface font-bold tabular-nums"
                                                >
                                                    {{ formatSetLabel(set) }}
                                                </span>
                                            </button>
                                        </div>

                                        <!-- Inline Quick Set Telemetry Editor -->
                                        <div
                                            v-if="ej.sets.includes(activeInlineSet)"
                                            class="p-3 rounded-xl bg-[#131924] border border-[#232D42] flex flex-col gap-3 shadow-lg"
                                        >
                                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                                                    <span class="text-xs font-bold text-white uppercase tracking-wider">
                                                        Editar Set #{{ activeInlineSet.series_numero }} · {{ ej.nombre }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        v-if="valorHistoricoSet(activeInlineSet)"
                                                        type="button"
                                                        @click="copyInlineFromPrev(activeInlineSet)"
                                                        class="px-2.5 py-1 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/40 text-[11px] font-mono font-bold text-indigo-300 cursor-pointer"
                                                    >
                                                        ↺ Copiar anterior ({{ valorHistoricoSet(activeInlineSet).peso ?? 0 }}kg × {{ valorHistoricoSet(activeInlineSet).reps ?? 0 }}r)
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="activeInlineSet = null"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-white cursor-pointer"
                                                        aria-label="Cerrar editor rápido"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 items-end">
                                                <!-- Peso (kg) con botones +/- -->
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                                        Peso (kg)
                                                    </label>
                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            type="button"
                                                            @click="stepInlinePeso(activeInlineSet, -2.5)"
                                                            class="h-10 px-2.5 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                                                        >
                                                            -2.5
                                                        </button>
                                                        <input
                                                            v-model.number="activeInlineSet.peso"
                                                            type="number"
                                                            inputmode="decimal"
                                                            step="0.5"
                                                            min="0"
                                                            placeholder="0"
                                                            class="w-full h-10 rounded-lg bg-[#101521] border border-[#232D42] text-center font-mono text-sm font-bold text-white focus:outline-none focus:border-indigo-500"
                                                        />
                                                        <button
                                                            type="button"
                                                            @click="stepInlinePeso(activeInlineSet, 2.5)"
                                                            class="h-10 px-2.5 rounded-lg bg-indigo-600/20 border border-indigo-500/40 text-xs font-mono font-bold text-indigo-300 hover:bg-indigo-600/30 active:scale-95 transition-all cursor-pointer"
                                                        >
                                                            +2.5
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Reps con botones +/- -->
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                                        Repeticiones
                                                    </label>
                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            type="button"
                                                            @click="stepInlineReps(activeInlineSet, -1)"
                                                            class="h-10 px-3 rounded-lg bg-[#101521] border border-[#232D42] text-xs font-mono font-bold text-slate-300 hover:text-white active:scale-95 transition-all cursor-pointer"
                                                        >
                                                            -1
                                                        </button>
                                                        <input
                                                            v-model.number="activeInlineSet.reps_realizadas"
                                                            type="number"
                                                            inputmode="numeric"
                                                            step="1"
                                                            min="0"
                                                            placeholder="8"
                                                            class="w-full h-10 rounded-lg bg-[#101521] border border-[#232D42] text-center font-mono text-sm font-bold text-white focus:outline-none focus:border-indigo-500"
                                                        />
                                                        <button
                                                            type="button"
                                                            @click="stepInlineReps(activeInlineSet, 1)"
                                                            class="h-10 px-3 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-xs font-mono font-bold text-emerald-300 hover:bg-emerald-500/30 active:scale-95 transition-all cursor-pointer"
                                                        >
                                                            +1
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Botón Completar Set -->
                                                <button
                                                    type="button"
                                                    @click="completeInlineSet(activeInlineSet, ej.sets)"
                                                    class="h-10 px-4 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 shadow-sm active:scale-95 transition-all cursor-pointer"
                                                >
                                                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                                    <span>{{ activeInlineSet.completado ? 'Actualizar serie' : 'Completar serie' }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <!-- Visual Media Interlude (Rich photographic elements) -->
                        <section class="grid grid-cols-1 md:grid-cols-3 gap-space-md mb-space-xl">
                            <div class="relative h-44 rounded-xl overflow-hidden group">
                                <img
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    alt="Sobrecarga Progresiva"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuA_237cDXPJ5kXFpkwOdgxoIn0ODK9BARrO1MF8UvxwL8MD8b_-c3eyKUBQS9taIQ6Cev_NY2hKw8ioQCSoVTDvku5PSOLJj1nz_m9LdQoF0J6fv8odydpTB844OwrVd5956e5-67mWps8BxjHYgAkqz7M2xIXYxVgOWx5OP0uPo_B6JIQ-5DzGfSFqIcJ_GmFHqGPzeJFMP7EFAfy3wgnUJmYS83AuoE7KcGiekA"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-surface-dim via-surface-dim/40 to-transparent flex flex-col justify-end p-space-md"
                                >
                                    <span
                                        class="font-label-sm text-label-sm uppercase text-secondary"
                                        >Sobrecarga Progresiva</span
                                    >
                                    <span
                                        class="font-label-lg text-label-lg text-on-surface font-bold"
                                        >+12.5% volumen pectoral</span
                                    >
                                </div>
                            </div>
                            <div class="relative h-44 rounded-xl overflow-hidden group">
                                <img
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    alt="Intensidad Media (RPE 8.5)"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCiu2GeVZel5XiEQKR3umJb6-DQG4eTcj9yFTyNKlZYQQIf0ft4GyA1r5wN9R1dYsXktsfqAMm_fYnfDPpFQq8GgYcAsvEaJCdlcnzrM7S81rjhomiG8Y4QXF7qeb24JCfOOQu_54qWBJupX2rf8LWru2yMFY6er8hXT-cNedZ93gf1ZZ9L2LDisYk1Zwsi8pvvvSdHxIKffbEbmNlwiBN0GHShts8LD7bwVSVQSQ"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-surface-dim via-surface-dim/40 to-transparent flex flex-col justify-end p-space-md"
                                >
                                    <span
                                        class="font-label-sm text-label-sm uppercase text-tertiary"
                                        >Intensidad Media (RPE 8.5)</span
                                    >
                                    <span
                                        class="font-label-lg text-label-lg text-on-surface font-bold"
                                        >Cadencia de empuje controlada</span
                                    >
                                </div>
                            </div>
                            <div class="relative h-44 rounded-xl overflow-hidden group">
                                <img
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    alt="Recuperación Sugerida"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuB8VCHImghNygnLsa-ZE_tPphr3uzTi29mH0QYrt5cwYcxAXlnMbm8-xHOw1wI9EkYV_mYmWNybhrexvw2T2KJj8BSFV48f8v5LwSufQipfcBzfgFfY1bSgqoPt2xS09xxLKfqHOqF-Nn8TGkU9doWQIjh2jp4Ed9IVkKYJQ9llQFXR1EUKa-Y0APNC4ms6d937PGIKpGJTs4gzXqMBiy90iG4qE-9xU37kXYXJdw"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-surface-dim via-surface-dim/40 to-transparent flex flex-col justify-end p-space-md"
                                >
                                    <span class="font-label-sm text-label-sm uppercase text-outline"
                                        >Recuperación Sugerida</span
                                    >
                                    <span
                                        class="font-label-lg text-label-lg text-on-surface font-bold"
                                        >48h descanso neuromuscular</span
                                    >
                                </div>
                            </div>
                        </section>

                        <!-- Analytics & Telemetry Charts Section -->
                        <section
                            class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg mb-space-xl"
                            data-tour="telemetry-charts"
                        >
                            <!-- Telemetry Chart 1: Press de Banca 30d -->
                            <div
                                class="p-space-lg rounded-xl bg-surface-container-low flex flex-col justify-between shadow-xl"
                            >
                                <div class="flex items-start justify-between gap-space-md mb-space-md">
                                    <div class="flex flex-col">
                                        <div
                                            class="flex items-center gap-space-xs text-secondary font-label-sm text-label-sm uppercase tracking-wider"
                                        >
                                            <span class="material-symbols-outlined text-[16px]"
                                                >show_chart</span
                                            >
                                            <span>Peso máximo por día (30d)</span>
                                        </div>
                                        <h4
                                            class="font-headline-md text-headline-md text-on-surface font-bold"
                                        >
                                            Progreso en {{ chartEjercicioNombre }}
                                        </h4>
                                        <span class="font-body-sm text-body-sm text-outline">
                                            Evolución de carga máxima en series principales
                                        </span>
                                    </div>
                                    <span
                                        class="px-space-md py-1 rounded-full bg-secondary-container/50 text-secondary font-label-md text-label-md font-bold shrink-0"
                                    >
                                        Max: {{ maxPesoChart }} kg
                                    </span>
                                </div>

                                <!-- Inline SVG Telemetry Curve 1 -->
                                <div class="relative w-full h-56 pt-space-sm">
                                    <svg
                                        class="w-full h-full overflow-visible"
                                        fill="none"
                                        preserveAspectRatio="none"
                                        viewBox="0 0 500 180"
                                    >
                                        <defs>
                                            <linearGradient
                                                id="chartGrad1"
                                                x1="0"
                                                x2="0"
                                                y1="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stop-color="#8083ff"
                                                    stop-opacity="0.35"
                                                ></stop>
                                                <stop
                                                    offset="100%"
                                                    stop-color="#8083ff"
                                                    stop-opacity="0.0"
                                                ></stop>
                                            </linearGradient>
                                        </defs>
                                        <!-- Grid Lines -->
                                        <line
                                            stroke="#31353e"
                                            stroke-dasharray="4 4"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="40"
                                            y2="40"
                                        ></line>
                                        <line
                                            stroke="#31353e"
                                            stroke-dasharray="4 4"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="90"
                                            y2="90"
                                        ></line>
                                        <line
                                            stroke="#31353e"
                                            stroke-dasharray="4 4"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="140"
                                            y2="140"
                                        ></line>
                                        <!-- Curve fill area -->
                                        <path :d="chart1Telemetry.area" fill="url(#chartGrad1)"></path>
                                        <!-- Smooth spline path -->
                                        <path
                                            :d="chart1Telemetry.line"
                                            stroke="#c0c1ff"
                                            stroke-linecap="round"
                                            stroke-width="3"
                                        ></path>
                                        <!-- Key Metric Nodes -->
                                        <template
                                            v-for="(node, nIdx) in chart1Telemetry.nodes"
                                            :key="nIdx"
                                        >
                                            <template v-if="node.peak">
                                                <circle
                                                    class="animate-pulse"
                                                    :cx="node.x"
                                                    :cy="node.y"
                                                    fill="#8083ff"
                                                    r="7"
                                                ></circle>
                                                <circle
                                                    :cx="node.x"
                                                    :cy="node.y"
                                                    fill="#dfe2ee"
                                                    r="4"
                                                ></circle>
                                            </template>
                                            <circle
                                                v-else
                                                :cx="node.x"
                                                :cy="node.y"
                                                fill="#0f131c"
                                                :r="node.muted ? 3 : 4"
                                                :stroke="node.muted ? '#908fa0' : '#c0c1ff'"
                                                stroke-width="2"
                                            ></circle>
                                        </template>
                                    </svg>
                                </div>
                                <!-- Date Axis -->
                                <div
                                    class="flex items-center justify-between text-outline font-label-sm text-label-sm pt-space-xs"
                                >
                                    <span
                                        v-for="(lbl, lIdx) in chart1Telemetry.labels"
                                        :key="lIdx"
                                        >{{ lbl }}</span
                                    >
                                </div>
                            </div>

                            <!-- Telemetry Chart 2: Global PR History 30d -->
                            <div
                                class="p-space-lg rounded-xl bg-surface-container-low flex flex-col justify-between shadow-xl"
                            >
                                <div class="flex items-start justify-between gap-space-md mb-space-md">
                                    <div class="flex flex-col">
                                        <div
                                            class="flex items-center gap-space-xs text-primary font-label-sm text-label-sm uppercase tracking-wider"
                                        >
                                            <span class="material-symbols-outlined text-[16px]"
                                                >monitoring</span
                                            >
                                            <span>Peso máximo por día (últimos 30 días)</span>
                                        </div>
                                        <h4
                                            class="font-headline-md text-headline-md text-on-surface font-bold"
                                        >
                                            Tu PR diario a lo largo del tiempo
                                        </h4>
                                        <span class="font-body-sm text-body-sm text-outline">
                                            Ponderación volumétrica por semana de descarga y pico
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                                        <span
                                            class="font-label-sm text-label-sm text-on-surface-variant"
                                            >Peso (kg)</span
                                        >
                                    </div>
                                </div>

                                <!-- Inline SVG Telemetry Curve 2 -->
                                <div class="relative w-full h-56 pt-space-sm">
                                    <svg
                                        class="w-full h-full overflow-visible"
                                        fill="none"
                                        preserveAspectRatio="none"
                                        viewBox="0 0 500 180"
                                    >
                                        <defs>
                                            <linearGradient
                                                id="chartGrad2"
                                                x1="0"
                                                x2="0"
                                                y1="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stop-color="#571bc1"
                                                    stop-opacity="0.4"
                                                ></stop>
                                                <stop
                                                    offset="100%"
                                                    stop-color="#571bc1"
                                                    stop-opacity="0.0"
                                                ></stop>
                                            </linearGradient>
                                        </defs>
                                        <!-- Guide markers horizontal -->
                                        <line
                                            stroke="#262a33"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="20"
                                            y2="20"
                                        ></line>
                                        <line
                                            stroke="#262a33"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="60"
                                            y2="60"
                                        ></line>
                                        <line
                                            stroke="#262a33"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="100"
                                            y2="100"
                                        ></line>
                                        <line
                                            stroke="#262a33"
                                            stroke-width="1"
                                            x1="0"
                                            x2="500"
                                            y1="140"
                                            y2="140"
                                        ></line>
                                        <!-- Curve fill area -->
                                        <path :d="chart2Telemetry.area" fill="url(#chartGrad2)"></path>
                                        <!-- Smooth spline path -->
                                        <path
                                            :d="chart2Telemetry.line"
                                            stroke="#d0bcff"
                                            stroke-linecap="round"
                                            stroke-width="3"
                                        ></path>
                                        <!-- Chart Nodes -->
                                        <circle
                                            v-for="(node, nIdx) in chart2Telemetry.nodes"
                                            :key="nIdx"
                                            :cx="node.x"
                                            :cy="node.y"
                                            :fill="
                                                node.isPeak
                                                    ? '#4edea3'
                                                    : node.isAccent
                                                      ? '#571bc1'
                                                      : '#0f131c'
                                            "
                                            :r="node.isPeak ? 6 : node.isAccent ? 5 : 4"
                                            :stroke="node.isPeak ? '#dfe2ee' : '#d0bcff'"
                                            stroke-width="2"
                                        ></circle>
                                    </svg>
                                </div>
                                <!-- Date Axis -->
                                <div
                                    class="flex items-center justify-between text-outline font-label-sm text-label-sm pt-space-xs"
                                >
                                    <span
                                        v-for="(lbl, lIdx) in chart2Telemetry.labels"
                                        :key="lIdx"
                                        >{{ lbl }}</span
                                    >
                                </div>
                            </div>
                        </section>

                        <!-- Session Actions Bar -->
                        <section class="flex flex-col gap-space-md pt-space-xs pb-space-xl">
                            <div class="flex flex-col sm:flex-row items-center gap-space-md">
                                <!-- Dia Anterior Secondary -->
                                <button
                                    v-if="diaIndex > 0"
                                    type="button"
                                    @click="diaAnterior"
                                    class="w-full sm:w-auto px-space-lg py-space-md rounded-xl bg-surface-container-high hover:bg-surface-bright text-on-surface font-label-lg text-label-lg flex items-center justify-center gap-2 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[20px]"
                                        >arrow_back</span
                                    >
                                    <span>Día anterior</span>
                                </button>
                                <!-- Guardar Sesion Primary -->
                                <button
                                    type="button"
                                    @click="guardarSesion"
                                    class="w-full sm:flex-1 px-space-xl py-space-md rounded-xl bg-primary-container hover:bg-primary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(128,131,255,0.35)] transition-all"
                                >
                                    <span class="material-symbols-outlined text-[20px]"
                                        >bookmark</span
                                    >
                                    <span>Guardar sesión</span>
                                </button>
                            </div>

                            <!-- Siguiente Día (si no es el último día) -->
                            <button
                                v-if="!esUltimoDia"
                                type="button"
                                @click="siguienteDia"
                                class="w-full py-space-md px-space-xl rounded-xl bg-surface-container-high hover:bg-surface-bright text-on-surface font-label-lg text-label-lg font-semibold flex items-center justify-center gap-2 transition-all"
                            >
                                <span>Avanzar al siguiente día: {{ diasSiguienteLabel }}</span>
                                <span class="material-symbols-outlined text-[20px]"
                                    >arrow_forward</span
                                >
                            </button>

                            <!-- Finalizar Rutina Full Accent CTA -->
                            <button
                                type="button"
                                @click="finalizarRutina"
                                class="w-full py-space-md px-space-xl rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2.5 shadow-[0_0_24px_rgba(78,222,163,0.35)] hover:shadow-[0_0_32px_rgba(78,222,163,0.5)] transition-all"
                            >
                                <span
                                    class="material-symbols-outlined text-[22px]"
                                    :style="{ fontVariationSettings: '\'FILL\' 1' }"
                                    >check_circle</span
                                >
                                <span>Finalizar rutina</span>
                            </button>
                        </section>
                    </div>
                </div>
            </div>

            <!-- Footer Pro Telemetry -->
            <footer class="w-full bg-surface-container-lowest mt-space-xl">
                <div
                    class="max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-xl flex flex-col md:flex-row items-center justify-between gap-space-md"
                >
                    <div class="flex items-center gap-space-sm">
                        <div
                            class="w-6 h-6 rounded-lg bg-secondary-container flex items-center justify-center"
                        >
                            <span class="material-symbols-outlined text-secondary text-[16px]"
                                >fitness_center</span
                            >
                        </div>
                        <span class="font-label-md text-label-md text-on-surface-variant">
                            © {{ anioActual }} GymApp Pro Telemetry. Todos los derechos reservados.
                        </span>
                    </div>
                    <div class="flex items-center gap-space-lg">
                        <a
                            class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                            href="#soporte"
                            >Soporte</a
                        >
                        <a
                            class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                            href="#terminos"
                            >Términos del Servicio</a
                        >
                        <a
                            class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                            href="#privacidad"
                            >Privacidad</a
                        >
                    </div>
                </div>
            </footer>
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
        <Teleport to="body">
            <Transition name="sheet-fade">
                <div
                    v-if="showDaySummary"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm"
                    @click.self="showDaySummary = false"
                >
                    <div
                        class="sheet-content w-full sm:max-w-md bg-surface-container sm:rounded-3xl rounded-t-3xl rounded-b-none sm:rounded-b-3xl shadow-2xl border border-surface-variant overflow-hidden max-h-[90vh] flex flex-col"
                    >
                        <div class="sm:hidden pt-2 pb-1 flex justify-center">
                            <div class="w-10 h-1.5 rounded-full bg-surface-variant"></div>
                        </div>

                        <div
                            class="bg-gradient-to-br from-tertiary-container to-tertiary px-6 py-5 text-on-tertiary"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider opacity-90"
                            >
                                <span>✓</span>
                                <span>Día completado</span>
                            </div>
                            <h3 class="mt-1 text-2xl font-black">
                                {{ daySummary.diaLabel }}
                            </h3>
                        </div>

                        <div class="px-6 py-5 space-y-4 overflow-y-auto">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-xl bg-primary-container/15 px-3 py-3">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-primary"
                                    >
                                        Series
                                    </p>
                                    <p
                                        class="mt-0.5 text-2xl font-black text-on-surface tabular-nums"
                                    >
                                        {{ daySummary.completadas }}/{{ daySummary.total }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-secondary-container/25 px-3 py-3">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-secondary"
                                    >
                                        Volumen
                                    </p>
                                    <p
                                        class="mt-0.5 text-2xl font-black text-on-surface tabular-nums"
                                    >
                                        {{ formatVolumen(daySummary.volumen) }}
                                    </p>
                                    <p class="text-[10px] text-outline">kg totales</p>
                                </div>
                                <div class="rounded-xl bg-tertiary/15 px-3 py-3">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-tertiary"
                                    >
                                        PRs
                                    </p>
                                    <p
                                        class="mt-0.5 text-2xl font-black text-tertiary tabular-nums"
                                    >
                                        {{ daySummary.prs }}
                                    </p>
                                    <p class="text-[10px] text-tertiary/80">récords</p>
                                </div>
                            </div>

                            <div
                                v-if="daySummary.mejorSet"
                                class="rounded-xl bg-tertiary/10 border border-tertiary/30 px-4 py-3"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-tertiary uppercase tracking-wider"
                                >
                                    <span>🏆</span>
                                    <span>Mejor set</span>
                                </div>
                                <p class="mt-1 text-base font-bold text-on-surface">
                                    {{ daySummary.mejorSet.ejercicio }}
                                </p>
                                <p class="text-sm font-semibold text-tertiary tabular-nums">
                                    {{ daySummary.mejorSet.peso }} kg ×
                                    {{ daySummary.mejorSet.reps }} reps
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-surface-container-lowest/60 border-t border-surface-variant flex gap-3 pb-[max(env(safe-area-inset-bottom),1rem)]"
                        >
                            <button
                                type="button"
                                @click="showDaySummary = false"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-surface-variant bg-surface-container-low text-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors"
                            >
                                Quedarme acá
                            </button>
                            <button
                                v-if="daySummary.tieneSiguiente"
                                type="button"
                                @click="avanzarAlSiguienteDia"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary text-sm font-bold shadow-md transition-all"
                            >
                                Ir al {{ daySummary.siguienteLabel }} →
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="finalizarRutinaDesdeResumen"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-tertiary hover:bg-tertiary-fixed-dim text-on-tertiary text-sm font-bold shadow-md transition-all"
                            >
                                🎉 Finalizar rutina
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.sheet-fade-enter-active,
.sheet-fade-leave-active {
    transition: opacity 0.25s ease;
}
.sheet-fade-enter-from,
.sheet-fade-leave-to {
    opacity: 0;
}

.sheet-fade-enter-active .sheet-content,
.sheet-fade-leave-active .sheet-content {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.sheet-fade-enter-from .sheet-content,
.sheet-fade-leave-to .sheet-content {
    transform: translateY(100%);
}

@media (min-width: 640px) {
    .sheet-fade-enter-from .sheet-content,
    .sheet-fade-leave-to .sheet-content {
        transform: translateY(0) scale(0.96);
    }
}
</style>
