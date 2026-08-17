<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <Breadcrumbs :items="[
        { label: 'Inicio', href: '/dashboard' },
        { label: 'Historial de entrenamiento' },
      ]" />
      <HistorialHeader
        :isTrainerOrAdmin="isTrainerOrAdmin"
        :alumnos="alumnos"
        :selectedAlumnoId="selectedAlumnoId"
        :activeTab="activeTab"
        :showKeyExercisesTab="isTrainerOrAdmin || hasTrainer"
        :can-export="historial.length > 0"
        :stats="headerStats"
        @alumno-change="onAlumnoChange"
        @tab-change="activeTab = $event"
        @export-csv="exportarCSV"
        @export-pdf="exportarPDF"
      />

      <!-- Pull-to-refresh indicator (mobile) -->
      <HistorialPullRefresh
        :offset="pullOffset"
        :refreshing="isRefreshing"
        class="md:hidden"
      />

      <!-- Loading / empty states -->
      <div v-if="loading" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-4">
          <BaseSkeleton variant="stat-card" :count="4" />
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
          <BaseSkeleton variant="text" :count="6" />
        </div>
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

      <div v-else-if="!resumenEjercicios.length">
        <EmptyState
          emoji="📊"
          title="Aún no hay historial"
          description="Cuando registres tus primeras series de entrenamiento, vas a ver acá la evolución de tu progreso."
        >
          <template #cta>
            <a
              href="/dashboard"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-all shadow-md"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Empezar a registrar
            </a>
          </template>
        </EmptyState>
      </div>

      <div v-else class="space-y-6 animate-fadeIn">
        <HistorialMatrix
          v-show="activeTab === 'matrix'"
          :pivotData="pivotData"
          :dateSortAsc="dateSortAsc"
          @toggle-sort="toggleDateSort"
        />

        <HistorialCalendar
          v-show="activeTab === 'calendar'"
          :historial="historial"
        />

        <HistorialEvolution
          v-show="activeTab === 'evolution'"
          :tablaProgreso="tablaProgreso"
          :resumenEjercicios="resumenEjercicios"
          :globalMaxWeight="globalMaxWeight"
        />

        <RmCalculator
          v-show="activeTab === 'rm_calculator'"
          :calculator="calculator"
          :rmFormula="rmFormula"
          :historical1RMs="historical1RMs"
          @update:calculator="calculator = $event"
          @update:rmFormula="rmFormula = $event"
        />

        <HistorialComparison
          v-show="activeTab === 'comparison'"
          :historial="historial"
        />

        <KeyExercises
          v-show="activeTab === 'key_exercises'"
          :isTrainerOrAdmin="isTrainerOrAdmin"
          :ejerciciosClave="ejerciciosClave"
          :todosEjercicios="todosEjercicios"
          :keyExercisesLoading="keyExercisesLoading"
          :savingKeyExercise="savingKeyExercise"
          :rmFormula="rmFormula"
          :getExercise1RMTimeline="getExercise1RMTimeline"
          @save-key="saveKeyExercise"
          @delete-key="deleteKeyExercise"
          @save-notes="saveEditingNotes"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

import HistorialHeader from './historial/HistorialHeader.vue';
import HistorialMatrix from './historial/HistorialMatrix.vue';
import HistorialEvolution from './historial/HistorialEvolution.vue';
import RmCalculator from './historial/RmCalculator.vue';
import KeyExercises from './historial/KeyExercises.vue';
import HistorialCalendar from './historial/HistorialCalendar.vue';
import HistorialComparison from './historial/HistorialComparison.vue';
import HistorialPullRefresh from './historial/HistorialPullRefresh.vue';
import BaseSkeleton from './BaseSkeleton.vue';
import EmptyState from './EmptyState.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';
import { usePullToRefresh } from '../composables/usePullToRefresh';

Chart.register(...registerables);

const toast = useToast();
const auth = useAuthStore();
const { isStaff } = storeToRefs(auth);

// === State ===
const loading = ref(true);
const historial = ref([]);
const isTrainerOrAdmin = ref(false);
const alumnos = ref([]);
const selectedAlumnoId = ref(null);
const hasTrainer = ref(false);
const activeTab = ref('matrix');
const dateSortAsc = ref(true);

const ejerciciosClave = ref([]);
const todosEjercicios = ref([]);
const keyExercisesLoading = ref(false);
const newKeyExercise = ref({ nombre: '', notas: '' });
const savingKeyExercise = ref(false);
const editingNotesId = ref(null);
const editingNotesValue = ref('');
const chartInstances = {};

const calculator = ref({ weight: 80, reps: 5, formula: 'epley' });
const rmFormula = ref('epley');

// === Helpers ===
const formatDate = (value) => {
    const date = new Date(value + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short' }).format(date);
};

const calculate1RMValue = (w, r, formula) => {
    if (!w || !r || w <= 0 || r <= 0) return 0;
    if (formula === 'epley') return w * (1 + r / 30);
    return (100 * w) / (101.3 - 2.6712 * r);
};

// === Data fetching ===
const fetchUserInfo = async () => {
    try {
        const u = await fetchUser();
        hasTrainer.value = !!u.has_trainer;
        isTrainerOrAdmin.value = isStaff.value;
        if (isTrainerOrAdmin.value) await fetchAlumnos();
    } catch (err) {
        console.error('Error fetching user info:', err);
    }
};

const fetchAlumnos = async () => {
    try {
        const res = await axios.get('/api/trainer/alumnos');
        alumnos.value = res.data;
        if (alumnos.value.length > 0) selectedAlumnoId.value = alumnos.value[0].id;
    } catch (err) {
        console.error('Error fetching alumnos:', err);
    }
};

const onAlumnoChange = async () => {
    newKeyExercise.value = { nombre: '', notas: '' };
    await Promise.all([loadHistorial(), loadKeyExercises()]);
};

const loadHistorial = async () => {
    loading.value = true;
    try {
        const params = {};
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const response = await axios.get('/api/historial', { params });
        historial.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        console.error('Error cargando historial:', error);
        historial.value = [];
    } finally {
        loading.value = false;
    }
};

const loadKeyExercises = async () => {
    if (!isTrainerOrAdmin.value && !hasTrainer.value) return;
    keyExercisesLoading.value = true;
    try {
        const params = {};
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const res = await axios.get('/api/ejercicios-clave', { params });
        ejerciciosClave.value = res.data.ejercicios_clave || [];
        todosEjercicios.value = res.data.todos_ejercicios || [];
        if (activeTab.value === 'key_exercises') nextTick(() => initKeyCharts());
    } catch (err) {
        console.error('Error loading key exercises:', err);
    } finally {
        keyExercisesLoading.value = false;
    }
};

const saveKeyExercise = async (payload) => {
    if (!payload.nombre.trim()) return;
    savingKeyExercise.value = true;
    try {
        await axios.post('/api/ejercicios-clave', {
            user_id: selectedAlumnoId.value,
            ejercicio_nombre: payload.nombre.trim(),
            notas_trainer: payload.notas.trim() || null,
        });
        newKeyExercise.value = { nombre: '', notas: '' };
        await loadKeyExercises();
        toast.success('Ejercicio clave designado');
    } catch (err) {
        console.error('Error saving key exercise:', err);
        toast.error('Error al guardar el ejercicio clave');
    } finally {
        savingKeyExercise.value = false;
    }
};

const deleteKeyExercise = async (id) => {
    const confirmed = await toast.confirm(
        '¿Estás seguro de que deseas eliminar este ejercicio clave?',
        { title: 'Eliminar ejercicio clave', confirmLabel: 'Sí, eliminar', type: 'error' }
    );
    if (!confirmed) return;

    // Snapshot para undo (buscamos en ambas posibles fuentes)
    const idx = keyExercises.value.findIndex(e => e.id === id);
    const snapshot = idx >= 0 ? { ...keyExercises.value[idx] } : null;

    await useUndoable({
        message: 'Ejercicio clave eliminado',
        apply: () => {
            keyExercises.value = keyExercises.value.filter(e => e.id !== id);
        },
        undo: () => {
            if (!snapshot) return;
            if (idx >= 0 && idx <= keyExercises.value.length) {
                keyExercises.value.splice(idx, 0, snapshot);
            } else {
                keyExercises.value.push(snapshot);
            }
        },
        commit: () => axios.delete(`/api/ejercicios-clave/${id}`),
        onError: (err) => console.error('Error deleting key exercise:', err),
    });
};

const saveEditingNotes = async ({ ej, value }) => {
    try {
        await axios.post('/api/ejercicios-clave', {
            user_id: ej.user_id,
            ejercicio_nombre: ej.ejercicio_nombre,
            notas_trainer: value.trim() || null,
        });
        editingNotesId.value = null;
        editingNotesValue.value = '';
        await loadKeyExercises();
        toast.success('Notas actualizadas');
    } catch (err) {
        console.error('Error updating notes:', err);
        toast.error('Error al actualizar las notas');
    }
};

// === Computed aggregations ===
const pivotData = computed(() => {
    if (!historial.value || !historial.value.length) return { dates: [], rows: [] };

    const dateMap = new Map();
    historial.value.forEach((row) => {
        if (!dateMap.has(row.fecha)) dateMap.set(row.fecha, formatDate(row.fecha));
    });

    const sortedDates = [...dateMap.keys()].sort((a, b) => {
        const dateA = new Date(a);
        const dateB = new Date(b);
        return dateSortAsc.value ? dateA - dateB : dateB - dateA;
    });

    const exercises = [...new Set(historial.value.map((row) => row.ejercicio_nombre))].sort();

    const rows = exercises.map((exerciseName) => {
        const exerciseRows = historial.value.filter((row) => row.ejercicio_nombre === exerciseName);
        const dateWeights = {};
        sortedDates.forEach((date) => {
            const dayRows = exerciseRows.filter((row) => row.fecha === date && row.peso !== null);
            if (dayRows.length > 0) {
                const weights = dayRows.map((row) => Number(row.peso));
                const maxWeight = Math.max(...weights);
                dateWeights[date] = maxWeight > 0 ? `${maxWeight} kg` : '-';
            } else {
                dateWeights[date] = '-';
            }
        });
        const superserie_grupo = exerciseRows.find((row) => row.superserie_grupo !== null)?.superserie_grupo || null;
        return { name: exerciseName, weights: dateWeights, superserie_grupo };
    });

    return {
        dates: sortedDates.map((d) => ({ raw: d, formatted: dateMap.get(d) })),
        rows,
    };
});

const globalMaxWeight = computed(() => {
    const values = historial.value
        .map((row) => Number(row.peso))
        .filter((peso) => Number.isFinite(peso) && peso > 0);
    return values.length ? Math.max(...values, 10) : 10;
});

const totalSeries = computed(() => historial.value.length);

const pesoPromedioGlobal = computed(() => {
    const values = historial.value
        .map((row) => Number(row.peso))
        .filter((peso) => Number.isFinite(peso) && peso > 0);
    if (!values.length) return '0.0';
    return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

const repsPromedioGlobal = computed(() => {
    const values = historial.value
        .map((row) => Number(row.reps_realizadas))
        .filter((reps) => Number.isFinite(reps) && reps >= 0);
    if (!values.length) return '0.0';
    return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

const headerStats = computed(() => ({
    ejercicios: resumenEjercicios.value.length,
    totalSeries: totalSeries.value,
    pesoPromedio: pesoPromedioGlobal.value,
    repsPromedio: repsPromedioGlobal.value,
}));

// === Exportar historial a CSV ===
// Convierte el historial completo del alumno (o del usuario logueado) a CSV descargable.
const exportarCSV = () => {
    if (!historial.value.length) {
        toast.warning('No hay historial para exportar.');
        return;
    }

    const headers = ['Fecha', 'Rutina', 'Día', 'Ejercicio', 'Serie #', 'Reps min', 'Reps max', 'Reps hechas', 'Peso (kg)', 'Descanso (min)', 'Completado', 'Superserie'];
    const escape = (val) => {
        const s = val == null ? '' : String(val);
        // Escapar comillas dobles y envolver en comillas si tiene comas/comillas/saltos
        if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
        return s;
    };

    const rows = historial.value.map((row) => [
        row.fecha || '',
        row.rutina || '',
        row.dia || '',
        row.ejercicio_nombre || '',
        row.series_numero ?? '',
        row.reps_min ?? '',
        row.reps_max ?? '',
        row.reps_realizadas ?? '',
        row.peso ?? '',
        row.descanso_min ?? '',
        row.completado ? 'Sí' : 'No',
        row.superserie_grupo ?? '',
    ].map(escape).join(','));

    const csv = '\ufeff' + [headers.map(escape).join(','), ...rows].join('\n'); // BOM para Excel
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const alumnoTag = isTrainerOrAdmin.value && selectedAlumnoId.value
        ? `-alumno-${selectedAlumnoId.value}`
        : '';
    const filename = `historial${alumnoTag}-${new Date().toISOString().split('T')[0]}.csv`;

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    toast.success(`Historial exportado (${historial.value.length} registros).`);
};

// === Exportar historial a PDF ===
// Genera un PDF profesional con resumen + detalle. Lazy-load jspdf para
// no penalizar el bundle inicial. autoTable se importa del mismo paquete.
// NOTA: NO usar Promise.all con dos import() dinámicos: Vite genera un
// helper __vitePreload que se acopla estáticamente al grafo. Mejor
// cargar en serie (o usar un único import del bundle compilado).
const exportarPDF = async () => {
    if (!historial.value.length) {
        toast.warning('No hay historial para exportar.');
        return;
    }

    try {
        toast.info('Generando PDF…');
        // jspdf 4: ahora ESM puro, así que usamos named import `jsPDF`
        // (en vez de default). Si tu build se queja, probá:
        //   const { jsPDF: jsPDFClass } = await import('jspdf');
        //   const doc = new jsPDFClass(opts);
        const { jsPDF } = await import('jspdf');
        // jspdf-autotable 5: pasó a named export `autoTable` (antes era
        // default export). El fallback `.default || ...` ya no hace falta.
        const { autoTable } = await import('jspdf-autotable');

        const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        const alumnoTag = isTrainerOrAdmin.value && selectedAlumnoId.value
            ? `-alumno-${selectedAlumnoId.value}`
            : '';
        const filename = `historial${alumnoTag}-${new Date().toISOString().split('T')[0]}.pdf`;

        // Header
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(20);
        doc.setTextColor(67, 56, 202); // indigo-700
        doc.text('Historial de entrenamiento', 40, 50);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(75, 85, 99);
        const fechaTexto = new Date().toLocaleString('es-AR', { dateStyle: 'long', timeStyle: 'short' });
        doc.text(`Generado: ${fechaTexto}`, 40, 68);

        // Stats resumen
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text('Resumen', 40, 100);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text(
            `Ejercicios rastreados: ${headerStats.value.ejercicios}   ·   ` +
            `Series registradas: ${headerStats.value.totalSeries}   ·   ` +
            `Peso promedio: ${headerStats.value.pesoPromedio} kg   ·   ` +
            `Reps promedio: ${headerStats.value.repsPromedio}`,
            40, 118,
        );

        // Tabla
        autoTable(doc, {
            startY: 140,
            head: [['Fecha', 'Rutina', 'Día', 'Ejercicio', 'Serie', 'Reps min', 'Reps max', 'Reps hechas', 'Peso (kg)', 'Descanso (min)', 'OK', 'SS']],
            body: historial.value.map((row) => [
                row.fecha || '',
                row.rutina || '',
                row.dia || '',
                row.ejercicio_nombre || '',
                row.series_numero ?? '',
                row.reps_min ?? '',
                row.reps_max ?? '',
                row.reps_realizadas ?? '',
                row.peso ?? '',
                row.descanso_min ?? '',
                row.completado ? 'Sí' : 'No',
                row.superserie_grupo ?? '',
            ]),
            styles: { fontSize: 8, cellPadding: 4 },
            headStyles: { fillColor: [67, 56, 202], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { cellWidth: 65 },
                3: { fontStyle: 'bold' },
                8: { halign: 'right' },
            },
            didDrawPage: (data) => {
                // Footer con número de página
                const pageStr = `Página ${doc.internal.getCurrentPageInfo().pageNumber}`;
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text(
                    pageStr,
                    doc.internal.pageSize.width - 60,
                    doc.internal.pageSize.height - 20,
                );
            },
        });

        doc.save(filename);
        toast.success(`PDF generado (${historial.value.length} registros).`);
    } catch (e) {
        console.error('[exportPDF]', e);
        toast.apiError(e, 'Error al generar el PDF.');
    }
};

// === Pull-to-refresh (Mejora 1.9) ===
// Usamos window como target: la página scrollea en window, no en un div interno.
const loadHistorialWithFeedback = async () => {
    await loadHistorial();
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, loadHistorialWithFeedback);

const slugify = (value) => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const resumenEjercicios = computed(() => {
    const grouped = new Map();
    historial.value.forEach((row) => {
        if (!grouped.has(row.ejercicio_nombre)) grouped.set(row.ejercicio_nombre, []);
        grouped.get(row.ejercicio_nombre).push(row);
    });

    return [...grouped.entries()].map(([nombre, rows]) => {
        const timelineMap = new Map();
        rows.forEach((row) => {
            const key = row.fecha;
            if (!timelineMap.has(key)) timelineMap.set(key, []);
            timelineMap.get(key).push(row);
        });

        const timeline = [...timelineMap.entries()]
            .sort((a, b) => new Date(a[0]) - new Date(b[0]))
            .map(([fecha, dayRows]) => {
                const weights = dayRows.map((row) => Number(row.peso)).filter((peso) => Number.isFinite(peso) && peso > 0);
                const reps = dayRows.map((row) => Number(row.reps_realizadas)).filter((value) => Number.isFinite(value) && value >= 0);
                const avgPeso = weights.length ? weights.reduce((sum, v) => sum + v, 0) / weights.length : 0;
                const avgReps = reps.length ? reps.reduce((sum, v) => sum + v, 0) / reps.length : 0;
                const sample = dayRows[0];
                return {
                    fecha,
                    fechaLabel: formatDate(fecha),
                    diaLabel: sample?.dia || 'Día',
                    avgPeso,
                    pesoPromedio: avgPeso.toFixed(1),
                    repsPromedio: avgReps.toFixed(1),
                };
            });

        const weights = rows.map((row) => Number(row.peso)).filter((peso) => Number.isFinite(peso) && peso > 0);
        const reps = rows.map((row) => Number(row.reps_realizadas)).filter((value) => Number.isFinite(value) && value >= 0);
        const totalPeso = weights.reduce((sum, v) => sum + v, 0);
        const totalReps = reps.reduce((sum, v) => sum + v, 0);

        return {
            nombre,
            slug: slugify(nombre),
            totalSeries: rows.length,
            totalSesiones: timeline.length,
            pesoPromedio: weights.length ? (totalPeso / weights.length).toFixed(1) : '0.0',
            repsPromedio: reps.length ? (totalReps / reps.length).toFixed(1) : '0.0',
            ultimoPeso: weights.length ? weights[weights.length - 1].toFixed(1) : '0.0',
            pesoMinimo: weights.length ? Math.min(...weights).toFixed(1) : '0.0',
            pesoMaximo: weights.length ? Math.max(...weights).toFixed(1) : '0.0',
            fechaInicial: timeline.length ? timeline[0].fechaLabel : '-',
            fechaFinal: timeline.length ? timeline[timeline.length - 1].fechaLabel : '-',
            timeline,
            sesiones: timeline.map((sesion) => ({
                fecha: sesion.fechaLabel,
                dia: sesion.diaLabel,
                pesoPromedio: sesion.pesoPromedio,
                repsPromedio: sesion.repsPromedio,
            })),
        };
    });
});

const tablaProgreso = computed(() => {
    const result = [];
    resumenEjercicios.value.forEach((ejercicio) => {
        ejercicio.timeline.forEach((sesion) => {
            const dayRows = historial.value.filter(
                (row) => row.ejercicio_nombre === ejercicio.nombre && row.fecha === sesion.fecha
            );
            const weights = dayRows.map((row) => Number(row.peso)).filter((peso) => Number.isFinite(peso) && peso > 0);
            const maxWeight = weights.length ? Math.max(...weights) : 0;
            const superserie_grupo = dayRows.find((row) => row.superserie_grupo !== null)?.superserie_grupo || null;
            result.push({
                nombre: ejercicio.nombre,
                fecha: sesion.fechaLabel,
                dia: sesion.diaLabel,
                rawFecha: new Date(sesion.fecha),
                maxWeight: maxWeight.toFixed(1),
                avgWeight: sesion.pesoPromedio,
                seriesCount: dayRows.length,
                superserie_grupo,
            });
        });
    });
    return result.sort((a, b) => b.rawFecha - a.rawFecha);
});

const historical1RMs = computed(() => {
    if (!historial.value || !historial.value.length) return [];
    const exerciseMaxes = {};
    historial.value.forEach((row) => {
        const w = parseFloat(row.peso);
        const r = parseInt(row.reps_realizadas);
        if (!w || !r || w <= 0 || r <= 0) return;
        const rmVal = calculate1RMValue(w, r, rmFormula.value);
        if (!exerciseMaxes[row.ejercicio_nombre] || rmVal > exerciseMaxes[row.ejercicio_nombre].rm) {
            exerciseMaxes[row.ejercicio_nombre] = {
                name: row.ejercicio_nombre,
                weight: w,
                reps: r,
                rm: rmVal,
                date: formatDate(row.fecha),
            };
        }
    });
    return Object.values(exerciseMaxes).sort((a, b) => b.rm - a.rm);
});

// === Chart helpers (used by KeyExercises) ===
const getExercise1RMTimeline = (exerciseName) => {
    const rows = historial.value.filter((row) => row.ejercicio_nombre === exerciseName);
    if (!rows.length) return [];
    const dateMap = {};
    rows.forEach((row) => {
        const w = parseFloat(row.peso);
        const r = parseInt(row.reps_realizadas);
        if (!w || !r || w <= 0 || r <= 0) return;
        const rmVal = calculate1RMValue(w, r, rmFormula.value);
        if (!dateMap[row.fecha] || rmVal > dateMap[row.fecha].rm) {
            dateMap[row.fecha] = { fecha: row.fecha, rm: rmVal, weight: w, reps: r, dia: row.dia || 'Día' };
        }
    });
    return Object.values(dateMap).sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
};

const initKeyCharts = () => {
    Object.keys(chartInstances).forEach((key) => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            delete chartInstances[key];
        }
    });

    ejerciciosClave.value.forEach((ej) => {
        const canvasId = 'keyChart-' + ej.id;
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        const timeline = getExercise1RMTimeline(ej.ejercicio_nombre);
        if (timeline.length === 0) return;

        const labels = timeline.map((t) => {
            const date = new Date(t.fecha + 'T00:00:00');
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
        });
        const dataValues = timeline.map((t) => parseFloat(t.rm.toFixed(1)));

        const gridColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';
        const textColor = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563';

        const canvasCtx = ctx.getContext('2d');
        const gradient = canvasCtx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        chartInstances[ej.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: '1RM Estimado (kg)',
                    data: dataValues,
                    borderColor: '#6366f1',
                    borderWidth: 2.5,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#4f46e5',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 11, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 8,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: (context) => {
                                const point = timeline[context.dataIndex];
                                return ` 1RM: ${context.parsed.y} kg (${point.weight} kg x ${point.reps} reps)`;
                            },
                        },
                    },
                },
                scales: {
                    y: { grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, font: { size: 10, family: 'ui-sans-serif, system-ui' } } },
                    x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10, family: 'ui-sans-serif, system-ui' } } },
                },
            },
        });
    });
};

// === UI actions ===
const toggleDateSort = () => { dateSortAsc.value = !dateSortAsc.value; };

watch(activeTab, (newTab) => {
    if (newTab === 'key_exercises') nextTick(() => initKeyCharts());
});

watch(rmFormula, () => {
    if (activeTab.value === 'key_exercises') nextTick(() => initKeyCharts());
});

onMounted(async () => {
    await fetchUserInfo();
    if (!isTrainerOrAdmin.value) {
        await Promise.all([loadHistorial(), loadKeyExercises()]);
    } else if (selectedAlumnoId.value) {
        await Promise.all([loadHistorial(), loadKeyExercises()]);
    } else {
        loading.value = false;
    }
});

// === Cleanup: destruir todos los charts cuando se desmonta el componente ===
// Sin esto, las instancias de Chart.js se quedan vivas en memoria y mantienen
// referencias a los <canvas> y a sus datos, causando leaks en SPA navigation.
onBeforeUnmount(() => {
    Object.keys(chartInstances).forEach((key) => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            delete chartInstances[key];
        }
    });
});
</script>
