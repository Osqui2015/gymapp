<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-900 dark:to-indigo-900/40 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <Breadcrumbs :items="[
        { label: 'Inicio', href: '/dashboard' },
        { label: 'Progreso & Evolución' },
      ]" />
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
            <span>📊</span> Progreso & Evolución
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Controla tus medidas, metas personales y logros desbloqueados</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            @click="exportarProgresoPdf"
            :disabled="exportandoPdf"
            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold text-sm shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            :title="exportandoPdf ? 'Generando PDF...' : 'Descargar reporte en PDF'"
          >
            <svg v-if="!exportandoPdf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8M4 12a8 8 0 008 8" />
            </svg>
            {{ exportandoPdf ? 'Generando...' : 'Exportar PDF' }}
          </button>
          <a
            href="/dashboard"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-700 font-semibold text-sm shadow-sm transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver al Dashboard
          </a>
        </div>
      </div>

      <!-- Body weight chart con goal line (Fase 1.2) -->
      <div class="mb-6">
        <BodyWeightChart
          :data="weightChart.data"
          :goal="weightChart.goal"
          :latest="weightChart.latest"
          :delta="weightChart.delta"
          :direction="weightChart.direction"
          :total-change="weightChart.totalChange"
          @update:goal="onUpdateGoal"
        />
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 gap-6 overflow-x-auto scrollbar-hide">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'pb-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap',
          ]"
        >
          <span>{{ tab.emoji }}</span> {{ tab.label }}
        </button>
      </div>

      <!-- Medidas -->
      <div v-show="activeTab === 'medidas'">
        <MedidasTab
          :progresos="progresos"
          :ultimoRegistro="ultimoRegistro"
          :puedeRegistrar="puedeRegistrar"
          :diasRestantesParaRegistrar="diasRestantesParaRegistrar"
          :guardando="guardando"
          :form="form"
          :metricaGrafica="metricaGrafica"
          :formatFecha="formatFecha"
          @save="guardarProgreso"
          @ver-detalle="verDetalle"
          @update:metricaGrafica="metricaGrafica = $event"
        />
      </div>

      <!-- Metas -->
      <MetasTab
        v-show="activeTab === 'metas'"
        :metas="metas"
        :creandoMeta="creandoMeta"
        @crear="crearMeta"
        @toggle="toggleMetaCompletada"
        @eliminar="eliminarMeta"
      />

      <!-- Fotos de progreso (galería cronológica) -->
      <div v-show="activeTab === 'fotos'">
        <FotosTab />
      </div>

      <!-- Logros -->
      <LogrosTab
        v-show="activeTab === 'logros'"
        :logros="logros"
        :logrosStats="logrosStats"
        :formatFechaMedalla="formatFechaMedalla"
      />

      <!-- Modal de Detalle -->
      <DetalleMedidaModal
        :modal="modalDetalle"
        :formatFecha="formatFecha"
        @cerrar="cerrarModal"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { useConfetti } from '../composables/useConfetti';
import { useProgresoPdf } from '../composables/useProgresoPdf';

import MedidasTab from './progreso/MedidasTab.vue';
import MetasTab from './progreso/MetasTab.vue';
import LogrosTab from './progreso/LogrosTab.vue';
import FotosTab from './progreso/FotosTab.vue';
import DetalleMedidaModal from './progreso/DetalleMedidaModal.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateLong, formatDateMedium, formatDateShort } = useFormatters();

// BodyWeightChart carga chart.js dinámicamente (vendor-chart, ya cacheado
// por otros componentes). Lo cargamos async para que ProgresoContent no
// arrastre vendor-chart en su grafo eager.
const BodyWeightChart = defineAsyncComponent(
    () => import('./BodyWeightChart.vue')
);

const toast = useToast();
const showNotification = (message, type = 'success') => toast.add(message, type);
const { bigCelebration, celebrate, mini } = useConfetti();

// NOTA: chart.js (vendor-chart ~206 kB) se importa dinámicamente dentro de
// initChart(). Solo se carga cuando el usuario entra a la tab Medidas y hay
// datos para graficar. Aplaza ~206 kB del initial load de /progreso.

const activeTab = ref('medidas');
const progresos = ref([]);
const puedeRegistrar = ref(true);
const ultimoRegistro = ref(null);
const guardando = ref(false);
const metricaGrafica = ref('peso');

const metas = ref([]);
const creandoMeta = ref(false);

const logros = ref([]);
const logrosStats = ref(null);

let chartInstance = null;

// === Body weight chart (Fase 1.2) ===
const weightChart = ref({
    data: [],
    goal: null,
    latest: null,
    delta: null,
    direction: null,
    totalChange: null,
});

const cargarWeightChart = async () => {
    try {
        const res = await axios.get('/api/progreso/weight-chart');
        weightChart.value = {
            data: res.data.data || [],
            goal: res.data.goal,
            latest: res.data.latest,
            delta: res.data.delta_to_goal,
            direction: res.data.goal_direction,
            totalChange: res.data.total_change,
        };
    } catch (err) {
        console.error('[ProgresoContent] Error cargando weight chart:', err);
    }
};

const onUpdateGoal = async (newGoal) => {
    try {
        await axios.patch('/api/progreso/goal', { peso_objetivo: newGoal });
        weightChart.value.goal = newGoal;
        const latest = weightChart.value.latest;
        if (latest && newGoal) {
            const delta = Math.round((latest.peso - newGoal) * 100) / 100;
            weightChart.value.delta = delta;
            weightChart.value.direction = newGoal < latest.peso ? 'down' : newGoal > latest.peso ? 'up' : null;
        } else {
            weightChart.value.delta = null;
            weightChart.value.direction = null;
        }
        toast.success(newGoal ? `Objetivo actualizado a ${newGoal} kg` : 'Objetivo eliminado');
    } catch (err) {
        console.error('[ProgresoContent] Error actualizando goal:', err);
        toast.error('No se pudo guardar el objetivo');
    }
};

const form = ref({
    peso: '',
    altura: '',
    edad: '',
    sexo: '',
    cuello: '',
    hombros: '',
    pecho: '',
    brazos: '',
    cintura: '',
    cadera: '',
    muslos: '',
    pantorrillas: '',
});

const modalDetalle = ref({
    mostrar: false,
    progreso: {},
    comparacion: {},
});

const tabs = [
    { id: 'medidas', emoji: '📏', label: 'Medidas Corporales' },
    { id: 'metas',   emoji: '🎯', label: 'Metas Personales' },
    { id: 'fotos',   emoji: '📸', label: 'Galería' },
    { id: 'logros',  emoji: '🏆', label: 'Medallas y Logros' },
];

// === Confetti (deprecated local; ahora viene de useConfetti) ===
// Kept as alias for backward compat with existing call sites
const triggerCelebration = celebrate;

// === Computed ===
const diasRestantesParaRegistrar = computed(() => {
    if (!ultimoRegistro.value) return 0;
    const ultimo = new Date(ultimoRegistro.value.fecha);
    const hoy = new Date();
    const diasPasados = Math.floor((hoy - ultimo) / (1000 * 60 * 60 * 24));
    return Math.max(0, 14 - diasPasados);
});

// === Format ===
const formatFecha = (dateStr) => {
    if (!dateStr) return '';
    return formatDateLong(dateStr);
};

const formatFechaMedalla = (dateStr) => {
    if (!dateStr) return '';
    return formatDateMedium(dateStr);
};

// === Data fetching ===
const cargarProgresos = async () => {
    try {
        const response = await axios.get('/api/progreso');
        progresos.value = response.data.progresos || [];
        ultimoRegistro.value = response.data.ultimo || null;
        puedeRegistrar.value = response.data.puede_registrar;

        if (ultimoRegistro.value) {
            form.value = {
                peso: ultimoRegistro.value.peso || '',
                altura: ultimoRegistro.value.altura || '',
                edad: ultimoRegistro.value.edad || '',
                sexo: ultimoRegistro.value.sexo || '',
                cuello: ultimoRegistro.value.cuello || '',
                hombros: ultimoRegistro.value.hombros || '',
                pecho: ultimoRegistro.value.pecho || '',
                brazos: ultimoRegistro.value.brazos || '',
                cintura: ultimoRegistro.value.cintura || '',
                cadera: ultimoRegistro.value.cadera || '',
                muslos: ultimoRegistro.value.muslos || '',
                pantorrillas: ultimoRegistro.value.pantorrillas || '',
            };
        }

        nextTick(() => initChart());
    } catch (error) {
        console.error('Error al cargar progresos:', error);
    }
};

const cargarMetas = async () => {
    try {
        const response = await axios.get('/api/metas');
        metas.value = response.data || [];
    } catch (error) {
        console.error('Error al cargar metas:', error);
    }
};

const cargarLogros = async () => {
    try {
        const response = await axios.get('/api/logros');
        logros.value = response.data.logros || [];
        logrosStats.value = response.data.stats || null;
    } catch (error) {
        console.error('Error al cargar logros:', error);
    }
};

// === Actions ===
const guardarProgreso = async (formData) => {
    const tieneDatos = Object.keys(formData)
        .filter((k) => !['sexo', 'edad', 'altura'].includes(k))
        .some((k) => formData[k] !== '' && formData[k] !== null);

    if (!tieneDatos) {
        showNotification('Por favor, ingresa al menos una medida física.', 'error');
        return;
    }

    guardando.value = true;
    try {
        const response = await axios.post('/api/progreso', formData);
        showNotification(response.data.message || 'Progreso guardado correctamente', 'success');

        if (response.data.new_medals && response.data.new_medals.length > 0) {
            response.data.new_medals.forEach((medal) => {
                showNotification(`🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`, 'success');
            });
            bigCelebration(); // 🎉 celebración grande por medalla nueva
        } else {
            mini(); // micro-celebración por progreso guardado
        }

        await cargarProgresos();
        await cargarLogros();
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.response?.data?.message || 'Error al guardar el progreso corporal.', 'error');
    } finally {
        guardando.value = false;
    }
};

const verDetalle = async (id) => {
    try {
        const response = await axios.get('/api/progreso/detalle', { params: { id } });
        modalDetalle.value = {
            mostrar: true,
            progreso: response.data.progreso,
            comparacion: response.data.comparacion,
        };
    } catch (error) {
        console.error('Error al obtener detalle:', error);
        showNotification('No se pudo cargar el detalle del registro.', 'error');
    }
};

const cerrarModal = () => {
    modalDetalle.value.mostrar = false;
};

const crearMeta = async (nuevaMeta) => {
    creandoMeta.value = true;
    try {
        const response = await axios.post('/api/metas', nuevaMeta);
        showNotification(response.data.message || 'Meta creada con éxito.', 'success');
        await cargarMetas();
        await cargarLogros();
    } catch (error) {
        console.error('Error al crear meta:', error);
        showNotification(error.response?.data?.message || 'Error al crear la meta.', 'error');
    } finally {
        creandoMeta.value = false;
    }
};

const toggleMetaCompletada = async (meta) => {
    try {
        const response = await axios.post(`/api/metas/${meta.id}/completar`);
        showNotification(response.data.message || 'Meta actualizada.', 'success');

        if (response.data.new_medals && response.data.new_medals.length > 0) {
            response.data.new_medals.forEach((medal) => {
                showNotification(`🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`, 'success');
            });
            bigCelebration();
        } else if (response.data.meta.completada) {
            celebrate();
        }

        await cargarMetas();
        await cargarLogros();
    } catch (error) {
        console.error('Error al actualizar meta:', error);
        showNotification('Error al actualizar la meta.', 'error');
    }
};

const eliminarMeta = async (id) => {
    const confirmed = await toast.confirm(
        '¿Eliminar esta meta?',
        { title: 'Eliminar meta', confirmLabel: 'Sí, eliminar', type: 'error' }
    );
    if (!confirmed) return;

    // Snapshot + posición original para restaurar en el mismo lugar
    const idx = metas.value.findIndex(m => m.id === id);
    const snapshot = idx >= 0 ? { ...metas.value[idx] } : null;

    const { cancelled } = await useUndoable({
        message: 'Meta eliminada',
        apply: () => {
            metas.value = metas.value.filter(m => m.id !== id);
        },
        undo: () => {
            if (!snapshot) return;
            if (idx >= 0 && idx <= metas.value.length) {
                metas.value.splice(idx, 0, snapshot);
            } else {
                metas.value.push(snapshot);
            }
        },
        commit: () => axios.delete(`/api/metas/${id}`),
        onError: (err) => {
            console.error('Error al eliminar meta:', err);
        },
    });

    // Solo recargar logros si la eliminación se confirmó
    if (!cancelled) {
        await cargarLogros();
    }
};

// === Chart.js ===
// async porque hace un dynamic import de chart.js (lazy-load). Solo se baja
// el vendor-chart (~206 kB) cuando hay datos para graficar.
const initChart = async () => {
    const ctx = document.getElementById('progresoChart');
    if (!ctx) return;
    if (chartInstance) chartInstance.destroy();

    // Lazy-load de chart.js: se carga solo cuando se inicializa el chart.
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const key = metricaGrafica.value;
    const validData = progresos.value
        .filter((p) => p[key] !== null && p[key] !== undefined && Number(p[key]) > 0)
        .map((p) => ({ fecha: p.fecha, valor: parseFloat(p[key]) }));

    if (validData.length === 0) return;
    validData.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

    const labels = validData.map((d) => {
        const date = new Date(d.fecha + 'T00:00:00');
        return formatDateShort(date);
    });
    const dataValues = validData.map((d) => d.valor);

    const gridColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';
    const textColor = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563';
    const canvasCtx = ctx.getContext('2d');
    const gradient = canvasCtx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: key,
                data: dataValues,
                borderColor: '#6366f1',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#4f46e5',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: (context) => ` ${context.parsed.y} ${key === 'peso' ? 'kg' : 'cm'}`,
                    },
                },
            },
            scales: {
                y: { grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, font: { family: 'ui-sans-serif, system-ui' } } },
                x: { grid: { display: false }, ticks: { color: textColor, font: { family: 'ui-sans-serif, system-ui' } } },
            },
        },
    });
};

watch(metricaGrafica, () => nextTick(() => initChart()));
watch(activeTab, (newTab) => { if (newTab === 'medidas') nextTick(() => initChart()); });

onMounted(() => {
    cargarProgresos();
    cargarMetas();
    cargarLogros();
    cargarWeightChart();
});

// === QW4: Exportar progreso a PDF ===
// Usa jspdf (lazy-loaded) para generar un PDF con cover, stats, medidas,
// metas y logros. No hace falta un endpoint backend: la data ya está en
// el estado del componente. Si la sección no se cargó todavía (ej. el
// user nunca entró a "metas"), hacemos un fetch para tener data completa.
const { exportando: exportandoPdf, exportarPdf } = useProgresoPdf();

const exportarProgresoPdf = async () => {
    try {
        // Pedimos en paralelo lo que no tenemos en estado local
        const [statsRes, userRes, metasRes, logrosRes] = await Promise.all([
            axios.get('/api/stats/resumen').catch(() => ({ data: {} })),
            axios.get('/api/user-info').catch(() => ({ data: {} })),
            axios.get('/api/metas').catch(() => ({ data: [] })),
            axios.get('/api/logros').catch(() => ({ data: [] })),
        ]);

        // Stats y metas ya pueden estar cargados en el estado local; usamos
        // lo que esté más completo (local o lo recién fetcheado).
        const stats = statsRes.data || {};
        const metasArr = (metasRes.data?.length ? metasRes.data : metas.value) || [];
        const logrosArr = (logrosRes.data?.length ? logrosRes.data : logros.value) || [];
        const nombre = userRes.data?.name || userRes.data?.nick || 'Alumno';

        await exportarPdf({
            progresos: progresos.value,
            stats,
            metas: metasArr,
            logros: logrosArr,
            userName: nombre,
        });
        toast.success('PDF generado ✓');
    } catch (e) {
        toast.apiError(e, 'No se pudo generar el PDF.');
    }
};
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
