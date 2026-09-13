<template>
    <div class="min-h-screen bg-gray-50 dark:bg-[var(--color-obsidian-base)] py-6 md:py-8">
        <SyncBadge :pending="offlinePending" :syncing="offlineSyncing" />
        <Breadcrumbs
            :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Rutinas' }]"
            class="px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto"
        />
        <!-- FAB (mobile only): crear nueva rutina (Kinetic Obsidian) -->
        <a
            href="/rutinas/crear"
            class="md:hidden fixed bottom-20 right-4 z-30 inline-flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] text-white shadow-[0_8px_24px_var(--color-violet-glow)] active:scale-95 transition-transform"
            aria-label="Crear nueva rutina"
            title="Crear nueva rutina"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
            </svg>
        </a>

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

        <!-- Swipe hint (mobile, primera vez) -->
        <div
            v-if="showSwipeHint"
            class="md:hidden mx-4 sm:mx-6 lg:mx-8 max-w-6xl mb-3 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-700 dark:text-indigo-300 flex items-center gap-2"
        >
            <span>👆</span>
            <span class="flex-1">Deslizá ← → para cambiar de pestaña</span>
            <button
                type="button"
                @click="showSwipeHint = false"
                aria-label="Cerrar aviso de navegación"
                class="text-indigo-500 hover:text-indigo-700 font-bold p-1 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
            >
                ✕
            </button>
        </div>

        <div ref="swipeRef" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Vista para Alumnos -->
            <RutinasAlumnoView v-if="isAlumno" :user-rutina="userRutina" class="mb-8" />
            <div v-else>
                <!-- Hero header (Kinetic Obsidian) -->
                <ObsidianHero
                    eyebrow="GESTIÓN DE RUTINAS"
                    title="Explorar Rutinas"
                    subtitle="Selecciona, comparte o importa planes de entrenamiento estructurados."
                    class="mb-5 md:mb-7"
                >
                    <template #actions>
                        <a
                            href="/rutinas/crear"
                            class="obs-cta-secondary !bg-white/15 !text-white !border-white/20 hover:!bg-white/25"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Crear Nueva Rutina
                        </a>
                    </template>
                </ObsidianHero>

                <!-- Search bar + CTA full width (mobile) -->
                <div class="flex flex-col sm:flex-row items-stretch gap-2 mb-5 md:mb-6">
                    <div class="relative flex-1">
                        <svg
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"
                            />
                        </svg>
                        <input
                            type="search"
                            placeholder="Buscar rutina, músculo o ejercicio…"
                            class="obs-input pl-10"
                        />
                    </div>
                    <a href="/rutinas/crear" class="obs-cta-primary sm:hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Crear Nueva Rutina
                    </a>
                </div>

                <!-- Navigation Tabs (Kinetic Obsidian segmented) -->
                <div class="mb-5 md:mb-7">
                    <ObsidianSegmentedTabs
                        v-model="catalogoTab"
                        :tabs="[
                            { id: 'predeterminadas', label: 'Rutinas Oficiales', icon: '📋' },
                            { id: 'personalizadas', label: 'Mis Rutinas', icon: '👤' },
                            { id: 'comunitarias', label: 'Catálogo Comunitario', icon: '🌎' },
                        ]"
                    />
                </div>

                <!-- TAB: Rutinas Predeterminadas (Oficiales) -->
                <div v-show="catalogoTab === 'predeterminadas'" class="space-y-6">
                    <div
                        v-for="(nivelData, nivelNombre) in defaultRutinas"
                        :key="nivelNombre"
                        class="mb-10"
                    >
                        <div class="flex items-center mb-4">
                            <div
                                :class="getNivelColor(nivelNombre)"
                                class="w-3 h-8 rounded-full mr-3"
                            ></div>
                            <h3 class="text-2xl font-extrabold text-gray-800 dark:text-white">
                                {{ nivelNombre }}
                            </h3>
                        </div>

                        <RutinaAcordeon
                            v-for="modalidad in nivelData.modalidades"
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
                            class="mb-6"
                        />
                    </div>
                </div>

                <!-- TAB: Rutinas Personalizadas (Mis Rutinas) -->
                <div v-show="catalogoTab === 'personalizadas'" class="space-y-6">
                    <div
                        v-if="
                            !personalRutinas ||
                            Object.keys(personalRutinas.modalidades).length === 0
                        "
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <EmptyState
                            emoji="👤"
                            title="No tenés rutinas personalizadas aún"
                            description="Creá tus propias rutinas o importá planes desde el catálogo comunitario para empezar."
                        >
                            <template #cta>
                                <a
                                    href="/rutinas/crear"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-all"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                        />
                                    </svg>
                                    Crear mi primera rutina
                                </a>
                            </template>
                        </EmptyState>
                    </div>

                    <div v-else>
                        <RutinaAcordeon
                            v-for="modalidad in personalRutinas.modalidades"
                            :key="modalidad.nombre"
                            :modalidad="modalidad"
                            :nivel="'Personalizada'"
                            :open="isAcordeonOpen('Personalizada', modalidad.nombre)"
                            :open-dias="getOpenDias('Personalizada', modalidad.nombre)"
                            title-class="text-indigo-600 dark:text-indigo-400"
                            show-quick-input
                            @toggle="toggleAcordeon('Personalizada', modalidad.nombre)"
                            @toggle-dia="(d) => toggleDia('Personalizada', modalidad.nombre, d)"
                            @quick-input="openQuickInput"
                            @toggle-favorite="toggleFavorita"
                            class="mb-6"
                        >
                            <template #header-extra>
                                <span
                                    v-if="isRoutineShared(modalidad)"
                                    class="px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-full flex items-center gap-1 shadow-sm"
                                >
                                    <span>🌎</span> Compartida
                                </span>
                            </template>
                            <template #footer>
                                <div
                                    class="p-5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/20 dark:to-gray-800/20 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-3"
                                >
                                    <button
                                        @click="
                                            seleccionarRutina('Personalizada', modalidad.nombre)
                                        "
                                        class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                    >
                                        Seleccionar {{ modalidad.nombre }}
                                    </button>
                                    <button
                                        v-if="!isRoutineShared(modalidad)"
                                        @click="compartirRutina('Personalizada', modalidad.nombre)"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-1.5"
                                    >
                                        <span>🌎</span> Compartir
                                    </button>
                                    <button
                                        @click="eliminarRutina('Personalizada', modalidad.nombre)"
                                        class="ripple bg-red-600 hover:bg-red-700 text-white px-5 py-3.5 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-1.5"
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

                <!-- TAB: Catálogo Comunitario -->
                <div v-show="catalogoTab === 'comunitarias'" class="space-y-6">
                    <div
                        v-if="Object.keys(communityRutinas).length === 0"
                        class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 shadow-sm"
                    >
                        <span class="text-4xl block mb-2">🌎</span>
                        <p class="font-bold text-gray-700 dark:text-gray-300">
                            No hay rutinas compartidas en la comunidad aún.
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ¡Sé el primero en compartir una rutina personalizada con el resto de los
                            usuarios!
                        </p>
                    </div>

                    <div v-else>
                        <RutinaAcordeon
                            v-for="modalidad in communityRutinas"
                            :key="`${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`"
                            :modalidad="modalidad"
                            :open="
                                isAcordeonOpen(
                                    'Comunitaria',
                                    `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`
                                )
                            "
                            :open-dias="
                                getOpenDias(
                                    'Comunitaria',
                                    `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`
                                )
                            "
                            show-quick-input
                            @toggle="
                                toggleAcordeon(
                                    'Comunitaria',
                                    `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`
                                )
                            "
                            @toggle-dia="
                                (d) =>
                                    toggleDia(
                                        'Comunitaria',
                                        `${modalidad.nombre}-${modalidad.nivel}-${modalidad.created_by}`,
                                        d
                                    )
                            "
                            @quick-input="openQuickInput"
                            class="mb-6"
                        >
                            <template #header-extra>
                                <span
                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 italic"
                                >
                                    {{ modalidad.nivel }} · Creado por: @{{
                                        nicknameCreator(modalidad)
                                    }}
                                </span>
                            </template>
                            <template #dia-footer="{ dia }">
                                <div class="mt-3 flex justify-end">
                                    <button
                                        @click="importarRutina(modalidad, dia)"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-500 to-green-500 hover:from-emerald-600 hover:to-green-600 text-white px-3 py-2 text-xs font-bold shadow-sm hover:shadow-md transition-all"
                                        :title="`Importar solo ${dia.nombre} a Mis Rutinas`"
                                    >
                                        <span>📥</span> Importar este día
                                    </button>
                                </div>
                            </template>
                            <template #footer>
                                <div
                                    class="p-5 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/20 dark:to-green-950/20 border-t border-gray-200 dark:border-gray-700"
                                >
                                    <button
                                        @click="importarRutina(modalidad)"
                                        class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white px-6 py-4 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-1.5"
                                    >
                                        <span>📥</span> Importar rutina completa ({{
                                            modalidad.dias.length
                                        }}
                                        días)
                                    </button>
                                </div>
                            </template>
                        </RutinaAcordeon>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile quick series input sheet -->
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
import confetti from 'canvas-confetti';
import EmptyState from './EmptyState.vue';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { usePullToRefresh } from '../composables/usePullToRefresh';
import { useSwipe } from '../composables/useSwipe';
import { useOfflineSeries } from '@/composables/useOfflineSeries';
import SyncBadge from './training/SyncBadge.vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';
import Breadcrumbs from './Breadcrumbs.vue';
import RutinasAlumnoView from './rutinas/RutinasAlumnoView.vue';
import RutinaAcordeon from './rutinas/RutinaAcordeon.vue';
import MobileQuickSeriesInput from './rutinas/MobileQuickSeriesInput.vue';
import ObsidianHero from './common/obsidian/ObsidianHero.vue';
import ObsidianSegmentedTabs from './common/obsidian/ObsidianSegmentedTabs.vue';

const toast = useToast();

// === Modo offline (Oleada 1) ===
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
const rutinasAgrupadas = ref({});
const comunitariasList = ref([]);
const openItems = ref({});
const isSelecting = ref(false);
const userRutina = ref(null);

const triggerSuccessConfetti = () => {
    confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.6 },
    });
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

// Group community routines
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

    // Convert dias to sorted arrays
    Object.keys(agrupadas).forEach((k) => {
        agrupadas[k].dias = Object.values(agrupadas[k].dias).sort((a, b) =>
            a.nombre.localeCompare(b.nombre)
        );
    });

    return agrupadas;
});

const nicknameCreator = (modalidad) => {
    return (
        modalidad.creador_obj?.nick || modalidad.creador_obj?.name || `user-${modalidad.created_by}`
    );
};

const isRoutineShared = (modalidad) => {
    // If any exercise in this custom routine has publica = true
    return modalidad.dias.some((d) => d.ejercicios.some((e) => e.publica));
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

const getNivelColor = (nivel) => {
    const colors = {
        Principiante: 'bg-green-500',
        Intermedio: 'bg-yellow-500',
        Avanzado: 'bg-red-500',
    };
    return colors[nivel] || 'bg-gray-500';
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

const isDiaOpen = (nivel, modalidad, dia) => {
    return openItems.value[`dia-${nivel}-${modalidad}-${dia}`] || false;
};

// Devuelve los nombres de los días abiertos para una (nivel, modalidad) dada.
// Lo usamos para pasar `openDias` como array al componente RutinaAcordeon.
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
    } catch (error) {
        console.error('Error:', error);
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
        // D1: el backend ahora pide `rutina_id` (FK a la tabla rutinas). Las
        // columnas denormalizadas nivel/modalidad fueron dropeadas, asi que
        // buscamos el id de la primera fila que matchee (nivel+modalidad) en
        // la lista ya cargada.
        const rutinaId =
            rutinasAgrupadas.value?.[nivel]?.modalidades?.[modalidad]?.dias?.[0]?.ejercicios?.[0]
                ?.id;
        if (!rutinaId) {
            showNotification('No se encontro la rutina seleccionada. Refresca la pagina.', 'error');
            return;
        }

        await axios.post('/api/user-rutina', {
            rutina_id: rutinaId,
            dia_actual: 'Día 1',
        });

        rutinaStore.seleccionar(`${nivel} ${modalidad}`, 'Todos los días');
        window.location.href = '/dashboard';
    } catch (error) {
        console.error('Error:', error);
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

        // Si el backend devuelve un link público, mostrarlo
        if (response.data.public_url) {
            await navigator.clipboard?.writeText(response.data.public_url);
            showNotification(
                `🔗 Link público copiado al portapapeles: ${response.data.public_url}`,
                'success',
                { duration: 6000 }
            );
        }

        // Confetti if new achievements unlocked
        if (response.data.new_medals && response.data.new_medals.length > 0) {
            response.data.new_medals.forEach((medal) => {
                showNotification(
                    `🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`,
                    'success'
                );
            });
            triggerSuccessConfetti();
        } else {
            triggerSuccessConfetti();
        }

        await fetchRutinas();
        await fetchComunitarias();
    } catch (error) {
        console.error('Error al compartir rutina:', error);
        showNotification('Error al compartir la rutina.', 'error');
    }
};

const importarRutina = async (modalidadObj, diaObj = null) => {
    try {
        // Pasamos el nivel ORIGINAL (no "Personalizada") y, si viene un dia,
        // el backend filtra para importar solo ese dia. Si no viene dia, importa
        // todos los dias de la rutina completa.
        const response = await axios.post('/api/rutinas/importar', {
            nivel: modalidadObj.nivel,
            modalidad: modalidadObj.nombre,
            created_by: modalidadObj.created_by,
            dia: diaObj?.nombre ?? null,
        });

        showNotification(response.data.message || 'Rutina importada con éxito.', 'success');
        triggerSuccessConfetti();

        await fetchRutinas();
        // Switch to personalized tab
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

    // Snapshot completo de TODAS las rutinas personales y comunitarias
    // (necesario porque el delete de una rutina puede tocar varios lugares).
    const snapshot = {
        personal: personalRutinas.value ? JSON.parse(JSON.stringify(personalRutinas.value)) : null,
        userRutina: userRutina.value ? { ...userRutina.value } : null,
    };

    // Buscar la rutina para conocer su grupo/nivel/modalidad exactos
    const rutinaObj = snapshot.personal?.find(
        (r) => r.nivel === nivel && r.modalidad === modalidad
    );

    // Verificar si la rutina eliminada es la que el usuario tiene asignada
    const isCurrentUserRutina =
        userRutina.value &&
        userRutina.value.nivel === nivel &&
        userRutina.value.modalidad === modalidad;

    // Patrón undo real: optimistic + commit diferido + cancel
    const { cancelled } = await useUndoable({
        message: `Rutina "${modalidad}" eliminada`,
        apply: () => {
            // 1) Quitar de la lista visual
            if (personalRutinas.value) {
                personalRutinas.value = personalRutinas.value.filter(
                    (r) => !(r.nivel === nivel && r.modalidad === modalidad)
                );
            }
            // 2) Si era la rutina del usuario, limpiar el store
            if (isCurrentUserRutina) {
                userRutina.value = null;
                rutinaStore.limpiar();
            }
        },
        undo: () => {
            // Restaurar la lista de rutinas personales
            if (snapshot.personal) {
                personalRutinas.value = JSON.parse(JSON.stringify(snapshot.personal));
            }
            // Restaurar la rutina del usuario
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

    // Solo refrescamos del server si el delete fue confirmado (no cancelado)
    if (!cancelled) {
        await fetchRutinas();
        await fetchComunitarias();
    }
};

onMounted(() => {
    rutinaStore.hidratar();
    fetchUserInfo();
    fetchRutinas();
    fetchComunitarias();
});

// === Mejora 2.5: Mobile Quick Input ===
const quickInputOpen = ref(false);
const quickInputDia = ref(null);
const quickInputNombre = ref('');
const quickInputEjercicios = ref([]);
const quickInputSaving = ref(false);

const openQuickInput = (dia) => {
    // El argumento es el objeto día emitido por RutinaAcordeon
    quickInputDia.value = dia?.nombre || '';
    quickInputEjercicios.value = Array.isArray(dia?.ejercicios) ? dia.ejercicios : [];
    // El nombre de la rutina: lo inferimos del contexto (no tenemos acceso
    // directo al "nivel-modalidad", usamos el día como etiqueta).
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
        // === Modo offline (Oleada 1) ===
        // Antes: un unico axios.post que fallaba si no habia red.
        // Ahora: usa useOfflineSeries, que encola si no hay conexion y sincroniza
        // automaticamente al volver online. La API es la misma que axios.post pero
        // devuelve { status: 'synced' | 'queued' | 'lost' }.
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
                confetti({ particleCount: 60, spread: 70, origin: { y: 0.7 } });
            } catch {
                /* ignore */
            }
        } else {
            toast.info(
                `Sin conexion: ${records.length} series guardadas en este dispositivo. Se sincronizaran automaticamente.`
            );
        }
        closeQuickInput();
    } catch (e) {
        toast.apiError(e, 'No se pudieron guardar las series.');
    } finally {
        quickInputSaving.value = false;
    }
};

// === QW2: Rutinas favoritas ===
// Toggle del flag is_favorita para TODA la modalidad (todos los días).
// Actualiza el flag localmente (optimista) y refresca la lista al final
// para garantizar consistencia con el backend.
const toggleFavorita = async ({ nivel, modalidad }) => {
    if (!nivel || !modalidad) return;

    // Optimistic update: invertimos el flag en todos los ejercicios del grupo
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

        // Asegurar consistencia final (por si el optimistic update fue incorrecto)
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
        // Rollback
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

// === Mejora 1.9: Pull-to-refresh ===
const loadAll = async () => {
    await Promise.all([fetchRutinas(), fetchComunitarias()]);
};
const { isPulling, isRefreshing, pullOffset } = usePullToRefresh(window, loadAll);

// === Mejora 2.2: Swipe entre tabs en mobile ===
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

// Indicador de swipe (sólo mobile)
const showSwipeHint = ref(false);
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
