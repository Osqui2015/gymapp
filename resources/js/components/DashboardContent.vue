<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 pb-28 md:py-8 md:pb-8">
    <Breadcrumbs :items="[{ label: 'Inicio' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />

    <!-- Indicador pull-to-refresh (mobile) -->
    <div
      v-show="pullOffset > 4 || isRefreshing"
      :style="{ height: pullOffset + 'px' }"
      class="md:hidden flex items-center justify-center overflow-hidden transition-[height] duration-150 max-w-6xl mx-auto"
      aria-live="polite"
      role="status"
    >
      <div class="flex flex-col items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
        <svg class="w-5 h-5 animate-spin text-indigo-600" v-if="isRefreshing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <svg class="w-5 h-5 text-indigo-600" v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
        <span>{{ isRefreshing ? 'Actualizando…' : 'Deslizá hacia abajo' }}</span>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <template v-if="rutinaStore.seleccionada">
        <div data-tour="home-hero" class="mb-4">
          <HomeHero />
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
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
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
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200/80 dark:border-gray-700 bg-gray-950/95 backdrop-blur md:hidden pb-[env(safe-area-inset-bottom)]">
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
      <DashboardWeeklyChart :historial="historialRutina" class="mt-6" data-tour="weekly-chart" />
    </div>

    <!-- Onboarding tour (auto-start en primera visita) -->
    <OnboardingTour :tour="onboarding" />

    <DashboardRestTimer
      :model-value="timer"
      @pausar-reanudar="pausarReanudarTemporizador"
      @agregar-30s="agregarTiempoTemporizador"
      @saltar="saltarTemporizador"
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
import DashboardRestTimer from './dashboard/DashboardRestTimer.vue';

const rutinaStore = useRutinaStore();

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

const diaIndex = computed(() => todosLosDias.value.indexOf(diaActual.value));
const esUltimoDia = computed(() => diaIndex.value === todosLosDias.value.length - 1);

const seriesTotales = computed(() => filasSerie.value.length);
const seriesCompletadas = computed(() => filasSerie.value.filter((f) => f.completado).length);
const seriesPendientes = computed(() => Math.max(seriesTotales.value - seriesCompletadas.value, 0));

const pesoRegistrado = computed(() => filasSerie.value.reduce((t, f) => {
    const p = Number(f.peso);
    return Number.isFinite(p) ? t + p : t;
}, 0).toFixed(1));

const pesoPromedio = computed(() => {
    const pesos = filasSerie.value.map((f) => Number(f.peso)).filter((p) => Number.isFinite(p) && p > 0);
    if (!pesos.length) return '0.0';
    return (pesos.reduce((t, p) => t + p, 0) / pesos.length).toFixed(1);
});

const repsRegistradas = computed(() => filasSerie.value.reduce((t, f) => {
    const r = Number(f.reps_realizadas);
    return Number.isFinite(r) ? t + r : t;
}, 0));

const progresoDia = computed(() => seriesTotales.value ? Math.round((seriesCompletadas.value / seriesTotales.value) * 100) : 0);

const textoBotonSiguiente = computed(() => {
    if (esUltimoDia.value) {
        return seriesCompletadas.value === seriesTotales.value ? '🎉 Finalizar Rutina' : '⚠️ Terminar e Iniciar';
    }
    return seriesPendientes.value > 0 ? '⚠️ Siguiente Día →' : 'Siguiente Día →';
});

const botonSiguienteClass = computed(() => {
    if (esUltimoDia.value && seriesPendientes.value > 0) return 'bg-orange-500 hover:bg-orange-600';
    if (!esUltimoDia.value && seriesPendientes.value > 0) return 'bg-yellow-500 hover:bg-yellow-600';
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
                const supersetExercises = filteredRutinas.filter((r) => r.superserie_grupo === rutina.superserie_grupo);
                blocks.push({ isSuperset: true, grupo: rutina.superserie_grupo, exercises: supersetExercises });
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
                    series_completadas: registro?.series_completadas ?? (registro?.completado ? 1 : 0),
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
                            series_completadas: registro?.series_completadas ?? (registro?.completado ? 1 : 0),
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
        await axios.post('/api/historial/guardar', {
            rutina_nombre: fila.rutina_nombre,
            dia: fila.dia,
            ejercicio_nombre: fila.ejercicio_nombre,
            series_numero: fila.series_numero,
            series_completadas: fila.completado ? 1 : 0,
            reps_min: fila.reps_min,
            reps_max: fila.reps_max,
            reps_realizadas: fila.reps_realizadas === '' || fila.reps_realizadas == null ? null : Number(fila.reps_realizadas),
            descanso_min: fila.descanso_min,
            peso: fila.peso === '' || fila.peso == null ? null : Number(fila.peso),
            completado: fila.completado,
            superserie_grupo: fila.superserie_grupo,
            // Fase 3: esfuerzo RIR/RPE
            esfuerzo_tipo: fila.esfuerzo_tipo || null,
            esfuerzo_valor: fila.esfuerzo_valor ?? null,
        });
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

watch(() => rutinaStore.seleccionada, (newVal) => {
    if (newVal) {
        fetchHistorialRutina();
        fetchRutinasDelDia();
    }
});

// === Mejora 1.9: Pull-to-refresh ===
const refreshDashboard = async () => {
    await fetchUserRutina();
    if (rutinaStore.seleccionada) {
        await fetchHistorialRutina();
        await fetchRutinasDelDia();
    }
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, refreshDashboard);

// === Timer de descanso (estado y controles; UI en DashboardRestTimer.vue) ===
const timer = ref({
    activo: false,
    totalSegundos: 0,
    segundosRestantes: 0,
    ejercicioNombre: '',
    timerId: null,
    pausado: false,
});

const deberiaIniciarTemporizador = (fila) => {
    if (!fila.superserie_grupo) return true;
    const setsEnRonda = filasSerie.value.filter(
        (f) => f.superserie_grupo === fila.superserie_grupo && f.series_numero === fila.series_numero
    );
    return setsEnRonda.every((f) => f.completado);
};

const iniciarTemporizador = (fila) => {
    if (timer.value.timerId) clearInterval(timer.value.timerId);
    const descansoMinutos = parseFloat(fila.descanso_min) || 1.5;
    const totalSegundos = Math.round(descansoMinutos * 60);
    const nombreLabel = fila.superserie_grupo ? `Descanso Superserie ${fila.superserie_grupo}` : fila.ejercicio_nombre;
    timer.value = { activo: true, totalSegundos, segundosRestantes: totalSegundos, ejercicioNombre: nombreLabel, pausado: false, timerId: null };
    runTimer();
};

const runTimer = () => {
    timer.value.timerId = setInterval(() => {
        if (!timer.value.pausado) {
            if (timer.value.segundosRestantes > 0) timer.value.segundosRestantes--;
            else finalizarTemporizador();
        }
    }, 1000);
};

const pausarReanudarTemporizador = () => { timer.value.pausado = !timer.value.pausado; };
const agregarTiempoTemporizador = () => { timer.value.segundosRestantes += 30; timer.value.totalSegundos += 30; };
const saltarTemporizador = () => {
    if (timer.value.timerId) clearInterval(timer.value.timerId);
    timer.value.activo = false;
};

const finalizarTemporizador = () => {
    if (timer.value.timerId) clearInterval(timer.value.timerId);
    timer.value.activo = false;
    reproducirBeep();
};

const reproducirBeep = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const playBeep = (time, frequency, duration) => {
            const osc = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = frequency;
            gainNode.gain.setValueAtTime(0, time);
            gainNode.gain.linearRampToValueAtTime(0.3, time + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, time + duration);
            osc.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            osc.start(time);
            osc.stop(time + duration);
        };
        const now = audioCtx.currentTime;
        playBeep(now, 880, 0.4);
        playBeep(now + 0.5, 880, 0.4);
    } catch (e) {
        console.error('AudioContext no soportado o bloqueado:', e);
    }
};

onUnmounted(() => {
    if (timer.value.timerId) clearInterval(timer.value.timerId);
});
</script>