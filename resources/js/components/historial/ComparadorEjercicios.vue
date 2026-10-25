<template>
    <section
        class="bg-[#131924] border border-[#232D42] rounded-xl shadow-lg overflow-hidden"
    >
        <div class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex items-center gap-2.5">
            <div class="w-2.5 h-2.5 rounded bg-emerald-400"></div>
            <div>
                <h3 class="text-sm font-bold text-white tracking-wide uppercase">
                    Comparar Progreso entre Dos Fechas
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Cuánto subiste entre dos períodos en un ejercicio específico
                </p>
            </div>
        </div>

        <div class="p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Ejercicio
                    </label>
                    <input
                        v-model="form.ejercicio"
                        type="text"
                        list="ejercicios-list"
                        placeholder="Ej: Press banca"
                        class="w-full bg-[#18202F] border border-[#232D42] text-xs text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <datalist id="ejercicios-list">
                        <option v-for="e in ejerciciosDisponibles" :key="e" :value="e" />
                    </datalist>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Desde
                    </label>
                    <input
                        v-model="form.desde"
                        type="date"
                        :max="form.hasta"
                        class="w-full bg-[#18202F] border border-[#232D42] text-xs font-mono text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">
                        Hasta
                    </label>
                    <input
                        v-model="form.hasta"
                        type="date"
                        :min="form.desde"
                        :max="hoy"
                        class="w-full bg-[#18202F] border border-[#232D42] text-xs font-mono text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-[#232D42]/60">
                <span class="text-[11px] text-slate-400 font-medium mr-1">Períodos rápidos:</span>
                <button
                    v-for="p in PERIodos"
                    :key="p.label"
                    type="button"
                    @click="aplicarPeriodo(p.semanas)"
                    class="px-2.5 py-1 text-xs font-medium rounded-md bg-[#18202F] text-slate-300 hover:text-white hover:bg-indigo-600/30 border border-[#232D42] transition-colors cursor-pointer"
                >
                    {{ p.label }}
                </button>
            </div>

            <div v-if="loading" class="text-center text-xs text-slate-400 py-6">
                Calculando...
            </div>

            <div v-else-if="resultado">
                <div
                    v-if="resultado.desde_stats.sets === 0 && resultado.hasta_stats.sets === 0"
                    class="text-center text-xs text-slate-400 py-6"
                >
                    No hay sets registrados para <strong class="text-white">{{ resultado.ejercicio }}</strong> en el
                    período seleccionado.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-[#101521] border border-[#232D42] p-4 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">
                            Peso máximo
                        </p>
                        <div class="flex items-center justify-center gap-2 font-mono text-xs text-slate-400">
                            <span>{{ formatNum(resultado.desde_stats.peso_max) }}kg</span>
                            <span>→</span>
                            <span class="text-white">{{ formatNum(resultado.hasta_stats.peso_max) }}kg</span>
                        </div>
                        <p :class="['text-2xl sm:text-3xl font-extrabold font-mono mt-1', diffClass(resultado.diff.peso_max)]">
                            {{ formatDelta(resultado.diff.peso_max) }}<span class="text-xs">kg</span>
                        </p>
                        <p :class="['text-xs font-mono font-semibold', diffClass(resultado.diff.peso_max)]">
                            ({{ formatDelta(resultado.diff.peso_max_pct) }}%)
                        </p>
                    </div>

                    <div class="rounded-xl bg-[#101521] border border-[#232D42] p-4 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">
                            Volumen total
                        </p>
                        <div class="flex items-center justify-center gap-2 font-mono text-xs text-slate-400">
                            <span>{{ formatNum(resultado.desde_stats.volumen_total) }}</span>
                            <span>→</span>
                            <span class="text-white">{{ formatNum(resultado.hasta_stats.volumen_total) }}</span>
                        </div>
                        <p :class="['text-2xl sm:text-3xl font-extrabold font-mono mt-1', diffClass(resultado.diff.volumen_total)]">
                            {{ formatDelta(resultado.diff.volumen_total) }}
                        </p>
                        <p :class="['text-xs font-mono font-semibold', diffClass(resultado.diff.volumen_total)]">
                            ({{ formatDelta(resultado.diff.volumen_pct) }}%)
                        </p>
                    </div>

                    <div class="rounded-xl bg-[#101521] border border-[#232D42] p-4 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-1">
                            Reps promedio
                        </p>
                        <div class="flex items-center justify-center gap-2 font-mono text-xs text-slate-400">
                            <span>{{ formatNum(resultado.desde_stats.reps_promedio) }}</span>
                            <span>→</span>
                            <span class="text-white">{{ formatNum(resultado.hasta_stats.reps_promedio) }}</span>
                        </div>
                        <p :class="['text-2xl sm:text-3xl font-extrabold font-mono mt-1', diffClass(resultado.diff.reps_promedio, true)]">
                            {{ formatDelta(resultado.diff.reps_promedio) }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">por set</p>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 font-mono text-center mt-3">
                    Comparamos la primera mitad del período contra la segunda mitad ({{ resultado.dias }}
                    días, {{ resultado.desde_stats.sets + resultado.hasta_stats.sets }} sets totales).
                </p>
            </div>

            <div v-else class="text-center text-xs text-slate-400 py-6">
                Empezá escribiendo un ejercicio y un rango de fechas.
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    ejercicios: { type: Array, default: () => [] },
    userId: { type: Number, default: null },
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

const ejerciciosDisponibles = computed(() => {
    const set = new Set();
    (props.ejercicios || []).forEach((e) =>
        set.add(typeof e === 'string' ? e : e.ejercicio_nombre || e.nombre)
    );
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

const diffClass = (delta, invertirColor = false) => {
    const positivo = invertirColor ? delta < 0 : delta > 0;
    if (delta === 0) return 'text-slate-400';
    return positivo ? 'text-emerald-400' : 'text-rose-400';
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
        const params = { ...form.value };
        // Trainer/admin: comparar el alumno que se está viendo, no el propio.
        if (props.userId) params.user_id = props.userId;
        const { data } = await axios.get('/api/historial/comparar', {
            params,
        });
        resultado.value = data;
    } catch {
        resultado.value = null;
    } finally {
        loading.value = false;
    }
};

watch(() => ({ ...form.value }), fetchConDebounce, { deep: true });

onMounted(() => {
    if (ejerciciosDisponibles.value.length > 0 && !form.value.ejercicio) {
        form.value.ejercicio = ejerciciosDisponibles.value[0];
    }
});
</script>
