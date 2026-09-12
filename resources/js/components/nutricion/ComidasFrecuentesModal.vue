<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/80"
                    >
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <span>⚡</span> Comidas Frecuentes
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Guarda tus comidas habituales y súmalas a tu día con un solo clic.
                            </p>
                        </div>
                        <button
                            @click="$emit('close')"
                            type="button"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
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

                    <!-- Content -->
                    <div class="p-5 overflow-y-auto space-y-5 flex-1">
                        <!-- Botón para alternar formulario de nueva comida -->
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400"
                                >Tus Comidas Guardadas</span
                            >
                            <button
                                @click="mostrarFormNuevo = !mostrarFormNuevo"
                                type="button"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors flex items-center gap-1"
                            >
                                <span>{{ mostrarFormNuevo ? 'Cancelar' : '+ Nueva comida' }}</span>
                            </button>
                        </div>

                        <!-- Formulario para agregar nueva comida frecuente -->
                        <div
                            v-if="mostrarFormNuevo"
                            class="bg-gray-50 dark:bg-gray-750 p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/40 space-y-3"
                        >
                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                Crear Comida Frecuente
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                                        >Nombre</label
                                    >
                                    <input
                                        v-model="nuevaComida.nombre"
                                        type="text"
                                        placeholder="Ej: Avena con proteína"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                                        >Porción (opcional)</label
                                    >
                                    <input
                                        v-model="nuevaComida.porcion"
                                        type="text"
                                        placeholder="Ej: 1 tazón (80g)"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                                        >Calorías (kcal)</label
                                    >
                                    <input
                                        v-model.number="nuevaComida.calorias"
                                        type="number"
                                        min="0"
                                        placeholder="350"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-red-500 mb-1"
                                        >Proteínas (g)</label
                                    >
                                    <input
                                        v-model.number="nuevaComida.proteinas"
                                        type="number"
                                        min="0"
                                        placeholder="30"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-amber-500 mb-1"
                                        >Carbohidratos (g)</label
                                    >
                                    <input
                                        v-model.number="nuevaComida.carbohidratos"
                                        type="number"
                                        min="0"
                                        placeholder="45"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-[11px] font-semibold text-emerald-500 mb-1"
                                        >Grasas (g)</label
                                    >
                                    <input
                                        v-model.number="nuevaComida.grasas"
                                        type="number"
                                        min="0"
                                        placeholder="8"
                                        class="w-full px-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    />
                                </div>
                            </div>

                            <button
                                @click="guardarNuevaComida"
                                :disabled="guardando || !nuevaComida.nombre"
                                type="button"
                                class="w-full py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                {{ guardando ? 'Guardando...' : 'Guardar Comida' }}
                            </button>
                        </div>

                        <!-- Lista de comidas -->
                        <div v-if="cargando" class="text-center py-6 text-xs text-gray-400">
                            Cargando comidas frecuentes...
                        </div>

                        <div
                            v-else-if="comidas.length === 0"
                            class="text-center py-8 text-gray-400"
                        >
                            <p class="text-2xl mb-2">🥗</p>
                            <p class="text-xs">No tienes comidas frecuentes guardadas todavía.</p>
                            <p class="text-[11px] text-gray-500 mt-1">
                                Crea una arriba para sumar rápido tus macros habituales.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="c in comidas"
                                :key="c.id"
                                class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <h5
                                        class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                    >
                                        {{ c.nombre }}
                                    </h5>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                        <span v-if="c.porcion" class="font-medium mr-1.5"
                                            >{{ c.porcion }} ·</span
                                        >
                                        <span
                                            class="font-mono font-bold text-indigo-600 dark:text-indigo-400"
                                            >{{ c.calorias }} kcal</span
                                        >
                                        <span class="mx-1">|</span>
                                        <span class="text-red-500 font-semibold"
                                            >{{ c.proteinas }}g P</span
                                        >
                                        ·
                                        <span class="text-amber-500 font-semibold"
                                            >{{ c.carbohidratos }}g C</span
                                        >
                                        ·
                                        <span class="text-emerald-500 font-semibold"
                                            >{{ c.grasas }}g G</span
                                        >
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button
                                        @click="seleccionarComida(c)"
                                        type="button"
                                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1"
                                        title="Sumar valores a los macros del día de hoy"
                                    >
                                        <span>+ Sumar</span>
                                    </button>
                                    <button
                                        @click="eliminarComida(c.id)"
                                        type="button"
                                        class="p-1.5 text-gray-400 hover:text-rose-500 rounded-lg transition-colors"
                                        title="Eliminar de comidas frecuentes"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex justify-end"
                    >
                        <button
                            @click="$emit('close')"
                            type="button"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'sumarComida']);

const toast = useToast();
const comidas = ref([]);
const cargando = ref(false);
const guardando = ref(false);
const mostrarFormNuevo = ref(false);

const nuevaComida = ref({
    nombre: '',
    porcion: '',
    calorias: '',
    proteinas: '',
    carbohidratos: '',
    grasas: '',
});

const cargarComidas = async () => {
    cargando.value = true;
    try {
        const { data } = await axios.get('/api/comidas-frecuentes');
        comidas.value = data || [];
    } catch (e) {
        console.error('Error al cargar comidas frecuentes:', e);
    } finally {
        cargando.value = false;
    }
};

const guardarNuevaComida = async () => {
    if (!nuevaComida.value.nombre) return;
    guardando.value = true;
    try {
        const payload = {
            nombre: nuevaComida.value.nombre,
            porcion: nuevaComida.value.porcion || null,
            calorias: Number(nuevaComida.value.calorias) || 0,
            proteinas: Number(nuevaComida.value.proteinas) || 0,
            carbohidratos: Number(nuevaComida.value.carbohidratos) || 0,
            grasas: Number(nuevaComida.value.grasas) || 0,
        };
        const { data } = await axios.post('/api/comidas-frecuentes', payload);
        comidas.value.unshift(data.comida);
        toast.add('Comida frecuente agregada', 'success');
        nuevaComida.value = {
            nombre: '',
            porcion: '',
            calorias: '',
            proteinas: '',
            carbohidratos: '',
            grasas: '',
        };
        mostrarFormNuevo.value = false;
    } catch (e) {
        console.error('Error al guardar comida frecuente:', e);
        toast.add('Error al guardar comida frecuente', 'error');
    } finally {
        guardando.value = false;
    }
};

const eliminarComida = async (id) => {
    try {
        await axios.delete(`/api/comidas-frecuentes/${id}`);
        comidas.value = comidas.value.filter((c) => c.id !== id);
        toast.add('Comida eliminada de frecuentes', 'info');
    } catch (e) {
        console.error('Error al eliminar comida:', e);
        toast.add('Error al eliminar comida', 'error');
    }
};

const seleccionarComida = (comida) => {
    emit('sumarComida', comida);
};

watch(
    () => props.isOpen,
    (abierto) => {
        if (abierto) {
            cargarComidas();
        }
    }
);

onMounted(() => {
    if (props.isOpen) {
        cargarComidas();
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
