<!--
  GlobalSearch — búsqueda global con:
   - Matching fuzzy (resalta el match en los resultados)
   - Búsquedas recientes (localStorage, max 8)
   - Navegación con teclado (↑↓, enter, esc)
   - Atajo ⌘K / Ctrl+K
-->
<template>
    <div class="relative" ref="rootRef">
        <!-- Input -->
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                v-model="query"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown.escape="open.value = false"
                @keydown.enter.prevent="confirmar"
                @keydown.down.prevent="moverSeleccion(1)"
                @keydown.up.prevent="moverSeleccion(-1)"
                type="text"
                placeholder="Buscar ejercicios, rutinas, alumnos... (⌘K)"
                class="w-72 sm:w-80 pl-9 pr-16 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                aria-label="Búsqueda global"
            />
            <kbd v-if="!query" class="hidden sm:inline-block absolute right-3 top-1/2 -translate-y-1/2 px-1.5 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded border border-gray-300 dark:border-gray-600">⌘ K</kbd>
            <button v-if="query" @click="query = ''; open.value = true" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" aria-label="Limpiar búsqueda">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Dropdown -->
        <transition name="search-dropdown">
            <div
                v-if="open && (query.length >= 2 || recientes.length)"
                class="absolute right-0 mt-2 w-screen max-w-md sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-[100]"
                role="listbox"
            >
                <!-- Loading -->
                <div v-if="loading" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin w-5 h-5 mx-auto mb-2 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Buscando...
                </div>

                <!-- Recientes (cuando query vacío) -->
                <div v-else-if="!query && recientes.length" class="py-2">
                    <div class="px-4 py-2 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <span>🕐 Recientes</span>
                        <button
                            @click="limpiarRecientes"
                            class="text-gray-400 hover:text-red-500 normal-case font-normal"
                        >
                            Limpiar
                        </button>
                    </div>
                    <ul>
                        <li
                            v-for="(r, i) in recientes"
                            :key="`rec-${i}`"
                            @click="seleccionarReciente(r)"
                            class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ r }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Sin resultados -->
                <div v-else-if="query.length >= 2 && !totalResultados" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Sin resultados para <span class="font-semibold">"{{ query }}"</span>
                    <p class="mt-1 text-xs">Probá con otro término o revisá la ortografía.</p>
                </div>

                <!-- Resultados -->
                <div v-else-if="totalResultados" class="max-h-[70vh] overflow-y-auto">
                    <!-- Ejercicios -->
                    <div v-if="resultados.ejercicios?.length">
                        <p class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            🏋️ Ejercicios ({{ resultados.ejercicios.length }})
                        </p>
                        <ul>
                            <li v-for="(ej, i) in resultados.ejercicios" :key="`ej-${ej.id}`">
                                <a
                                    :href="`/ejercicios?busqueda=${encodeURIComponent(query)}`"
                                    @click="open.value = false"
                                    class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700"
                                    :class="{ 'bg-indigo-50 dark:bg-indigo-950/30': indexSeleccionado === indexGlobal(i) }"
                                >
                                    <span class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0 text-base">🏋️</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate" v-html="highlight(ej.nombre)"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            <span v-if="ej.grupo_muscular">{{ ej.grupo_muscular }}</span>
                                            <span v-if="ej.grupo_muscular && ej.equipamiento"> · </span>
                                            <span v-if="ej.equipamiento">{{ ej.equipamiento }}</span>
                                        </p>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Rutinas -->
                    <div v-if="resultados.rutinas?.length">
                        <p class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            📋 Rutinas ({{ resultados.rutinas.length }})
                        </p>
                        <ul>
                            <li v-for="(r, i) in resultados.rutinas" :key="`r-${r.id}`">
                                <a
                                    :href="`/rutinas`"
                                    @click="open.value = false"
                                    class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700"
                                    :class="{ 'bg-indigo-50 dark:bg-indigo-950/30': indexSeleccionado === indexGlobal(i, 'rutinas') }"
                                >
                                    <span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0 text-base">📋</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate" v-html="highlight(`${r.nivel} ${r.modalidad}`)"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Rutina completa</p>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Alumnos -->
                    <div v-if="resultados.alumnos?.length">
                        <p class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            👥 Alumnos ({{ resultados.alumnos.length }})
                        </p>
                        <ul>
                            <li v-for="(a, i) in resultados.alumnos" :key="`a-${a.id}`">
                                <a
                                    :href="`/trainer/alumnos?buscar=${encodeURIComponent(a.nick)}`"
                                    @click="open.value = false"
                                    class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                    :class="{ 'bg-indigo-50 dark:bg-indigo-950/30': indexSeleccionado === indexGlobal(i, 'alumnos') }"
                                >
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0 text-purple-700 dark:text-purple-300 font-bold text-sm">
                                        {{ a.name?.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate" v-html="highlight(a.name)"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@<span v-html="highlight(a.nick)"></span> · {{ a.role }}</p>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer hint -->
                <div class="px-4 py-2 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
                    <span>↑↓ navegar · ↵ abrir · esc cerrar</span>
                    <span v-if="totalResultados" class="font-mono">{{ totalResultados }} resultado{{ totalResultados === 1 ? '' : 's' }}</span>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useFuzzySearch } from '../composables/useFuzzySearch';

const RECENT_KEY = 'global_search_recent';
const RECENT_MAX = 8;

const { fuzzyMatch, highlight, sortByRelevance } = useFuzzySearch();

const query = ref('');
const open = ref(false);
const loading = ref(false);
const resultados = ref({ ejercicios: [], rutinas: [], alumnos: [] });
const indexSeleccionado = ref(-1);
const rootRef = ref(null);
const recientes = ref(loadRecientes());

function loadRecientes() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
    } catch { return []; }
}

function saveRecientes() {
    try {
        localStorage.setItem(RECENT_KEY, JSON.stringify(recientes.value));
    } catch { /* ignore */ }
}

function addReciente(q) {
    if (!q || q.length < 2) return;
    const filtered = recientes.value.filter((r) => r !== q);
    filtered.unshift(q);
    recientes.value = filtered.slice(0, RECENT_MAX);
    saveRecientes();
}

function limpiarRecientes() {
    recientes.value = [];
    saveRecientes();
}

const totalResultados = computed(() => {
    return (resultados.value.ejercicios?.length || 0) +
           (resultados.value.rutinas?.length || 0) +
           (resultados.value.alumnos?.length || 0);
});

const indexGlobal = (i, categoria) => {
    if (!categoria || categoria === 'ejercicios') return i;
    const ejLen = resultados.value.ejercicios?.length || 0;
    if (categoria === 'rutinas') return ejLen + i;
    if (categoria === 'alumnos') return ejLen + (resultados.value.rutinas?.length || 0) + i;
    return i;
};

const totalItems = computed(() => {
    if (!query.value) return recientes.value.length;
    return totalResultados.value;
});

let searchTimer = null;
const onInput = () => {
    clearTimeout(searchTimer);
    if (query.value.length < 2) {
        resultados.value = { ejercicios: [], rutinas: [], alumnos: [] };
        indexSeleccionado.value = -1;
        return;
    }
    searchTimer = setTimeout(buscar, 200);
};

const onFocus = () => {
    open.value = true;
};

const onBlur = () => {
    // Pequeño delay para que el click en un item funcione
    setTimeout(() => { open.value = false; }, 150);
};

const buscar = async () => {
    if (query.value.length < 2) return;
    loading.value = true;
    indexSeleccionado.value = -1;
    try {
        const response = await axios.get('/api/search', { params: { q: query.value } });
        const data = response.data;

        // Re-ordenar los resultados por relevancia fuzzy local
        resultados.value = {
            ejercicios: sortByRelevance(data.ejercicios || [], query.value, 'nombre'),
            rutinas: sortByRelevance(data.rutinas || [], query.value, (r) => `${r.nivel} ${r.modalidad}`),
            alumnos: sortByRelevance(data.alumnos || [], query.value, (a) => `${a.name} ${a.nick}`),
        };
    } catch (err) {
        console.error('Search error:', err);
        resultados.value = { ejercicios: [], rutinas: [], alumnos: [] };
    } finally {
        loading.value = false;
    }
};

const moverSeleccion = (delta) => {
    if (!totalItems.value) return;
    indexSeleccionado.value = (indexSeleccionado.value + delta + totalItems.value) % totalItems.value;
};

const confirmar = () => {
    if (indexSeleccionado.value < 0) {
        // Sin selección explícita: guardar como reciente y abrir el primer link
        if (query.value) {
            addReciente(query.value);
            const firstLink = document.querySelector('[role="listbox"] a');
            if (firstLink) firstLink.click();
        }
    } else {
        // Si estamos viendo recientes, ejecutar el reciente
        if (!query.value && recientes.value[indexSeleccionado.value]) {
            seleccionarReciente(recientes.value[indexSeleccionado.value]);
            return;
        }
        const allLinks = document.querySelectorAll('[role="listbox"] a');
        if (allLinks[indexSeleccionado.value]) {
            addReciente(query.value);
            allLinks[indexSeleccionado.value].click();
        }
    }
};

const seleccionarReciente = (q) => {
    query.value = q;
    addReciente(q);
    open.value = true;
    buscar();
};

const handleClickOutside = (e) => {
    if (rootRef.value && !rootRef.value.contains(e.target)) open.value = false;
};

const handleKeydown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const input = rootRef.value?.querySelector('input');
        if (input) input.focus();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.search-dropdown-enter-active,
.search-dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.search-dropdown-enter-from,
.search-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
