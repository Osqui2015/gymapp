<template>
    <div class="min-h-screen w-full bg-background font-body-md text-on-surface antialiased selection:bg-secondary-container selection:text-on-secondary-container flex flex-col justify-between">
        <div class="max-w-[1280px] w-full mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-xl flex flex-col gap-space-xl">
            <HistorialHeader
                :isTrainerOrAdmin="isTrainerOrAdmin"
                :alumnos="alumnos"
                :selectedAlumnoId="selectedAlumnoId"
                :activeTab="activeTab"
                :showKeyExercisesTab="isTrainerOrAdmin || hasTrainer"
                :has-history="historial.length > 0"
                :can-export="historial.length > 0"
                :stats="headerStats"
                @alumno-change="onAlumnoChange"
                @tab-change="activeTab = $event"
                @export-csv="exportarCSV"
                @export-pdf="exportarPDF"
                @export-resumen-csv="exportarResumenCSV"
            />

            <!-- Series pendientes de sincronizar (offline) -->
            <SyncBadge
            :pending="offline.pendingCount.value"
            :syncing="offline.isSyncing.value"
            :failed="offline.failedCount.value"
            :error-message="offline.lastError.value"
            @retry="reintentarSeriesFallidas"
            @dismiss="descartarSeriesFallidas"
        />

            <!-- Pull-to-refresh indicator (mobile) -->
            <HistorialPullRefresh
                :offset="pullOffset"
                :refreshing="isRefreshing"
                class="md:hidden"
            />

            <!-- Nivel 6: modal de edición de serie (se monta al final para que
                 pueda superponerse a cualquier contenido sin afectar layout). -->
            <EditHistorialModal
                :open="showEditModal"
                :serie="editingSerie"
                @close="showEditModal = false"
                @saved="onSerieSaved"
                @deleted="onSerieDeleted"
            />

            <!-- Loading / empty states -->
            <div v-if="loading" class="space-y-4">
                <div class="grid gap-3 grid-cols-2">
                    <BaseSkeleton variant="stat-card" :count="4" />
                </div>
                <div
                    class="bg-[#171b26] rounded-2xl border border-slate-800 p-6 shadow-sm"
                >
                    <BaseSkeleton variant="text" :count="6" />
                </div>
            </div>

            <div v-else-if="errorHistorial" class="space-y-2">
                <EmptyState
                    emoji="📡"
                    title="No se pudo cargar el historial"
                    :description="errorHistorial"
                    cta-text="Reintentar"
                    @cta="loadHistorial"
                />
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

            <div v-else class="space-y-4 animate-fadeIn">
                <!-- ===== UNIFIED RESPONSIVE LAYOUT (Mobile + Desktop) ===== -->
                <div class="flex flex-col gap-space-xl">
                    <!-- Filtros avanzados de Historial (con toggle compacto en mobile) -->
                    <HistorialFilters
                        v-model="filtros"
                        :rutinas-disponibles="rutinasDisponibles"
                        :dias-disponibles="diasDisponibles"
                        :hay-filtros-activos="hayFiltrosActivos"
                        :total-filtrados="filteredHistorial.length"
                        :total-original="historial.length"
                        @limpiar="limpiarFiltros"
                        @clear="limpiarFiltros"
                    />

                    <div
                        v-if="filteredHistorial.length === 0"
                        class="p-space-xl text-center bg-surface-container-low rounded-2xl shadow-sm"
                    >
                        <p class="text-body-md font-semibold text-on-surface">
                            No se encontraron series que coincidan con los filtros aplicados.
                        </p>
                        <button
                            type="button"
                            @click="limpiarFiltros"
                            class="mt-3 px-4 py-2 bg-secondary-container hover:opacity-90 text-on-secondary-container text-label-md font-label-md rounded-xl transition-all shadow-sm cursor-pointer"
                        >
                            Restablecer filtros
                        </button>
                    </div>

                    <HistorialMatrix
                        v-else-if="activeTab === 'matrix'"
                        :pivotData="pivotData"
                        :dateSortAsc="dateSortAsc"
                        :prDates="prDates"
                        @toggle-sort="toggleDateSort"
                        @cell-click="onMatrixCellClick"
                    />

                    <!-- Mobile Session Cards (Quick Session Accordion on Mobile inside Matrix tab) -->
                    <div
                        v-if="activeTab === 'matrix' && sesionesParaMostrar.length > 0"
                        class="md:hidden flex flex-col gap-2.5"
                    >
                        <div class="flex items-center justify-between px-0.5">
                            <h2 class="font-headline-md text-headline-md text-on-surface">Sesiones Recientes</h2>
                            <span class="text-label-sm text-outline">
                                {{ sesionesParaMostrar.length }} sesiones
                            </span>
                        </div>
                        <HistorialSesionCard
                            v-for="(s, i) in sesionesParaMostrar.slice(0, 6)"
                            :key="i"
                            :fecha="s.fecha"
                            :fecha-highlight="s.fechaHighlight"
                            :fecha-label="s.fechaLabel"
                            :duracion="s.duracion"
                            :series="s.series"
                            :tonelaje="s.tonelaje"
                            :rir-prom="s.rirProm"
                            :ejercicios="s.ejercicios"
                            :expanded="expandedSesionIndex === i"
                            :show-actions="expandedSesionIndex === i"
                            @toggle="toggleSesionExpand(i)"
                            @ver-detalles="verDetalleSesion(s)"
                            @repetir-sesion="repetirSesion(s)"
                        />
                    </div>

                    <!-- Live Session Status Strip -->
                    <section
                        v-show="activeTab === 'matrix'"
                        class="px-space-lg py-space-md rounded-xl bg-surface-container-high flex flex-col sm:flex-row items-center justify-between gap-space-md shadow-sm"
                    >
                        <div class="flex items-center gap-space-md">
                            <div class="w-3 h-3 rounded-full bg-tertiary animate-ping"></div>
                            <span class="font-label-md text-label-md text-on-surface-variant">
                                Sesión programada en curso
                            </span>
                            <span class="text-body-md text-on-surface">
                                Día actual:
                                <strong class="text-secondary font-bold">
                                    {{ userRutina?.dia_actual || 'Día 1' }}
                                </strong>
                            </span>
                        </div>
                        <RescheduleButton
                            v-if="!isTrainerOrAdmin"
                            :current-day="userRutina?.dia_actual || 'Día 1'"
                            @rescheduled="onRescheduled"
                        />
                    </section>

                    <!-- Analytics & Activity Dashboard Grid (12 Columns) -->
                    <section
                        v-show="activeTab === 'matrix'"
                        class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg"
                    >
                        <!-- Left Column: Streak & Activity Heatmap + Weekly Calendar (7 Columns) -->
                        <div class="lg:col-span-7 flex flex-col gap-space-lg">
                            <!-- Top Row: Streak Card + Activity Heatmap -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-space-md">
                                <div class="md:col-span-5">
                                    <StreakCard :data="statsResumen" />
                                </div>
                                <div class="md:col-span-7">
                                    <ActivityHeatmap :data="statsHeatmap" />
                                </div>
                            </div>
                            <!-- Bottom Row: Weekly Calendar Strip -->
                            <WeekCalendar :user-id="selectedAlumnoId" />
                        </div>

                        <!-- Right Column: Esfuerzo (RIR / RPE Telemetry) (5 Columns) -->
                        <div class="lg:col-span-5">
                            <EffortCard :user-id="selectedAlumnoId" />
                        </div>
                    </section>

                    <!-- Vista dedicada cuando el usuario elige el tab Esfuerzo RIR.
                         v-if (no v-show) para que EffortCard y WeekCalendar no
                         se monten hasta que el usuario abra el tab. -->
                    <div v-if="activeTab === 'rir'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div class="lg:col-span-6">
                            <EffortCard :user-id="selectedAlumnoId" />
                        </div>
                        <div class="lg:col-span-6 flex flex-col gap-6">
                            <StreakCard :data="statsResumen" />
                            <WeekCalendar :user-id="selectedAlumnoId" />
                        </div>
                    </div>

                <HistorialCalendar v-show="activeTab === 'calendar'" :historial="historial" />

                <HistorialEvolution
                    v-show="activeTab === 'evolution'"
                    :tablaProgreso="tablaProgreso"
                    :resumenEjercicios="resumenEjercicios"
                    :globalMaxWeight="globalMaxWeight"
                    @export-view="exportarResumenCSV"
                />

                <RmCalculator
                    v-show="activeTab === 'rm_calculator'"
                    v-model:calculator="calculator"
                    v-model:rm-formula="rmFormula"
                    :historical1RMs="historical1RMs"
                />

                <HistorialComparison v-show="activeTab === 'comparison'" :historial="historial" />

                <!-- Comparador numérico: diffs entre dos fechas para un ejercicio -->
                <div v-show="activeTab === 'comparison'" class="mt-6">
                    <ComparadorEjercicios :ejercicios="historial" :user-id="selectedAlumnoId" />
                </div>

                <!-- Mapa corporal: muestra balance/fatigue/strength de los músculos -->
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
                                @muscle-click="onMuscleClick"
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
                </div>
                <!-- /DESKTOP -->

                <!-- Drilldown: modal con los ejercicios que trabajan el músculo clickeado -->
                <Teleport to="body">
                    <Transition name="modal">
                        <div
                            v-if="selectedMuscleSlug"
                            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                            @click.self="closeMuscleDrilldown"
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
                                        @click="closeMuscleDrilldown"
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
                                                    @click="toggleEjercicioChart(ej)"
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

        <!-- Footer -->
        <footer class="w-full bg-surface-container-lowest mt-space-xl py-space-lg">
            <div class="max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop flex flex-col sm:flex-row items-center justify-between gap-space-md text-body-sm text-outline">
                <div class="flex items-center gap-space-sm">
                    <span class="material-symbols-outlined text-secondary text-[18px]">fitness_center</span>
                    <span>© 2026 GymApp Telemetry Pro · Kinetic Obsidian Design System</span>
                </div>
                <div class="flex items-center gap-space-lg">
                    <a class="hover:text-on-surface transition-colors" href="/dashboard">Panel</a>
                    <a class="hover:text-on-surface transition-colors" href="/historial">Historial</a>
                    <a class="hover:text-on-surface transition-colors" href="/profile">Perfil</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';

import HistorialHeader from './historial/HistorialHeader.vue';
import HistorialSesionCard from './historial/HistorialSesionCard.vue';
import ObsidianMobileTopBar from './common/obsidian/ObsidianMobileTopBar.vue';
import ObsidianPill from './common/obsidian/ObsidianPill.vue';
import HistorialFilters from './historial/HistorialFilters.vue';
import HistorialMatrix from './historial/HistorialMatrix.vue';
import EditHistorialModal from './historial/EditHistorialModal.vue';
import HistorialEvolution from './historial/HistorialEvolution.vue';
import RmCalculator from './historial/RmCalculator.vue';
import KeyExercises from './historial/KeyExercises.vue';
import HistorialCalendar from './historial/HistorialCalendar.vue';
import HistorialComparison from './historial/HistorialComparison.vue';
import ComparadorEjercicios from './historial/ComparadorEjercicios.vue';
import HistorialPullRefresh from './historial/HistorialPullRefresh.vue';
import SyncBadge from './training/SyncBadge.vue';
import BaseSkeleton from './BaseSkeleton.vue';
import EmptyState from './EmptyState.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import BodyMap from './BodyMap.vue';
import StreakCard from './StreakCard.vue';
import ActivityHeatmap from './ActivityHeatmap.vue';
import EffortCard from './EffortCard.vue';
import OneRmChart from './OneRmChart.vue';
import RescheduleButton from './RescheduleButton.vue';
import WeekCalendar from './WeekCalendar.vue';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { useMuscleLoad } from '../composables/useMuscleLoad';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useMediaQuery } from '../composables/useMediaQuery';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import { useFormatters } from '@/composables/useFormatters';
import {
    filtrarHistorial,
    rutinasDisponibles as rutinasDisponiblesHelper,
    diasDisponibles as diasDisponiblesHelper,
} from '@/composables/useHistorialFiltros';

const { formatDateShort } = useFormatters();

let chartLoader;

const loadChart = async () => {
    chartLoader ??= import('chart.js').then(({ Chart, registerables }) => {
        Chart.register(...registerables);
        return Chart;
    });

    return chartLoader;
};

const toast = useToast();
const auth = useAuthStore();
const { isStaff, hasTrainer: authHasTrainer } = storeToRefs(auth);

// === State ===
// === Estado de carga/error ===
const loading = ref(true);
// Mensaje de error de la carga principal (historial). Antes un fallo de red
// se veía igual que "no hay series".
const errorHistorial = ref(null);
// Controller para cancelar el request anterior: si el usuario cambia de alumno
// rápido, la respuesta vieja no debe pisar la nueva.
let historialController = null;
const historial = ref([]);
const isTrainerOrAdmin = ref(false);
const alumnos = ref([]);
const selectedAlumnoId = ref(null);
const hasTrainer = ref(false);
const activeTab = ref('matrix');
const dateSortAsc = ref(true);

// Renderiza sólo el layout que corresponde (mobile o desktop). Con los dos en
// el DOM se duplicaban requests, gráficos y renders.
const isMobile = useMediaQuery('(max-width: 767px)');

// Series pendientes de sincronizar (offline-first).
const offline = useOfflineSeries();

// Series rechazadas de forma permanente: el usuario decide si reintenta o descarta.
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

const ejerciciosClave = ref([]);
const todosEjercicios = ref([]);
const keyExercisesLoading = ref(false);
const newKeyExercise = ref({ nombre: '', notas: '' });
const savingKeyExercise = ref(false);
const editingNotesId = ref(null);
const editingNotesValue = ref('');
const chartInstances = {};

const calculator = ref({ weight: 80, reps: 5 });
const rmFormula = ref('epley');

// === Filtros avanzados de historial ===
const filtros = ref({
    search: '',
    rutina: '',
    dia: '',
    periodo: 'all',
    fechaDesde: '',
    fechaHasta: '',
    soloCompletados: false,
    orden: 'fecha_desc',
});

const limpiarFiltros = () => {
    filtros.value = {
        search: '',
        rutina: '',
        dia: '',
        periodo: 'all',
        fechaDesde: '',
        fechaHasta: '',
        soloCompletados: false,
        orden: 'fecha_desc',
    };
};

// Antes se pasaba un prop que no existía (→ warning de Vue en cada render).
const hayFiltrosActivos = computed(() => {
    const f = filtros.value;
    return Boolean(
        (f.search && f.search.trim() !== '') ||
            (f.rutina && f.rutina !== '') ||
            (f.dia && f.dia !== '') ||
            (f.periodo && f.periodo !== 'all') ||
            (f.periodo === 'custom' && (f.fechaDesde || f.fechaHasta)) ||
            f.soloCompletados ||
            (f.orden && f.orden !== 'fecha_desc')
    );
});

// El payload de /api/historial usa 'rutina_nombre' (no 'rutina').
const rutinasDisponibles = computed(() => rutinasDisponiblesHelper(historial.value));

const diasDisponibles = computed(() => diasDisponiblesHelper(historial.value));

const filteredHistorial = computed(() => filtrarHistorial(historial.value, filtros.value));

// === Stats: racha + heatmap (Fase 1.3) ===
const statsResumen = ref({});
const statsHeatmap = ref({ days: [] });
const userRutina = ref(null);

// Iniciales del user para el avatar del top bar mobile
const userInitials = computed(() => {
    try {
        // auth store expone user con .name o .nick
        const u = window.__user || {};
        if (u.name) return String(u.name).slice(0, 1).toUpperCase();
        if (u.nick) return String(u.nick).slice(0, 1).toUpperCase();
    } catch {
        // ignore
    }
    return 'U';
});

// Sesiones recientes formateadas para el HistorialSesionCard (mockup mobile)
// Agrupa el historial por fecha+rutina+dia y arma los ejercicios con sus sets.
const sesionesRecientes = computed(() => {
    if (!historial.value || !historial.value.length) return [];

    const groups = new Map();
    historial.value.forEach((h) => {
        const key = `${h.fecha}|${h.rutina_nombre || ''}|${h.dia || ''}`;
        if (!groups.has(key)) {
            groups.set(key, {
                fecha: h.fecha,
                rutina: h.rutina_nombre,
                dia: h.dia,
                series: [],
                ejerciciosMap: new Map(),
            });
        }
        const g = groups.get(key);
        g.series.push(h);
        if (h.ejercicio_nombre) {
            if (!g.ejerciciosMap.has(h.ejercicio_nombre)) {
                g.ejerciciosMap.set(h.ejercicio_nombre, {
                    nombre: h.ejercicio_nombre,
                    meta: h.musculo || h.equipamiento || '',
                    sets: [],
                });
            }
            g.ejerciciosMap.get(h.ejercicio_nombre).sets.push({
                label: `${h.peso ?? 0} × ${h.reps_realizadas ?? 0}${h.unidad_peso === 'lb' ? 'lb' : 'kg'}`,
                esPR: h.es_pr === true,
            });
        }
    });

    // Ordenar por fecha descendente (más recientes primero) y formatear
    return Array.from(groups.values())
        .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
        .slice(0, 10)
        .map((g) => {
            const ejercicios = Array.from(g.ejerciciosMap.values());
            const fechaDate = new Date(g.fecha);
            const hoy = new Date();
            const diffDays = Math.round((hoy - fechaDate) / (1000 * 60 * 60 * 24));
            let fechaLabel = fechaDate.toLocaleDateString('es-AR', {
                day: 'numeric',
                month: 'short',
            });
            let fechaHighlight = '';
            if (diffDays === 0) {
                fechaLabel = 'Hoy';
                fechaHighlight = 'Hoy';
            } else if (diffDays === 1) {
                fechaLabel = 'Ayer';
                fechaHighlight = 'Ayer';
            } else if (diffDays < 7) {
                fechaLabel = fechaDate.toLocaleDateString('es-AR', { weekday: 'long' });
            } else {
                fechaLabel = fechaDate
                    .toLocaleDateString('es-AR', { day: 'numeric', month: 'long' })
                    .toUpperCase();
            }

            const tonelaje = g.series.reduce(
                (acc, s) => acc + Number(s.peso || 0) * Number(s.reps_realizadas || 0),
                0
            );
            const tonelajeStr = tonelaje >= 1000
                ? `${(tonelaje / 1000).toFixed(1)} t total`
                : `${tonelaje.toFixed(0)} kg total`;

            // Si el día está etiquetado, agregarlo al highlight
            const meta = g.dia ? (g.dia.startsWith('Día') ? g.dia : `Día ${g.dia}`) : '';
            const rirSeries = g.series.filter((s) => s.esfuerzo != null || s.rir != null);
            const rirProm = rirSeries.length
                ? (rirSeries.reduce((acc, s) => acc + Number(s.esfuerzo ?? s.rir), 0) / rirSeries.length).toFixed(1)
                : '—';

            return {
                fecha: fechaLabel.charAt(0).toUpperCase() + fechaLabel.slice(1),
                fechaHighlight,
                fechaLabel: meta || g.rutina || 'Sesión',
                fechaRaw: g.fecha,
                duracion: g.series[0]?.duracion || `${Math.max(25, Math.round(g.series.length * 2.5))} min`,
                series: g.series.length,
                tonelaje: tonelajeStr,
                rirProm,
                meta,
                ejercicios: ejercicios.slice(0, 8).map((ej) => {
                    // El badge muestra el peso máximo real del ejercicio
                    // (antes estaba hardcodeado en '60kg').
                    const pesos = ej.sets
                        .map((s) => Number(String(s.label).split(' ')[0]) || 0)
                        .filter((p) => p > 0);
                    const maxPeso = pesos.length ? Math.max(...pesos) : null;

                    return {
                        nombre: ej.nombre,
                        meta: ej.meta || meta,
                        cantSeries: ej.sets.length,
                        prBadge: maxPeso ? `${maxPeso}kg` : null,
                        sets: ej.sets.slice(0, 6),
                    };
                }),
            };
        });
});

const loadUserRutina = async () => {
    try {
        const res = await axios.get('/api/user-rutina');
        userRutina.value = res.data;
    } catch (err) {
        userRutina.value = null;
    }
};

const onRescheduled = (newUserRutina) => {
    userRutina.value = newUserRutina;
    toast.success('Día actualizado');
};

const loadStats = async () => {
    try {
        const params = {};
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const [r, h] = await Promise.all([
            axios.get('/api/stats/resumen', { params }),
            axios.get('/api/stats/heatmap', { params }),
        ]);
        statsResumen.value = r.data;
        statsHeatmap.value = h.data;
    } catch (err) {
        console.error('[HistorialContent] Error cargando stats:', err);
    }
};

// === Body map (mapa corporal) ===
const bodyMapMode = ref('balance'); // 'balance' | 'fatigue' | 'strength'
const bodyMapGender = ref('male');
const bodyMapLoading = ref(false);
const bodyMapData = ref({ historiales: [], musculos: [] });
// Para qué alumno se cargó el body map (evita mostrar el dato de otro).
const bodyMapAlumnoId = ref(null);
const selectedMuscleSlug = ref(null);
const muscleDrilldown = ref({ loading: false, error: null, data: null });
const selectedEjercicioId = ref(null);

const toggleEjercicioChart = (ej) => {
    selectedEjercicioId.value = selectedEjercicioId.value === ej.id ? null : ej.id;
};

// Cálculo de carga por músculo (reacciona cuando cambian los historiales)
const bodyMapHistorial = computed(() => bodyMapData.value.historiales);
// Diccionario ejercicio_id → músculos (el backend ya no lo repite por serie)
const bodyMapEjercicios = computed(() => bodyMapData.value.ejercicios || {});
const { levelsFor: bodyMapLevelsFor } = useMuscleLoad(bodyMapHistorial, {
    ejercicios: bodyMapEjercicios,
});
const bodyMapLevels = computed(() => bodyMapLevelsFor(bodyMapMode.value));

// Labels para el tooltip del body map
const muscleLabels = computed(() => {
    const out = {};
    for (const m of bodyMapData.value.musculos || []) {
        out[m.slug] = m.nombre_es;
    }
    return out;
});

const muscleCount = computed(() => {
    const used = new Set();
    const mapa = bodyMapEjercicios.value;
    for (const h of bodyMapData.value.historiales || []) {
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

const loadBodyMap = async () => {
    bodyMapLoading.value = true;
    try {
        const params = { window: 90 };
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const res = await axios.get('/api/body-map/data', { params });
        bodyMapData.value = res.data;
        bodyMapAlumnoId.value = selectedAlumnoId.value ?? null;
    } catch (err) {
        console.error('[HistorialContent] Error cargando body map:', err);
        bodyMapData.value = { historiales: [], ejercicios: {}, musculos: [] };
    } finally {
        bodyMapLoading.value = false;
    }
};

const onMuscleClick = async (slug) => {
    selectedMuscleSlug.value = slug;
    muscleDrilldown.value = { loading: true, error: null, data: null };
    try {
        const params = {};
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const res = await axios.get(`/api/body-map/muscle/${slug}/exercises`, { params });
        muscleDrilldown.value = { loading: false, error: null, data: res.data };
    } catch (err) {
        console.error('[HistorialContent] Error drilldown músculo:', err);
        muscleDrilldown.value = { loading: false, error: 'No se pudo cargar', data: null };
    }
};

const closeMuscleDrilldown = () => {
    selectedMuscleSlug.value = null;
    muscleDrilldown.value = { loading: false, error: null, data: null };
    selectedEjercicioId.value = null;
};

// Cargar el body map cuando se activa el tab o cambia el alumno
watch(activeTab, (newTab) => {
    if (newTab === 'body_map' && bodyMapAlumnoId.value !== (selectedAlumnoId.value ?? null)) {
        loadBodyMap();
    }
});

watch(selectedAlumnoId, () => {
    if (activeTab.value === 'body_map') loadBodyMap();
});

// === Helpers ===
const formatDate = (value) => {
    const datePart = String(value ?? '').slice(0, 10);
    const date = new Date(`${datePart}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date);
};

const calculate1RMValue = (w, r, formula) => {
    if (!w || !r || w <= 0 || r <= 0) return 0;
    if (formula === 'epley') return w * (1 + r / 30);
    return (100 * w) / (101.3 - 2.6712 * r);
};

// === Data fetching ===
const fetchUserInfo = async () => {
    try {
        // auth.fetchUser() retorna void — popula el store. Leemos del store
        // directamente vía el storeToRefs.
        await auth.fetchUser();
        hasTrainer.value = !!authHasTrainer.value;
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

const onAlumnoChange = async (alumnoId) => {
    // El header emite el id del alumno elegido. Sin asignarlo acá, el
    // select se revierte al primer alumno y toda la vista sigue mostrando
    // los datos del primero.
    const nuevoId = alumnoId == null ? null : Number(alumnoId);
    if (nuevoId === selectedAlumnoId.value) return;

    selectedAlumnoId.value = nuevoId;
    newKeyExercise.value = { nombre: '', notas: '' };
    // El body map cachea por alumno: hay que invalidarlo o muestra el anterior.
    bodyMapData.value = { historiales: [], ejercicios: {}, musculos: [] };
    bodyMapAlumnoId.value = null;

    await Promise.all([
        loadHistorial(),
        loadKeyExercises(),
        loadStats(),
    ]);
};

const loadHistorial = async () => {
    loading.value = true;
    errorHistorial.value = null;

    // Cancela la carga anterior: si el usuario cambia de alumno rápido, la
    // respuesta vieja podría pisar la nueva.
    if (historialController) historialController.abort();
    historialController = new AbortController();

    try {
        const params = {};
        if (isTrainerOrAdmin.value && selectedAlumnoId.value) {
            params.user_id = selectedAlumnoId.value;
        }
        const response = await axios.get('/api/historial', {
            params,
            signal: historialController.signal,
        });
        historial.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        // Cancelación: no es un error, la reemplazó otra carga.
        if (axios.isCancel(error)) return;
        console.error('Error cargando historial:', error);
        errorHistorial.value =
            'No se pudo cargar el historial. Revisá tu conexión y reintentá.';
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
    const idx = ejerciciosClave.value.findIndex((e) => e.id === id);
    const snapshot = idx >= 0 ? { ...ejerciciosClave.value[idx] } : null;

    await useUndoable({
        message: 'Ejercicio clave eliminado',
        apply: () => {
            ejerciciosClave.value = ejerciciosClave.value.filter((e) => e.id !== id);
        },
        undo: () => {
            if (!snapshot) return;
            if (idx >= 0 && idx <= ejerciciosClave.value.length) {
                ejerciciosClave.value.splice(idx, 0, snapshot);
            } else {
                ejerciciosClave.value.push(snapshot);
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

// === EditHistorialModal: estado y handlers (Nivel 6) ===
const showEditModal = ref(false);
const editingSerie = ref(null);

// Mobile: search + filtro de día (mockup)
const searchSesion = ref('');
const filtroDiaMobile = ref('todas');
const filtroPeriodoMobile = ref('30');
const expandedSesionIndex = ref(0);

const toggleSesionExpand = (idx) => {
    expandedSesionIndex.value = expandedSesionIndex.value === idx ? null : idx;
};

watch(filtroPeriodoMobile, (newVal) => {
    if (filtros.value) {
        filtros.value.periodo = newVal;
    }
});

// Días disponibles para las pills mobile.
// Sin fallback mock: si el usuario no tiene días registrados, no hay pills
// que ofrecer (antes se inventaban "Día 1 (Torso)", "Día 2 (Pierna)"...).
const diasParaFiltroMobile = computed(() => {
    const base = [{ id: 'todas', label: 'Todas' }];
    if (diasDisponibles && diasDisponibles.value && diasDisponibles.value.length) {
        diasDisponibles.value.forEach((d, i) => {
            base.push({ id: `d${i}`, label: d.label || d });
        });
    }
    return base;
});

// (Se eliminó el mock de sesiones del mockup: un usuario sin historial veía
//  sesiones falsas como "Press de banca plano" con un PR de 60kg.)

// Sesiones finales para mostrar en la lista mobile con filtrado y búsqueda
const sesionesParaMostrar = computed(() => {
    // Sin mock: si no hay sesiones reales, la lista queda vacía y se muestra
    // el empty state ("No se encontraron sesiones...").
    let res = sesionesRecientes.value;

    // Filtro por día
    if (filtroDiaMobile.value !== 'todas') {
        const diaObj = diasParaFiltroMobile.value.find((d) => d.id === filtroDiaMobile.value);
        if (diaObj) {
            const queryLabel = diaObj.label.toLowerCase();
            res = res.filter((s) => {
                const sl = (s.fechaLabel || s.meta || '').toLowerCase();
                return sl.includes(queryLabel) || queryLabel.includes(sl);
            });
        }
    }

    // Filtro por búsqueda de texto
    if (searchSesion.value.trim()) {
        const q = searchSesion.value.toLowerCase().trim();
        res = res.filter((s) => {
            const matchFecha = (s.fecha || '').toLowerCase().includes(q);
            const matchLabel = (s.fechaLabel || '').toLowerCase().includes(q);
            const matchEjercicios = s.ejercicios && s.ejercicios.some((e) => e.nombre.toLowerCase().includes(q));
            return matchFecha || matchLabel || matchEjercicios;
        });
    }

    return res;
});

// Tracker Semanal y consistencia anual (Micro Week Tracker)
const semanaDias = computed(() => {
    const now = new Date();
    const currentDay = now.getDay();
    const distanceToMonday = (currentDay + 6) % 7;
    const monday = new Date(now);
    monday.setDate(now.getDate() - distanceToMonday);

    const letters = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
    const days = [];
    // La API devuelve la fecha como ISO completo: hay que quedarse con el
    // YYYY-MM-DD para que matchee la clave del día (si no, ningún día
    // marcaba como entrenado).
    const fechasEntrenadas = new Set(
        (historial.value || []).map((h) => String(h.fecha || '').slice(0, 10)).filter(Boolean)
    );

    for (let i = 0; i < 7; i++) {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        const iso = d.toISOString().split('T')[0];
        const isToday = d.toDateString() === now.toDateString();
        const trained = fechasEntrenadas.has(iso);
        days.push({
            letter: letters[i],
            dayNum: d.getDate(),
            trained,
            isToday,
        });
    }
    return days;
});

const semanaTexto = computed(() => {
    const days = semanaDias.value;
    if (!days.length) return 'Semana actual';
    const first = days[0].dayNum;
    const last = days[6].dayNum;
    const month = new Date().toLocaleDateString('es-AR', { month: 'short' });
    return `Semana del ${first} al ${last} ${month}`;
});

const entrenadosCountSemana = computed(() => {
    return semanaDias.value.filter((d) => d.trained || d.isToday).length || 4;
});

const totalSetsAnual = computed(() => {
    return statsResumen.value?.total_sets || (historial.value?.length ? historial.value.length : 91);
});

const miniHeatmapBlocks = [
    'bg-[#4edea3]',
    'bg-[#00885d]',
    'bg-[#4edea3]',
    'bg-[#313540]',
    'bg-[#4edea3]',
    'bg-[#4edea3]',
    'bg-[#00885d]',
    'bg-[#313540]',
    'bg-[#4edea3]',
    'bg-[#4edea3]',
    'bg-[#4edea3]',
    'bg-[#4edea3]',
];
// (La card de Esfuerzo/RIR mobile ahora usa el componente EffortCard, que consume
//  /api/historial/effort-analysis con datos reales. Se eliminó el mock local y su
//  carga a /api/stats/esfuerzo, que además usaba claves inexistentes del payload.)

// Acciones del HistorialSesionCard mobile
const verDetalleSesion = (sesion) => {
    // Por ahora navega al dashboard con query string de la sesión;
    // más adelante podemos hacer drilldown con el id.
    if (sesion?.fechaRaw) {
        window.location.href = `/dashboard?fecha=${sesion.fechaRaw}`;
    }
};

const repetirSesion = (sesion) => {
    if (!sesion?.fechaRaw) return;
    if (!window.confirm('¿Cargar esta rutina como nuevo entrenamiento?')) return;
    // Llamada simple al backend para arrancar nueva sesión copiando sets
    axios
        .post('/api/sesiones/repetir', { fecha_origen: sesion.fechaRaw })
        .then(() => {
            toast.success('Sesión copiada. Iniciá el entrenamiento desde el Dashboard.');
        })
        .catch((err) => {
            console.error(err);
            toast.error('No se pudo copiar la sesión.');
        });
};

const onMatrixCellClick = ({ ejercicio, fecha }) => {
    // Buscar la primera serie que matchea (ejercicio, fecha).
    // Si hay varias (distintos pesos/reps), tomar la de mayor peso.
    const matches = filteredHistorial.value.filter(
        (h) => h.ejercicio_nombre === ejercicio && h.fecha === fecha
    );
    if (!matches.length) return;
    const target =
        matches.length === 1
            ? matches[0]
            : matches.reduce((a, b) => (Number(b.peso) > Number(a.peso) ? b : a));
    editingSerie.value = target;
    showEditModal.value = true;
};

const onSerieSaved = (updated) => {
    if (!updated) return;
    // Reemplazar la serie en el array (insert o update)
    const idx = historial.value.findIndex((h) => h.id === updated.id);
    if (idx >= 0) {
        historial.value[idx] = updated;
    } else {
        // Caso undo: insertar
        historial.value.push(updated);
    }
};

const onSerieDeleted = (id) => {
    historial.value = historial.value.filter((h) => h.id !== id);
};

// === PR detection: marca fechas donde el peso fue máximo histórico ===
// (considera todas las series hasta esa fecha, no solo del filtro).
const prDates = computed(() => {
    const set = new Set();
    if (!historial.value || !historial.value.length) return set;

    // Agrupar por ejercicio, ordenar por fecha, llevar max acumulado.
    const porEjercicio = new Map();
    historial.value.forEach((h) => {
        if (!h.ejercicio_nombre || !h.fecha || h.peso == null) return;
        if (!porEjercicio.has(h.ejercicio_nombre)) porEjercicio.set(h.ejercicio_nombre, []);
        porEjercicio.get(h.ejercicio_nombre).push(h);
    });

    porEjercicio.forEach((series) => {
        const ordenadas = [...series].sort((a, b) => {
            if (a.fecha !== b.fecha) return a.fecha < b.fecha ? -1 : 1;
            return (a.id ?? 0) - (b.id ?? 0);
        });
        let max = 0;
        ordenadas.forEach((s) => {
            const peso = Number(s.peso) || 0;
            if (peso > 0 && peso > max) {
                max = peso;
                set.add(`${s.ejercicio_nombre}|||${s.fecha}`);
            }
        });
    });

    return set;
});

// === Computed aggregations (aplican sobre filteredHistorial) ===
const pivotData = computed(() => {
    if (!filteredHistorial.value || !filteredHistorial.value.length) return { dates: [], rows: [] };

    const dateMap = new Map();
    filteredHistorial.value.forEach((row) => {
        if (!dateMap.has(row.fecha)) dateMap.set(row.fecha, formatDate(row.fecha));
    });

    const sortedDates = [...dateMap.keys()].sort((a, b) => {
        const dateA = new Date(a);
        const dateB = new Date(b);
        return dateSortAsc.value ? dateA - dateB : dateB - dateA;
    });

    const exercises = [
        ...new Set(filteredHistorial.value.map((row) => row.ejercicio_nombre)),
    ].sort();

    const rows = exercises.map((exerciseName) => {
        const exerciseRows = filteredHistorial.value.filter(
            (row) => row.ejercicio_nombre === exerciseName
        );
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
        const superserie_grupo =
            exerciseRows.find((row) => row.superserie_grupo !== null)?.superserie_grupo || null;
        return { name: exerciseName, weights: dateWeights, superserie_grupo };
    });

    return {
        dates: sortedDates.map((d) => ({ raw: d, formatted: dateMap.get(d) })),
        rows,
    };
});

const globalMaxWeight = computed(() => {
    const values = filteredHistorial.value
        .map((row) => Number(row.peso))
        .filter((peso) => Number.isFinite(peso) && peso > 0);
    return values.length ? Math.max(...values, 10) : 10;
});

const totalSeries = computed(() => filteredHistorial.value.length);

const tonelajeTotal = computed(() => {
    const totalKg = filteredHistorial.value.reduce((sum, row) => {
        const p = Number(row.peso) || 0;
        const r = Number(row.reps_realizadas) || 0;
        return sum + p * r;
    }, 0);
    if (totalKg >= 1000) {
        return `${(totalKg / 1000).toFixed(1)} t`;
    }
    return `${Math.round(totalKg).toLocaleString('es-AR')} kg`;
});

const pesoPromedioGlobal = computed(() => {
    const values = filteredHistorial.value
        .map((row) => Number(row.peso))
        .filter((peso) => Number.isFinite(peso) && peso > 0);
    if (!values.length) return '0.0';
    return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

const repsPromedioGlobal = computed(() => {
    const values = filteredHistorial.value
        .map((row) => Number(row.reps_realizadas))
        .filter((reps) => Number.isFinite(reps) && reps >= 0);
    if (!values.length) return '0.0';
    return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1);
});

// Sin fallbacks mock: si no hay datos se muestra 0 / '—', nunca cifras inventadas
// (antes un usuario sin historial veía 19 ejercicios, 101 series y "14.6 ton").
const headerStats = computed(() => ({
    ejercicios: resumenEjercicios.value.length,
    totalSeries: totalSeries.value,
    seriesCompletadas: filteredHistorial.value.filter((h) => h.completado).length,
    tonelajeTotal: totalSeries.value ? tonelajeTotal.value : '—',
    pesoPromedio: totalSeries.value ? pesoPromedioGlobal.value : '—',
    repsPromedio: totalSeries.value ? repsPromedioGlobal.value : '—',
}));

// === Exportar historial a CSV (respeta filtros actuales) ===
// Escapa comillas dobles y envuelve en comillas si el valor tiene comas/comillas/saltos.
const escapeCsvValue = (val) => {
    const s = val == null ? '' : String(val);
    if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
    return s;
};

// La API devuelve la fecha como ISO (2026-10-07T00:00:00.000000Z).
// Nos quedamos con YYYY-MM-DD para que en la planilla sea una fecha y no un timestamp.
const formatFechaHistorial = (fecha) => {
    if (!fecha) return '';
    const s = String(fecha);
    const match = s.match(/^(\d{4}-\d{2}-\d{2})/);
    return match ? match[1] : s;
};

const exportarCSV = () => {
    const dataToExport = filteredHistorial.value;
    if (!dataToExport.length) {
        toast.warning('No hay datos en el filtro actual para exportar.');
        return;
    }

    const headers = [
        'Fecha',
        'Rutina',
        'Día',
        'Ejercicio',
        'Serie #',
        'Reps min',
        'Reps max',
        'Reps hechas',
        'Peso (kg)',
        'Descanso (min)',
        'Completado',
        'Superserie',
    ];
    const escape = escapeCsvValue;

    const rows = dataToExport.map((row) =>
        [
            formatFechaHistorial(row.fecha),
            row.rutina_nombre || row.rutina || '',
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
        ]
            .map(escape)
            .join(',')
    );

    const csv = '\ufeff' + [headers.map(escape).join(','), ...rows].join('\n'); // BOM para Excel
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const alumnoTag =
        isTrainerOrAdmin.value && selectedAlumnoId.value ? `-alumno-${selectedAlumnoId.value}` : '';
    const filename = `historial${alumnoTag}-${new Date().toISOString().split('T')[0]}.csv`;

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    toast.success(`Historial exportado (${dataToExport.length} registros filtrados).`);
};

// === Exportar resumen por ejercicio a CSV (promedio / mínima / máxima + fecha) ===
// Una fila por ejercicio y fecha: qué cargaste, en qué rango y cuándo.
const exportarResumenCSV = () => {
    const dataToExport = filteredHistorial.value;
    if (!dataToExport.length) {
        toast.warning('No hay datos en el filtro actual para exportar.');
        return;
    }

    // Agrupamos por ejercicio + fecha (cada fila es una sesión de ese ejercicio).
    const groups = new Map();
    dataToExport.forEach((row) => {
        const nombre = (row.ejercicio_nombre || '').trim() || 'Sin nombre';
        const fecha = formatFechaHistorial(row.fecha);
        const key = `${nombre}::${fecha}`;
        if (!groups.has(key)) {
            groups.set(key, {
                nombre,
                fecha,
                rutina: row.rutina_nombre || row.rutina || '',
                dia: row.dia || '',
                rows: [],
            });
        }
        groups.get(key).rows.push(row);
    });

    const round = (value) => Math.round(value * 100) / 100;

    const headers = [
        'Ejercicio',
        'Fecha',
        'Rutina',
        'Día',
        'Series',
        'Carga promedio (kg)',
        'Carga mínima (kg)',
        'Carga máxima (kg)',
        'Reps promedio',
        'Volumen (kg)',
    ];

    const rows = [...groups.values()]
        .sort(
            (a, b) =>
                (b.fecha || '').localeCompare(a.fecha || '') || a.nombre.localeCompare(b.nombre)
        )
        .map((group) => {
            // Solo cuentan las series con carga > 0: las de peso corporal (0 kg)
            // no deben promediar ni bajar la carga mínima.
            const cargas = group.rows
                .map((row) => Number(row.peso))
                .filter((peso) => Number.isFinite(peso) && peso > 0);
            const reps = group.rows
                .map((row) => Number(row.reps_realizadas))
                .filter((value) => Number.isFinite(value) && value > 0);

            const promedio = cargas.length ? cargas.reduce((s, v) => s + v, 0) / cargas.length : null;
            const minimo = cargas.length ? Math.min(...cargas) : null;
            const maximo = cargas.length ? Math.max(...cargas) : null;
            const repsPromedio = reps.length ? reps.reduce((s, v) => s + v, 0) / reps.length : null;
            const volumen = group.rows.reduce((sum, row) => {
                const peso = Number(row.peso);
                const r = Number(row.reps_realizadas);
                if (!Number.isFinite(peso) || peso <= 0) return sum;
                if (!Number.isFinite(r) || r <= 0) return sum;
                return sum + peso * r;
            }, 0);

            return [
                group.nombre,
                group.fecha,
                group.rutina,
                group.dia,
                group.rows.length,
                promedio === null ? '' : round(promedio),
                minimo === null ? '' : round(minimo),
                maximo === null ? '' : round(maximo),
                repsPromedio === null ? '' : round(repsPromedio),
                round(volumen),
            ]
                .map(escapeCsvValue)
                .join(',');
        });

    const csv = '\ufeff' + [headers.map(escapeCsvValue).join(','), ...rows].join('\n'); // BOM para Excel
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const alumnoTag =
        isTrainerOrAdmin.value && selectedAlumnoId.value ? `-alumno-${selectedAlumnoId.value}` : '';
    const filename = `historial-resumen${alumnoTag}-${new Date().toISOString().split('T')[0]}.csv`;

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    toast.success(`Resumen exportado (${rows.length} ejercicios con carga).`);
};

// === Exportar historial a PDF (respeta filtros actuales) ===
const exportarPDF = async () => {
    const dataToExport = filteredHistorial.value;
    if (!dataToExport.length) {
        toast.warning('No hay datos en el filtro actual para exportar.');
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
        const alumnoTag =
            isTrainerOrAdmin.value && selectedAlumnoId.value
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
        const fechaTexto = new Date().toLocaleString('es-AR', {
            dateStyle: 'long',
            timeStyle: 'short',
        });
        doc.text(`Generado: ${fechaTexto}`, 40, 68);

        // Stats resumen
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text('Resumen', 40, 100);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text(
            `Ejercicios rastreados: ${headerStats.value.ejercicios} · ` +
                `Series registradas: ${headerStats.value.totalSeries} · ` +
                `Peso promedio: ${headerStats.value.pesoPromedio} kg · ` +
                `Reps promedio: ${headerStats.value.repsPromedio} · ` +
                `Volumen: ${headerStats.value.tonelajeTotal} kg`,
            40,
            118
        );

        // Tabla
        autoTable(doc, {
            startY: 140,
            head: [
                [
                    'Fecha',
                    'Rutina',
                    'Día',
                    'Ejercicio',
                    'Serie',
                    'Reps min',
                    'Reps max',
                    'Reps hechas',
                    'Peso (kg)',
                    'Descanso (min)',
                    'OK',
                    'SS',
                ],
            ],
            body: dataToExport.map((row) => [
                formatFechaHistorial(row.fecha),
                row.rutina_nombre || row.rutina || '',
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
                    doc.internal.pageSize.height - 20
                );
            },
        });

        doc.save(filename);
        toast.success(`PDF generado (${dataToExport.length} registros).`);
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

const slugify = (value) =>
    value
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const resumenEjercicios = computed(() => {
    const grouped = new Map();
    filteredHistorial.value.forEach((row) => {
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
                const weights = dayRows
                    .map((row) => Number(row.peso))
                    .filter((peso) => Number.isFinite(peso) && peso > 0);
                const reps = dayRows
                    .map((row) => Number(row.reps_realizadas))
                    .filter((value) => Number.isFinite(value) && value >= 0);
                const avgPeso = weights.length
                    ? weights.reduce((sum, v) => sum + v, 0) / weights.length
                    : 0;
                const maxPeso = weights.length ? Math.max(...weights) : 0;
                const avgReps = reps.length ? reps.reduce((sum, v) => sum + v, 0) / reps.length : 0;
                const sample = dayRows[0];
                const rirRow = dayRows.find((r) => r.rir !== null && r.rir !== undefined);
                return {
                    fecha,
                    fechaLabel: formatDate(fecha),
                    diaLabel: sample?.dia || 'Día',
                    avgPeso,
                    maxPeso,
                    pesoPromedio: avgPeso.toFixed(1),
                    pesoMaximo: maxPeso.toFixed(1),
                    repsPromedio: avgReps.toFixed(1),
                    rir: rirRow ? rirRow.rir : null,
                };
            });

        const weights = rows
            .map((row) => Number(row.peso))
            .filter((peso) => Number.isFinite(peso) && peso > 0);
        const reps = rows
            .map((row) => Number(row.reps_realizadas))
            .filter((value) => Number.isFinite(value) && value >= 0);
        const totalPeso = weights.reduce((sum, v) => sum + v, 0);
        const totalReps = reps.reduce((sum, v) => sum + v, 0);
        const superserie_grupo =
            rows.find((row) => row.superserie_grupo !== null && row.superserie_grupo !== undefined)
                ?.superserie_grupo || null;
        const uniqueDias = [...new Set(rows.map((r) => r.dia).filter(Boolean))];
        const diasBadge = uniqueDias.length ? uniqueDias.join(' & ') : 'Rutina General';
        const minW = weights.length ? Math.min(...weights) : 0;
        const maxW = weights.length ? Math.max(...weights) : 0;
        const overloadPct = minW > 0 && maxW > minW ? (((maxW - minW) / minW) * 100).toFixed(1) : null;

        return {
            nombre,
            slug: slugify(nombre),
            superserie_grupo,
            diasBadge,
            overloadPct,
            totalSeries: rows.length,
            totalSesiones: timeline.length,
            pesoPromedio: weights.length ? (totalPeso / weights.length).toFixed(1) : '0.0',
            repsPromedio: reps.length ? (totalReps / reps.length).toFixed(1) : '0.0',
            ultimoPeso: weights.length ? weights[weights.length - 1].toFixed(1) : '0.0',
            pesoMinimo: minW.toFixed(1),
            pesoMaximo: maxW.toFixed(1),
            fechaInicial: timeline.length ? timeline[0].fechaLabel : '-',
            fechaFinal: timeline.length ? timeline[timeline.length - 1].fechaLabel : '-',
            timeline,
            sesiones: [...timeline].reverse().map((sesion) => ({
                fecha: sesion.fechaLabel,
                dia: sesion.diaLabel,
                pesoPromedio: sesion.pesoPromedio,
                pesoMaximo: sesion.pesoMaximo,
                repsPromedio: sesion.repsPromedio,
                rir: sesion.rir,
                isPR: maxW > 0 && Number(sesion.maxPeso) === maxW,
            })),
        };
    });
});

const tablaProgreso = computed(() => {
    const result = [];
    resumenEjercicios.value.forEach((ejercicio) => {
        const exMax = Number(ejercicio.pesoMaximo) || 0;
        ejercicio.timeline.forEach((sesion, idx) => {
            const dayRows = filteredHistorial.value.filter(
                (row) => row.ejercicio_nombre === ejercicio.nombre && row.fecha === sesion.fecha
            );
            const weights = dayRows
                .map((row) => Number(row.peso))
                .filter((peso) => Number.isFinite(peso) && peso > 0);
            const maxWeight = weights.length ? Math.max(...weights) : 0;
            const prevSesion = idx > 0 ? ejercicio.timeline[idx - 1] : null;
            const prevMax = prevSesion ? Number(prevSesion.maxPeso || prevSesion.avgPeso || 0) : maxWeight;
            const trendDelta = Number((maxWeight - prevMax).toFixed(1));
            const superserie_grupo =
                dayRows.find((row) => row.superserie_grupo !== null)?.superserie_grupo || null;
            result.push({
                nombre: ejercicio.nombre,
                fecha: sesion.fechaLabel,
                dia: sesion.diaLabel,
                rawFecha: new Date(sesion.fecha),
                maxWeight: maxWeight.toFixed(1),
                avgWeight: sesion.pesoPromedio,
                seriesCount: dayRows.length,
                superserie_grupo,
                trendDelta,
                isPR: exMax > 0 && maxWeight === exMax,
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
        if (
            !exerciseMaxes[row.ejercicio_nombre] ||
            rmVal > exerciseMaxes[row.ejercicio_nombre].rm
        ) {
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
            dateMap[row.fecha] = {
                fecha: row.fecha,
                rm: rmVal,
                weight: w,
                reps: r,
                dia: row.dia || 'Día',
            };
        }
    });
    return Object.values(dateMap).sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
};

const initKeyCharts = async () => {
    const Chart = await loadChart();

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
            return formatDateShort(t.fecha);
        });
        const dataValues = timeline.map((t) => parseFloat(t.rm.toFixed(1)));

        const gridColor = document.documentElement.classList.contains('dark')
            ? '#374151'
            : '#e2e8f0';
        const textColor = document.documentElement.classList.contains('dark')
            ? '#9ca3af'
            : '#4b5563';

        const canvasCtx = ctx.getContext('2d');
        const gradient = canvasCtx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        chartInstances[ej.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
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
                    },
                ],
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
                    y: {
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: textColor,
                            font: { size: 10, family: 'ui-sans-serif, system-ui' },
                        },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: textColor,
                            font: { size: 10, family: 'ui-sans-serif, system-ui' },
                        },
                    },
                },
            },
        });
    });
};

// === UI actions ===
const toggleDateSort = () => {
    dateSortAsc.value = !dateSortAsc.value;
};

watch(activeTab, (newTab) => {
    if (newTab === 'key_exercises') nextTick(() => initKeyCharts());
});

// Cuando termina la sincronización offline el historial cambió (series que
// estaban encoladas): hay que recargar para verlas.
watch(
    () => offline.pendingCount.value,
    (nuevo, anterior) => {
        if (anterior > 0 && nuevo === 0) loadHistorial();
    }
);

watch(rmFormula, () => {
    if (activeTab.value === 'key_exercises') nextTick(() => initKeyCharts());
});

onMounted(async () => {
    await fetchUserInfo();
    if (!isTrainerOrAdmin.value) {
        await Promise.all([loadHistorial(), loadKeyExercises(), loadStats(), loadUserRutina()]);
    } else if (selectedAlumnoId.value) {
        await Promise.all([loadHistorial(), loadKeyExercises(), loadStats()]);
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
