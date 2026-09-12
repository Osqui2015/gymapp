<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700"
    >
        <div class="flex justify-between items-center mb-5">
            <h3
                class="text-base font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2"
            >
                <span>🛌</span> Descanso y Recuperación
            </h3>
            <span
                v-if="registrado"
                class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400"
            >
                Registrado
            </span>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
            Monitorea cómo impacta tu sueño, estrés y dolor muscular (DOMS) en tu rendimiento en el
            gimnasio.
        </p>

        <form @submit.prevent="guardarBienestar" class="space-y-4">
            <!-- Horas de sueño -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                        Horas de Sueño
                    </label>
                    <span class="text-xs font-bold font-mono text-indigo-600 dark:text-indigo-400">
                        {{ form.horas_sueno ? form.horas_sueno + ' hrs' : '—' }}
                    </span>
                </div>
                <input
                    v-model.number="form.horas_sueno"
                    type="number"
                    step="0.5"
                    min="0"
                    max="24"
                    placeholder="Ej: 7.5"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                />
            </div>

            <!-- Calidad del sueño (1-5) -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5">
                    Calidad de Sueño
                </label>
                <div class="grid grid-cols-5 gap-1.5">
                    <button
                        v-for="val in [1, 2, 3, 4, 5]"
                        :key="'sueno-' + val"
                        type="button"
                        @click="form.calidad_sueno = val"
                        :class="[
                            'py-2 px-1 text-center rounded-lg border text-xs font-bold transition-all',
                            form.calidad_sueno === val
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                : 'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <span class="block text-sm">{{ suenoEmojis[val - 1] }}</span>
                        <span class="text-[10px] block mt-0.5">{{ val }}</span>
                    </button>
                </div>
            </div>

            <!-- Nivel de Estrés (1-5) -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                        Nivel de Estrés
                    </label>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ form.nivel_estres ? nivelEstresLabels[form.nivel_estres - 1] : '—' }}
                    </span>
                </div>
                <div class="grid grid-cols-5 gap-1.5">
                    <button
                        v-for="val in [1, 2, 3, 4, 5]"
                        :key="'estres-' + val"
                        type="button"
                        @click="form.nivel_estres = val"
                        :class="[
                            'py-1.5 px-1 text-center rounded-lg border text-xs font-bold transition-all',
                            form.nivel_estres === val
                                ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
                                : 'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ val }}
                    </button>
                </div>
            </div>

            <!-- Dolor Muscular (DOMS 1-5) -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                        Dolor / Fatiga Muscular
                    </label>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ form.dolor_muscular ? dolorLabels[form.dolor_muscular - 1] : '—' }}
                    </span>
                </div>
                <div class="grid grid-cols-5 gap-1.5">
                    <button
                        v-for="val in [1, 2, 3, 4, 5]"
                        :key="'dolor-' + val"
                        type="button"
                        @click="form.dolor_muscular = val"
                        :class="[
                            'py-1.5 px-1 text-center rounded-lg border text-xs font-bold transition-all',
                            form.dolor_muscular === val
                                ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                                : 'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ val }}
                    </button>
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                    Notas o sensaciones del día
                </label>
                <input
                    v-model="form.notas"
                    type="text"
                    maxlength="255"
                    placeholder="Ej: Buenas energías, piernas cansadas"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-xs"
                />
            </div>

            <button
                type="submit"
                :disabled="guardando"
                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition-all disabled:opacity-50"
            >
                {{ guardando ? 'Guardando...' : 'Guardar Bienestar' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    fecha: { type: String, required: true },
});

const toast = useToast();
const guardando = ref(false);
const cargando = ref(false);

const suenoEmojis = ['😫', '🥱', '😐', '😊', '⚡'];
const nivelEstresLabels = ['Muy bajo', 'Bajo', 'Moderado', 'Alto', 'Muy alto'];
const dolorLabels = ['Sin dolor', 'Leve', 'Moderado', 'Intenso', 'Muy intenso'];

const form = ref({
    horas_sueno: '',
    calidad_sueno: null,
    nivel_estres: null,
    dolor_muscular: null,
    notas: '',
});

const registrado = computed(() => {
    return (
        form.value.horas_sueno !== '' ||
        form.value.calidad_sueno !== null ||
        form.value.nivel_estres !== null ||
        form.value.dolor_muscular !== null
    );
});

const cargarBienestar = async () => {
    cargando.value = true;
    try {
        const { data } = await axios.get('/api/bienestar', {
            params: { fecha: props.fecha },
        });
        form.value.horas_sueno = data.horas_sueno ?? '';
        form.value.calidad_sueno = data.calidad_sueno ?? null;
        form.value.nivel_estres = data.nivel_estres ?? null;
        form.value.dolor_muscular = data.dolor_muscular ?? null;
        form.value.notas = data.notas ?? '';
    } catch (e) {
        console.error('Error al cargar bienestar diario:', e);
    } finally {
        cargando.value = false;
    }
};

const guardarBienestar = async () => {
    guardando.value = true;
    try {
        await axios.post('/api/bienestar', {
            fecha: props.fecha,
            horas_sueno: form.value.horas_sueno === '' ? null : Number(form.value.horas_sueno),
            calidad_sueno: form.value.calidad_sueno,
            nivel_estres: form.value.nivel_estres,
            dolor_muscular: form.value.dolor_muscular,
            notas: form.value.notas || null,
        });
        toast.add('Bienestar diario guardado correctamente', 'success');
    } catch (e) {
        console.error('Error al guardar bienestar diario:', e);
        toast.add('Error al guardar bienestar', 'error');
    } finally {
        guardando.value = false;
    }
};

watch(
    () => props.fecha,
    () => {
        cargarBienestar();
    }
);

onMounted(() => {
    cargarBienestar();
});
</script>
