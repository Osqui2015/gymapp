<template>
    <div class="space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <span>⚙️</span> Configurar TDEE
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Tus datos se usan para calcular tu metabolismo basal (Mifflin-St Jeor) y sugerirte calorías y macros diarios.
                </p>
            </div>
        </div>

        <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            Cargando datos...
        </div>

        <form v-else @submit.prevent="guardar" class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Sexo biológico</label>
                <select
                    v-model="form.sexo"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                    required
                >
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Edad</label>
                <input
                    v-model.number="form.edad"
                    type="number"
                    min="10"
                    max="120"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                    placeholder="Ej: 30"
                    required
                />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                    Nivel de actividad
                    <span class="text-[10px] text-gray-400">(incluye el gym)</span>
                </label>
                <select
                    v-model="form.nivel_actividad"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                    required
                >
                    <option value="sedentario">Sedentario (oficina, sin deporte)</option>
                    <option value="ligero">Ligero (1-3 días/semana)</option>
                    <option value="moderado">Moderado (3-5 días/semana)</option>
                    <option value="activo">Activo (6-7 días/semana)</option>
                    <option value="muy_activo">Muy activo (atleta, 2x día)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Objetivo</label>
                <select
                    v-model="form.objetivo_nutricional"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                    required
                >
                    <option value="perder_grasa">Perder grasa (-20% cal)</option>
                    <option value="mantener">Mantener peso</option>
                    <option value="ganar_masa">Ganar masa muscular (+15% cal)</option>
                </select>
            </div>

            <div v-if="inputs.peso" class="sm:col-span-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/40 rounded-lg p-3">
                Usando peso y altura de tu último registro de Progreso del
                <strong>{{ inputs.ultimo_progreso_fecha || '—' }}</strong>
                ({{ inputs.peso }}kg, {{ inputs.altura }}cm). Actualizalos desde
                <a href="/progreso" class="text-indigo-600 dark:text-indigo-400 underline">Progreso</a>
                para que el cálculo use los más recientes.
            </div>

            <div class="sm:col-span-2 flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="guardando"
                    class="flex-1 py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md text-sm disabled:opacity-50"
                >
                    {{ guardando ? 'Guardando...' : 'Guardar y recalcular' }}
                </button>
                <button
                    v-if="cancelable"
                    type="button"
                    @click="$emit('cancel')"
                    class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';

const props = defineProps({
    cancelable: { type: Boolean, default: false },
});
const emit = defineEmits(['saved', 'cancel']);

const toast = useToast();
const loading = ref(true);
const guardando = ref(false);
const inputs = ref({});
const form = ref({
    sexo: 'masculino',
    edad: null,
    nivel_actividad: 'moderado',
    objetivo_nutricional: 'mantener',
});

const cargar = async () => {
    try {
        const { data } = await axios.get('/api/nutricion/tdee');
        inputs.value = data.inputs || {};
        // Pre-rellenar con lo que el user ya tenga seteado
        if (data.inputs?.sexo) form.value.sexo = data.inputs.sexo;
        if (data.inputs?.edad) form.value.edad = data.inputs.edad;
        if (data.inputs?.nivel_actividad) form.value.nivel_actividad = data.inputs.nivel_actividad;
        if (data.inputs?.objetivo_nutricional) form.value.objetivo_nutricional = data.inputs.objetivo_nutricional;
    } catch (e) {
        toast.apiError(e, 'No se pudo cargar la configuración.');
    } finally {
        loading.value = false;
    }
};

const guardar = async () => {
    guardando.value = true;
    try {
        const { data } = await axios.patch('/api/nutricion/config', form.value);
        toast.success('Configuración guardada. TDEE recalculado ✓');
        emit('saved', data.tdee);
    } catch (e) {
        toast.apiError(e, 'No se pudo guardar la configuración.');
    } finally {
        guardando.value = false;
    }
};

onMounted(cargar);
</script>
