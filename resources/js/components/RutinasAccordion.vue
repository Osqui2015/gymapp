<template>
    <div
        class="bg-obsidian-950 text-slate-100 min-h-screen pb-28 antialiased selection:bg-indigo-500/30 selection:text-white"
    >
        <!-- TopBar Sticky (Exact Kinetic Obsidian Mockup) - Solo Mobile -->
        <header
            class="md:hidden sticky top-0 z-40 bg-obsidian-950/80 backdrop-blur-xl border-b border-slate-800/60 px-4 py-3"
        >
            <div class="max-w-md mx-auto flex items-center justify-between">
                <!-- Logo brand and title -->
                <div class="flex items-center gap-2.5">
                    <div
                        class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-indigo-400 flex items-center justify-center font-black text-white text-lg shadow-lg shadow-indigo-600/30 shrink-0"
                    >
                        G
                    </div>
                    <div>
                        <span class="font-display font-extrabold text-base tracking-tight text-white block leading-none">
                            GymApp
                        </span>
                        <span class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">
                            Kinetic Obsidian
                        </span>
                    </div>
                </div>

                <!-- Streak Badge & Notification Bell -->
                <div class="flex items-center gap-2">
                    <div
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 text-xs font-bold"
                    >
                        <span class="text-sm">🔥</span>
                        <span>{{ rachaActual > 0 ? `${rachaActual} DÍAS` : '2 DÍAS' }}</span>
                    </div>
                    <button
                        aria-label="Notificaciones"
                        class="h-9 w-9 rounded-xl bg-obsidian-850 border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white active:scale-95 transition-all"
                        type="button"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            ></path>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <SyncBadge :pending="offlinePending" :syncing="offlineSyncing" />

        <!-- Indicador pull-to-refresh (mobile) -->
        <div
            v-show="pullOffset > 4 || isRefreshing"
            :style="{ height: pullOffset + 'px' }"
            class="md:hidden flex items-center justify-center overflow-hidden transition-[height] duration-150 max-w-md mx-auto"
            aria-live="polite"
            role="status"
        >
            <div class="flex flex-col items-center gap-1 text-xs font-semibold text-slate-400">
                <svg
                    class="w-5 h-5 animate-spin text-indigo-500"
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
                    class="w-5 h-5 text-indigo-500"
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

        <main ref="swipeRef" class="max-w-md md:max-w-6xl lg:max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 md:pt-8 space-y-6 md:space-y-8">
            <!-- Vista para Alumnos (si aplica) -->
            <RutinasAlumnoView v-if="isAlumno" :user-rutina="userRutina" class="mb-4" />

            <template v-else>
                <!-- Header Section -->
                <section class="flex flex-col md:flex-row md:items-end md:justify-between gap-4" data-purpose="page-title-actions">
                    <div class="space-y-1.5">
                        <!-- Breadcrumb indicator -->
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1">
                            <a class="hover:text-slate-200 transition-colors" href="/dashboard">Inicio</a>
                            <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M9 5l7 7-7 7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                ></path>
                            </svg>
                            <span class="text-indigo-400 font-semibold">Rutinas</span>
                        </div>

                        <!-- Main Title -->
                        <h1 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white">
                            Explorar Rutinas
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-400 max-w-xl">
                            Selecciona, comparte o importa planes de entrenamiento estructurados.
                        </p>
                    </div>

                    <!-- Primary Action CTA -->
                    <a
                        href="/rutinas/crear"
                        class="w-full md:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white font-semibold text-sm flex items-center justify-center gap-2 glow-purple shadow-lg transition-all active:scale-[0.98] shrink-0"
                    >
                        <svg class="w-4 h-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4.5v15m7.5-7.5h-15" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span>Crear Nueva Rutina</span>
                    </a>
                </section>

                <!-- Filters and Search Section -->
                <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-obsidian-900/60 p-2 sm:p-2.5 rounded-2xl border border-slate-800/80" data-purpose="navigation-tabs-and-search">
                    <!-- Horizontal Pills Tabs -->
                    <nav aria-label="Filtros de catálogo" class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                        <button
                            type="button"
                            @click="catalogoTab = 'predeterminadas'"
                            :class="catalogoTab === 'predeterminadas'
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/20'
                                : 'bg-obsidian-850 hover:bg-obsidian-800 border border-slate-800 text-slate-300'"
                            class="whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-semibold transition-all"
                        >
                            📋 Rutinas Oficiales
                        </button>
                        <button
                            type="button"
                            @click="catalogoTab = 'personalizadas'"
                            :class="catalogoTab === 'personalizadas'
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/20'
                                : 'bg-obsidian-850 hover:bg-obsidian-800 border border-slate-800 text-slate-300'"
                            class="whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-semibold transition-all"
                        >
                            👤 Mis Rutinas (Personalizadas)
                        </button>
                        <button
                            type="button"
                            @click="catalogoTab = 'comunitarias'"
                            :class="catalogoTab === 'comunitarias'
                                ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/20'
                                : 'bg-obsidian-850 hover:bg-obsidian-800 border border-slate-800 text-slate-300'"
                            class="whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-semibold transition-all"
                        >
                            🌎 Catálogo Comunitario
                        </button>
                    </nav>

                    <!-- Search Input -->
                    <div class="relative w-full md:w-80 lg:w-96">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            class="w-full pl-10 pr-4 py-2 bg-obsidian-950/80 border border-slate-800 rounded-xl text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                            placeholder="Buscar rutina, músculo o ejercicio..."
                            type="text"
                        />
                    </div>
                </section>

                <!-- TAB 1: Rutinas Oficiales (Predeterminadas) -->
                <div v-show="catalogoTab === 'predeterminadas'" class="space-y-5">
                    <div
                        v-if="rutinasLoaded && Object.keys(defaultRutinas).length === 0"
                        class="p-8 rounded-2xl bg-obsidian-900 border border-slate-800 text-center space-y-2"
                        data-purpose="no-oficiales"
                    >
                        <span class="text-4xl block mb-2">📋</span>
                        <h3 class="font-bold text-white text-base">No hay rutinas oficiales disponibles</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            Si acabás de instalar la app, corré los seeders oficiales o contactá al administrador.
                        </p>
                    </div>

                    <div
                        v-for="nivelNombre in nivelesOrden"
                        :key="nivelNombre"
                        v-show="defaultRutinas[nivelNombre]"
                        class="space-y-3"
                        :data-purpose="`nivel-${nivelNombre}-${Object.keys(defaultRutinas[nivelNombre]?.modalidades || {}).length}mods`"
                    >
                        <!-- DEBUG: count: {{ Object.keys(defaultRutinas[nivelNombre]?.modalidades || {}).length }} -->

                        <!-- Section Header -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-1.5 h-5 rounded-full"
                                :class="getNivelMeta(nivelNombre).colorBar"
                            ></div>
                            <h2 class="text-lg font-display font-bold text-white tracking-wide">
                                {{ nivelNombre }}
                            </h2>
                            <span
                                class="text-[11px] px-2 py-0.5 rounded-md border font-semibold uppercase"
                                :class="getNivelMeta(nivelNombre).badgeClass"
                            >
                                {{ getNivelMeta(nivelNombre).badgeText }}
                            </span>
                        </div>

                        <!-- Routine Cards in this Level -->
                        <div class="space-y-3">
                            <RutinaAcordeon
                                v-for="modalidad in getOrderedModalidades(defaultRutinas[nivelNombre]?.modalidades, nivelNombre)"
                                :key="modalidad.nombre"
                                :modalidad="modalidad"
                                :nivel="nivelNombre"
                                :open="isAcordeonOpen(nivelNombre, modalidad.nombre)"
                                :open-dias="getOpenDias(nivelNombre, modalidad.nombre)"
                                show-select-button
                                :select-label="`${nivelNombre} - ${modalidad.nombre}`"
                                show-quick-input
                                @toggle="toggleAcordeon(nivelNombre, modalidad.nombre)"
                                @toggle-dia="(d) => toggleDia(nivelNombre, modalidad.nombre, d)"
                                @select="seleccionarRutina(nivelNombre, modalidad.nombre)"
                                @quick-input="openQuickInput"
                                @toggle-favorite="toggleFavorita"
                            />
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Mis Rutinas (Personalizadas) -->
                <div v-show="catalogoTab === 'personalizadas'" class="space-y-4">
                    <div
                        v-if="!personalRutinas || Object.keys(personalRutinas.modalidades || {}).length === 0"
                        class="p-8 rounded-2xl bg-obsidian-900 border border-slate-800 text-center space-y-3"
                    >
                        <div class="text-4xl">👤</div>
                        <h3 class="font-bold text-white text-base">No tenés rutinas personalizadas aún</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            Creá tus propias rutinas o importá planes desde el catálogo comunitario para empezar.
                        </p>
                        <a
                            href="/rutinas/crear"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white font-semibold text-xs glow-purple shadow-lg transition-all active:scale-[0.98]"
                        >
                            <svg class="w-4 h-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4.5v15m7.5-7.5h-15" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <span>Crear mi primera rutina</span>
                        </a>
                    </div>

                    <div v-else class="space-y-3">
                        <RutinaAcordeon
                            v-for="modalidad in filterModalidades(Object.values(personalRutinas.modalidades), 'Personalizada')"
                            :key="modalidad.nombre"
                            :modalidad="modalidad"
                            :nivel="'Personalizada'"
                            :open="isAcordeonOpen('Personalizada', modalidad.nombre)"
                            :open-dias="getOpenDias('Personalizada', modalidad.nombre)"
                            show-quick-input
                            @toggle="toggleAcordeon('Personalizada', modalidad.nombre)"
                            @toggle-dia="(d) => toggleDia('Personalizada', modalidad.nombre, d)"
                            @quick-input="openQuickInput"
                            @toggle-favorite="toggleFavorita"
                        >
                            <template #header-extra>
                                <span
                                    v-if="isRoutineShared(modalidad)"
                                    class="px-2.5 py-0.5 text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 rounded-full flex items-center gap-1"
                                >
                                    <span>🌎</span> Compartida
                                </span>
                            </template>
                            <template #footer>
                                <div
                                    class="p-3.5 bg-obsidian-850 border-t border-slate-800 flex flex-col sm:flex-row gap-2.5"
                                >
                                    <button
                                        type="button"
                                        @click="seleccionarRutina('Personalizada', modalidad.nombre)"
                                        class="flex-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white py-2.5 px-4 rounded-xl font-bold text-xs shadow-md transition-all active:scale-[0.98]"
                                    >
                                        Seleccionar {{ modalidad.nombre }}
                                    </button>
                                    <button
                                        type="button"
                                        v-if="!isRoutineShared(modalidad)"
                                        @click="compartirRutina('Personalizada', modalidad.nombre)"
                                        class="bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/40 text-indigo-300 py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all active:scale-[0.98]"
                                    >
                                        <span>🌎</span> Compartir
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminarRutina('Personalizada', modalidad.nombre)"
                                        class="bg-rose-600/20 hover:bg-rose-600/30 border border-rose-500/40 text-rose-300 py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all active:scale-[0.98]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </template>
                        </RutinaAcordeon>
                    </div>
                </div>

                <!-- TAB 3: Catálogo Comunitario -->
                <div v-show="catalogoTab === 'comunitarias'" class="space-y-4">
                    <div
                        v-if="Object.keys(communityRutinas).length === 0"
                        class="p-8 rounded-2xl bg-obsidian-900 border border-slate-800 text-center space-y-2"
                    >
                        <span class="text-4xl block mb-2">🌎</span>
                        <h3 class="font-bold text-white text-base">No hay rutinas compartidas en la comunidad aún</h3>
                        <p class="text-xs text-slate-400">
                            ¡Sé el primero en compartir una rutina personalizada con el resto de los usuarios!
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <RutinaAcordeon
                            v-for="modalidad in filterModalidades(Object.values(communityRutinas), 'Comunitaria')"
                            :key="`${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`"
                            :modalidad="modalidad"
                            :nivel="modalidad.nivel"
                            :open="isAcordeonOpen('Comunitaria', `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`)"
                            :open-dias="getOpenDias('Comunitaria', `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`)"
                            show-quick-input
                            @toggle="toggleAcordeon('Comunitaria', `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`)"
                            @toggle-dia="(d) => toggleDia('Comunitaria', `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`, d)"
                            @quick-input="openQuickInput"
                        >
                            <template #header-extra>
                                <span class="text-[11px] font-medium text-slate-400 italic">
                                    {{ modalidad.nivel }} · @{{ nicknameCreator(modalidad) }}
                                </span>
                            </template>
                            <template #dia-footer="{ dia }">
                                <div class="mt-2 flex justify-end">
                                    <button
                                        type="button"
                                        @click="importarRutina(modalidad, dia)"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/40 text-emerald-300 px-3 py-1.5 text-xs font-bold transition-all active:scale-[0.98]"
                                        :title="`Importar solo ${dia.nombre} a Mis Rutinas`"
                                    >
                                        <span>📥</span> Importar este día
                                    </button>
                                </div>
                            </template>
                            <template #footer>
                                <div class="p-3.5 bg-obsidian-850 border-t border-slate-800">
                                    <button
                                        type="button"
                                        @click="importarRutina(modalidad)"
                                        class="w-full bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white py-3 rounded-xl font-bold text-sm shadow-md transition-all active:scale-[0.98] flex items-center justify-center gap-2"
                                    >
                                        <span>📥</span> Importar rutina completa ({{ modalidad.dias.length }} días)
                                    </button>
                                </div>
                            </template>
                        </RutinaAcordeon>
                    </div>
                </div>
            </template>
        </main>

        <!-- Floating Action Button (FAB) - Solo Mobile -->
        <div class="md:hidden fixed right-4 bottom-20 z-40">
            <a
                href="/rutinas/crear"
                aria-label="Añadir rutina rápida"
                class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-indigo-400 text-white shadow-xl glow-purple active:scale-95 transition-transform flex items-center justify-center"
            >
                <svg class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4.5v15m7.5-7.5h-15" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>
        </div>

        <!-- Mobile Quick Series Input Sheet Modal -->
        <MobileQuickSeriesInput
            :open="quickInputOpen"
            :dia="quickInputDia"
            :saving="quickInputSaving"
            :ejercicios="quickInputEjercicios"
            @close="closeQuickInput"
            @save="saveQuickInput"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRutinaStore } from '../stores/rutina';
import axios from 'axios';
import { useConfetti } from '../composables/useConfetti';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useSwipe } from '../composables/useSwipe';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import SyncBadge from './training/SyncBadge.vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';
import RutinasAlumnoView from './rutinas/RutinasAlumnoView.vue';
import RutinaAcordeon from './rutinas/RutinaAcordeon.vue';
import MobileQuickSeriesInput from './rutinas/MobileQuickSeriesInput.vue';

const toast = useToast();
const confetti = useConfetti();

// === Modo offline ===
const {
    recordSet: offlineRecordSet,
    pendingCount: offlinePending,
    isSyncing: offlineSyncing,
} = useOfflineSeries();
const offline = { recordSet: offlineRecordSet };
const showNotification = (message, type = 'success') => toast.add(message, type);

const rutinaStore = useRutinaStore();
const auth = useAuthStore();
const { role: userRole, isAlumno } = storeToRefs(auth);

const catalogoTab = ref('predeterminadas');
const searchQuery = ref('');
const rutinasAgrupadas = ref({});
const rutinasLoaded = ref(false);
const comunitariasList = ref([]);

// Estado inicial: 'Principiante 2 Días' y 'Día 1' expandidos por defecto como en la captura
const openItems = ref({
    'acordeon-Principiante-2 Días': true,
    'dia-Principiante-2 Días-Día 1': true,
});

const isSelecting = ref(false);
const userRutina = ref(null);

const nivelesOrden = ['Principiante', 'Intermedio', 'Avanzado'];

const nivelMeta = {
    Principiante: {
        colorBar: 'bg-emerald-500',
        badgeClass: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
        badgeText: 'Recomendado',
    },
    Intermedio: {
        colorBar: 'bg-amber-500',
        badgeClass: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
        badgeText: 'Fuerza & Hipertrofia',
    },
    Avanzado: {
        colorBar: 'bg-rose-500',
        badgeClass: 'bg-rose-500/10 border-rose-500/20 text-rose-400',
        badgeText: 'Alto Rendimiento',
    },
};

const getNivelMeta = (nivel) => {
    return nivelMeta[nivel] || {
        colorBar: 'bg-indigo-500',
        badgeClass: 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400',
        badgeText: nivel,
    };
};

const triggerSuccessConfetti = () => {
    try {
        confetti.mini();
    } catch {
        /* ignore — CSP puede bloquear el worker de canvas-confetti */
    }
};

const defaultRutinas = computed(() => {
    const result = {};
    Object.keys(rutinasAgrupadas.value).forEach((nivel) => {
        if (nivel !== 'Personalizada') {
            result[nivel] = rutinasAgrupadas.value[nivel];
        }
    });
    return result;
});

const personalRutinas = computed(() => {
    return rutinasAgrupadas.value['Personalizada'] || null;
});

// Agrupación de rutinas comunitarias
const communityRutinas = computed(() => {
    const agrupadas = {};

    comunitariasList.value.forEach((r) => {
        const key = `${r.modalidad}-${r.nivel}-${r.created_by}`;
        if (!agrupadas[key]) {
            agrupadas[key] = {
                nombre: r.modalidad,
                nivel: r.nivel,
                created_by: r.created_by,
                creador_obj: r.creador,
                dias: {},
            };
        }
        if (!agrupadas[key].dias[r.dia]) {
            agrupadas[key].dias[r.dia] = { nombre: r.dia, ejercicios: [] };
        }
        agrupadas[key].dias[r.dia].ejercicios.push(r);
    });

    Object.keys(agrupadas).forEach((k) => {
        agrupadas[k].dias = Object.values(agrupadas[k].dias).sort((a, b) =>
            a.nombre.localeCompare(b.nombre)
        );
    });

    return agrupadas;
});

// Ordenar modalidades (ej. 2 Días, 3 Días, 4 Días)
const getOrderedModalidades = (modalidadesDict, nivel) => {
    if (!modalidadesDict) return [];
    const list = Object.values(modalidadesDict);
    list.sort((a, b) => {
        const numA = parseInt(a.nombre) || 0;
        const numB = parseInt(b.nombre) || 0;
        return numA - numB;
    });
    return filterModalidades(list, nivel);
};

// Filtrar por término de búsqueda (nombre, nivel o ejercicio)
const filterModalidades = (modalidadesList, nivel) => {
    if (!modalidadesList) return [];
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return modalidadesList;

    return modalidadesList.filter((mod) => {
        if (mod.nombre?.toLowerCase().includes(q)) return true;
        if (nivel && nivel.toLowerCase().includes(q)) return true;
        return (mod.dias || []).some((d) => {
            if (d.nombre?.toLowerCase().includes(q)) return true;
            return (d.ejercicios || []).some((e) => {
                if (e.ejercicio_nombre?.toLowerCase().includes(q)) return true;
                if (e.ejercicio?.grupo_muscular?.toLowerCase().includes(q)) return true;
                return false;
            });
        });
    });
};

const nicknameCreator = (modalidad) => {
    return (
        modalidad.creador_obj?.nick || modalidad.creador_obj?.name || `user-${modalidad.created_by}`
    );
};

const isRoutineShared = (modalidad) => {
    return (modalidad.dias || []).some((d) => (d.ejercicios || []).some((e) => e.publica));
};

const fetchUserInfo = async () => {
    try {
        await auth.fetchUser();
        if (isAlumno.value) {
            const rutinaResponse = await axios.get('/api/user-rutina');
            userRutina.value = rutinaResponse.data || null;
        }
    } catch (error) {
        userRutina.value = null;
    }
};

const toggleAcordeon = (nivel, modalidad) => {
    const key = `acordeon-${nivel}-${modalidad}`;
    openItems.value[key] = !openItems.value[key];
};

const isAcordeonOpen = (nivel, modalidad) => {
    return openItems.value[`acordeon-${nivel}-${modalidad}`] || false;
};

const toggleDia = (nivel, modalidad, dia) => {
    const key = `dia-${nivel}-${modalidad}-${dia}`;
    openItems.value[key] = !openItems.value[key];
};

const getOpenDias = (nivel, modalidad) => {
    const result = [];
    for (const key in openItems.value) {
        if (key.startsWith(`dia-${nivel}-${modalidad}-`) && openItems.value[key]) {
            result.push(key.substring(`dia-${nivel}-${modalidad}-`.length));
        }
    }
    return result;
};

const fetchRutinas = async () => {
    try {
        const response = await axios.get('/api/rutinas');
        const rutinas = response.data;

        const agrupadas = {};

        rutinas.forEach((r) => {
            if (!r.nivel) return;
            if (!agrupadas[r.nivel]) {
                agrupadas[r.nivel] = { modalidades: {} };
            }
            if (!agrupadas[r.nivel].modalidades[r.modalidad]) {
                agrupadas[r.nivel].modalidades[r.modalidad] = { nombre: r.modalidad, dias: {} };
            }
            if (!agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia]) {
                agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia] = {
                    nombre: r.dia,
                    ejercicios: [],
                };
            }
            agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia].ejercicios.push(r);
        });

        Object.keys(agrupadas).forEach((nivel) => {
            Object.keys(agrupadas[nivel].modalidades).forEach((mod) => {
                agrupadas[nivel].modalidades[mod] = {
                    nombre: mod,
                    dias: Object.values(agrupadas[nivel].modalidades[mod].dias).sort((a, b) =>
                        a.nombre.localeCompare(b.nombre)
                    ),
                };
            });
        });

        rutinasAgrupadas.value = agrupadas;
        rutinasLoaded.value = true;
    } catch (error) {
        console.error('Error al obtener rutinas:', error);
        rutinasLoaded.value = true;
    }
};

const fetchComunitarias = async () => {
    try {
        const response = await axios.get('/api/rutinas', { params: { comunitarias: true } });
        comunitariasList.value = response.data || [];
    } catch (error) {
        console.error('Error al cargar rutinas comunitarias:', error);
    }
};

const seleccionarRutina = async (nivel, modalidad) => {
    if (isSelecting.value) return;
    isSelecting.value = true;

    try {
        const rutinaId =
            rutinasAgrupadas.value?.[nivel]?.modalidades?.[modalidad]?.dias?.[0]?.ejercicios?.[0]
                ?.id;
        if (!rutinaId) {
            showNotification('No se encontró la rutina seleccionada. Refresca la página.', 'error');
            return;
        }

        await axios.post('/api/user-rutina', {
            rutina_id: rutinaId,
            dia_actual: 'Día 1',
        });

        rutinaStore.seleccionar(`${nivel} ${modalidad}`, 'Todos los días');
        window.location.href = '/dashboard';
    } catch (error) {
        console.error('Error al seleccionar rutina:', error);
        showNotification('No se pudo guardar la rutina. Intenta de nuevo.', 'error');
    } finally {
        isSelecting.value = false;
    }
};

const compartirRutina = async (nivel, modalidad) => {
    const ok = await toast.confirm(
        `¿Deseas compartir la rutina "${modalidad}" con la comunidad? Otros usuarios podrán verla e importarla.`,
        { title: 'Compartir rutina', confirmLabel: 'Sí, compartir' }
    );
    if (!ok) return;

    try {
        const response = await axios.post('/api/rutinas/compartir', { nivel, modalidad });
        showNotification(response.data.message || 'Rutina compartida con éxito.', 'success');

        if (response.data.public_url) {
            await navigator.clipboard?.writeText(response.data.public_url);
            showNotification(
                `🔗 Link público copiado al portapapeles: ${response.data.public_url}`,
                'success',
                { duration: 6000 }
            );
        }

        triggerSuccessConfetti();
        await fetchRutinas();
        await fetchComunitarias();
    } catch (error) {
        console.error('Error al compartir rutina:', error);
        showNotification('Error al compartir la rutina.', 'error');
    }
};

const importarRutina = async (modalidadObj, diaObj = null) => {
    try {
        const response = await axios.post('/api/rutinas/importar', {
            nivel: modalidadObj.nivel,
            modalidad: modalidadObj.nombre,
            created_by: modalidadObj.created_by,
            dia: diaObj?.nombre ?? null,
        });

        showNotification(response.data.message || 'Rutina importada con éxito.', 'success');
        triggerSuccessConfetti();

        await fetchRutinas();
        catalogoTab.value = 'personalizadas';
    } catch (error) {
        console.error('Error al importar rutina:', error);
        showNotification('Error al importar la rutina.', 'error');
    }
};

const eliminarRutina = async (nivel, modalidad) => {
    const confirmed = await toast.confirm(
        `¿Eliminar la rutina "${modalidad}"? Se mantendrá el historial de los alumnos, pero no podrán volver a seleccionarla.`,
        { title: 'Eliminar rutina', confirmLabel: 'Sí, eliminar', type: 'error' }
    );
    if (!confirmed) return;

    const snapshot = {
        personal: personalRutinas.value ? JSON.parse(JSON.stringify(personalRutinas.value)) : null,
        userRutina: userRutina.value ? { ...userRutina.value } : null,
    };

    const isCurrentUserRutina =
        userRutina.value &&
        userRutina.value.nivel === nivel &&
        userRutina.value.modalidad === modalidad;

    const { cancelled } = await useUndoable({
        message: `Rutina "${modalidad}" eliminada`,
        apply: () => {
            if (personalRutinas.value) {
                delete personalRutinas.value.modalidades[modalidad];
            }
            if (isCurrentUserRutina) {
                userRutina.value = null;
                rutinaStore.limpiar();
            }
        },
        undo: () => {
            if (snapshot.personal) {
                personalRutinas.value = JSON.parse(JSON.stringify(snapshot.personal));
            }
            if (isCurrentUserRutina && snapshot.userRutina) {
                userRutina.value = { ...snapshot.userRutina };
            }
        },
        commit: () =>
            axios.delete('/api/rutinas', {
                data: { nivel, modalidad },
            }),
        onError: (err) => {
            console.error('Error al eliminar la rutina:', err);
        },
    });

    if (!cancelled) {
        await fetchRutinas();
        await fetchComunitarias();
    }
};

// Toggle de favorito
const toggleFavorita = async ({ nivel, modalidad }) => {
    if (!nivel || !modalidad) return;

    const nivelData = rutinasAgrupadas.value[nivel];
    if (nivelData?.modalidades?.[modalidad]) {
        const mod = nivelData.modalidades[modalidad];
        mod.dias.forEach((dia) => {
            dia.ejercicios.forEach((ej) => {
                ej.is_favorita = !ej.is_favorita;
            });
        });
    }

    try {
        const res = await axios.post('/api/rutinas/favorite', { nivel, modalidad });
        const isFav = res.data?.is_favorita;

        if (nivelData?.modalidades?.[modalidad]) {
            const mod = nivelData.modalidades[modalidad];
            mod.dias.forEach((dia) => {
                dia.ejercicios.forEach((ej) => {
                    ej.is_favorita = isFav;
                });
            });
        }
        toast.success(isFav ? 'Agregada a favoritas ⭐' : 'Quitada de favoritas');
    } catch (e) {
        if (nivelData?.modalidades?.[modalidad]) {
            const mod = nivelData.modalidades[modalidad];
            mod.dias.forEach((dia) => {
                dia.ejercicios.forEach((ej) => {
                    ej.is_favorita = !ej.is_favorita;
                });
            });
        }
        toast.apiError(e, 'No se pudo actualizar la favorita');
    }
};

// === Quick Input ===
const quickInputOpen = ref(false);
const quickInputDia = ref(null);
const quickInputNombre = ref('');
const quickInputEjercicios = ref([]);
const quickInputSaving = ref(false);

const openQuickInput = (dia) => {
    quickInputDia.value = dia?.nombre || '';
    quickInputEjercicios.value = Array.isArray(dia?.ejercicios) ? dia.ejercicios : [];
    quickInputNombre.value = '';
    quickInputOpen.value = true;
};

const closeQuickInput = () => {
    quickInputOpen.value = false;
    quickInputDia.value = null;
    quickInputEjercicios.value = [];
};

const saveQuickInput = async ({ records }) => {
    if (!records || !records.length) return;
    quickInputSaving.value = true;
    try {
        const fecha = new Date().toISOString().split('T')[0];
        let allSynced = true;
        for (const r of records) {
            const result = await offline.recordSet({
                fecha,
                rutina_nombre: quickInputNombre.value || 'Rápida',
                dia: quickInputDia.value,
                ejercicio_nombre: r.ejercicio_nombre,
                series_numero: r.series_numero,
                series_completadas: r.completado ? 1 : 0,
                reps_min: r.reps_min,
                reps_max: r.reps_max,
                reps_realizadas: r.reps_realizadas,
                descanso_min: r.descanso_min,
                peso: r.peso,
                completado: r.completado,
                nota_user: r.nota_user,
            });
            if (result.status === 'queued' || result.status === 'lost') allSynced = false;
        }
        if (allSynced) {
            toast.success(`${records.length} series registradas ✓`);
            try {
                confetti.mini();
            } catch {
                /* ignore */
            }
        } else {
            toast.info(
                `Sin conexión: ${records.length} series guardadas en este dispositivo. Se sincronizarán automáticamente.`
            );
        }
        closeQuickInput();
    } catch (e) {
        toast.apiError(e, 'No se pudieron guardar las series.');
    } finally {
        quickInputSaving.value = false;
    }
};

// === Pull-to-refresh ===
const loadAll = async () => {
    await Promise.all([fetchRutinas(), fetchComunitarias()]);
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, loadAll);

// Racha actual
const rachaActual = ref(0);
const cargarRacha = async () => {
    try {
        const r = await axios.get('/api/stats/resumen');
        rachaActual.value = r.data?.current_streak ?? 0;
    } catch (e) {
        /* silencioso */
    }
};

// Swipe mobile
const swipeRef = ref(null);
const { onSwipeLeft, onSwipeRight } = useSwipe(swipeRef, { threshold: 60, timeout: 700 });
const tabsOrder = ['predeterminadas', 'personalizadas', 'comunitarias'];
onSwipeLeft(() => {
    const i = tabsOrder.indexOf(catalogoTab.value);
    if (i >= 0 && i < tabsOrder.length - 1) catalogoTab.value = tabsOrder[i + 1];
});
onSwipeRight(() => {
    const i = tabsOrder.indexOf(catalogoTab.value);
    if (i > 0) catalogoTab.value = tabsOrder[i - 1];
});

onMounted(() => {
    rutinaStore.hidratar();
    fetchUserInfo();
    fetchRutinas();
    fetchComunitarias();
    cargarRacha();
});
</script>
