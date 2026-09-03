<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-2xl">⚖️</span>
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Comparar progreso por ejercicio</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Cuánto subiste entre dos fechas en un ejercicio específico</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Ejercicio</label>
                <input
                    v-model="form.ejercicio"
                    type="text"
                    list="ejercicios-list"
                    placeholder="Ej: Press banca"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm"
                />
                <datalist id="ejercicios-list">
                    <option v-for="e in ejerciciosDisponibles" :key="e" :value="e" />
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Desde</label>
                <input v-model="form.desde" type="date" :max="form.hasta" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
                <input v-model="form.hasta" type="date" :min="form.desde" :max="hoy" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Períodos rápidos:</span>
            <button
                v-for="p in PERIodos"
                :key="p.label"
                @click="aplicarPeriodo(p.semanas)"
                :class="['px-2.5 py-1 text-xs font-semibold rounded-lg border transition-all',
                    'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30']"
            >
                {{ p.label }}
            </button>
        </div>

        <div v-if="loading" class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">Calculando...</div>

        <div v-else-if="resultado">
            <div v-if="resultado.desde_stats.sets === 0 && resultado.hasta_stats.sets === 0" class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">
                No hay sets registrados para <strong>{{ resultado.ejercicio }}</strong> en el período seleccionado.
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 p-4 text-center">
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold mb-1">Peso máximo</p>
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.desde_stats.peso_max) }}kg</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.hasta_stats.peso_max) }}kg</span>
                    </div>
                    <p :class="['text-3xl font-black font-mono mt-1', diffClass(resultado.diff.peso_max)]">
                        {{ formatDelta(resultado.diff.peso_max) }}<span class="text-sm">kg</span>
                    </p>
                    <p :class="['text-xs font-semibold', diffClass(resultado.diff.peso_max)]">
                        ({{ formatDelta(resultado.diff.peso_max_pct) }}%)
                    </p>
                </div>

                <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 p-4 text-center">
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold mb-1">Volumen total</p>
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.desde_stats.volumen_total) }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.hasta_stats.volumen_total) }}</span>
                    </div>
                    <p :class="['text-3xl font-black font-mono mt-1', diffClass(resultado.diff.volumen_total)]">
                        {{ formatDelta(resultado.diff.volumen_total) }}
                    </p>
                    <p :class="['text-xs font-semibold', diffClass(resultado.diff.volumen_total)]">
                        ({{ formatDelta(resultado.diff.volumen_pct) }}%)
                    </p>
                </div>

                <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 p-4 text-center">
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold mb-1">Reps promedio</p>
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.desde_stats.reps_promedio) }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <span class="text-xs text-gray-500">{{ formatNum(resultado.hasta_stats.reps_promedio) }}</span>
                    </div>
                    <p :class="['text-3xl font-black font-mono mt-1', diffClass(resultado.diff.reps_promedio, true)]">
                        {{ formatDelta(resultado.diff.reps_promedio) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">por set</p>
                </div>
            </div>

            <p class="text-[10px] text-gray-400 text-center mt-3">
                Comparamos la primera mitad del período contra la segunda mitad
                ({{ resultado.dias }} días, {{ resultado.desde_stats.sets + resultado.hasta_stats.sets }} sets totales).
            </p>
        </div>

        <div v-else class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">
            Empezá escribiendo un ejercicio y un rango de fechas.
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    ejercicios: { type: Array, default: () => [] },
});

const hoy = new Date().toISOString().split('T')[0];
const hace8sem = new Date();
hace8sem.setDate(hace8sem.getDate() - 7 * 8);
const desdeDefault = hace8sem.toISOString().split('T')[0];

const PERIodos = [
    { label: '4 semanas', semanas: 4 },
    { label: '8 semanas', semanas: 8 },
    { label: '12 semanas', semanas: 12 },
    { label: '6 meses', semanas: 26 },
];

const form = ref({
    ejercicio: '',
    desde: desdeDefault,
    hasta: hoy,
});
const resultado = ref(null);
const loading = ref(false);

// Lista única de ejercicios para el datalist
const ejerciciosDisponibles = computed(() => {
    const set = new Set();
    (props.ejercicios || []).forEach((e) => set.add(typeof e === 'string' ? e : e.ejercicio_nombre || e.nombre));
    return Array.from(set).filter(Boolean).sort();
});

const aplicarPeriodo = (semanas) => {
    const d = new Date();
    d.setDate(d.getDate() - 7 * semanas);
    form.value.desde = d.toISOString().split('T')[0];
    form.value.hasta = hoy;
};

const formatNum = (n) => {
    if (n == null) return '—';
    return Number(n).toLocaleString('es-AR', { maximumFractionDigits: 1 });
};

const formatDelta = (n) => {
    if (n == null) return '—';
    const sign = n > 0 ? '+' : '';
    return sign + Number(n).toLocaleString('es-AR', { maximumFractionDigits: 1 });
};

// Para reps_promedio, RESTAR reps es bueno (más reps a menos peso) → invertimos el color
const diffClass = (delta, invertirColor = false) => {
    const positivo = invertirColor ? delta < 0 : delta > 0;
    if (delta === 0) return 'text-gray-500 dark:text-gray-400';
    return positivo
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-rose-600 dark:text-rose-400';
};

const fetchConDebounce = (() => {
    let t = null;
    return () => {
        clearTimeout(t);
        t = setTimeout(() => comparar(), 400);
    };
})();

const comparar = async () => {
    if (!form.value.ejercicio || !form.value.desde || !form.value.hasta) {
        resultado.value = null;
        return;
    }
    loading.value = true;
    try {
        const { data } = await axios.get('/api/historial/comparar', {
            params: form.value,
        });
        resultado.value = data;
    } catch (e) {
        resultado.value = null;
    } finally {
        loading.value = false;
    }
};

watch(() => ({ ...form.value }), fetchConDebounce, { deep: true });

onMounted(() => {
    // Si la prop `ejercicios` viene con datos, auto-seteamos el primero
    if (ejerciciosDisponibles.value.length > 0 && !form.value.ejercicio) {
        form.value.ejercicio = ejerciciosDisponibles.value[0];
    }
});
</script>
