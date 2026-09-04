<template>
    <div class="space-y-4">
        <!-- Toolbar: filtro por ángulo + botón subir -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    v-for="opt in TIPO_OPTIONS"
                    :key="opt.value"
                    @click="filtroTipo = opt.value"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all',
                        filtroTipo === opt.value
                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                    ]"
                >
                    <span class="mr-1">{{ opt.emoji }}</span>{{ opt.label }}
                </button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ fotos.length }} foto{{ fotos.length === 1 ? '' : 's' }}
                </span>
                <button
                    @click="mostrarUpload = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-md text-sm transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Subir foto
                </button>
            </div>
        </div>

        <!-- Estado vacío -->
        <div v-if="!loading && fotos.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-5xl mb-3">📸</p>
            <h3 class="text-base font-bold text-gray-800 dark:text-white mb-1">Tu galería está vacía</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Subí tu primera foto de progreso. Lo ideal es hacerlo siempre
                en el mismo lugar, con la misma luz, a la misma hora.
            </p>
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="text-center text-sm text-gray-500 dark:text-gray-400 py-8">
            Cargando...
        </div>

        <!-- Grid cronológico -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div
                v-for="foto in fotosAgrupados.flatMap(g => g.fotos)"
                :key="foto.id"
                class="relative aspect-[3/4] rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 cursor-pointer group hover:ring-2 hover:ring-indigo-500 transition-all"
                @click="abrirLightbox(foto)"
            >
                <img
                    v-if="foto.url"
                    :src="foto.url"
                    :alt="`Foto de progreso del ${foto.fecha}`"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                    Archivo no disponible
                </div>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <p class="text-[10px] text-white font-mono">
                        {{ formatFechaCorta(foto.fecha) }}
                    </p>
                </div>
                <div class="absolute top-1.5 left-1.5 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded">
                    {{ TIPO_OPTIONS.find(o => o.value === foto.tipo)?.emoji }}
                </div>
                <button
                    v-if="foto.url"
                    @click.stop="confirmarBorrar(foto)"
                    class="absolute top-1.5 right-1.5 p-1 bg-black/60 hover:bg-red-600 text-white rounded opacity-0 group-hover:opacity-100 transition-all"
                    title="Eliminar"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal: subir foto -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="mostrarUpload"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    @click.self="mostrarUpload = false"
                >
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <form
                        @submit.prevent="subir"
                        class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">📸 Subir foto de progreso</h3>
                            <button
                                type="button"
                                @click="mostrarUpload = false"
                                class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- File input con preview -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Foto</label>
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                @change="onFileChange"
                                class="block w-full text-sm text-gray-900 dark:text-gray-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/40 dark:file:text-indigo-300"
                                required
                            />
                            <div v-if="previewUrl" class="mt-2 aspect-[3/4] max-h-64 mx-auto rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                                <img :src="previewUrl" class="w-full h-full object-cover" alt="preview" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Ángulo</label>
                                <select v-model="form.tipo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" required>
                                    <option value="front">Frente</option>
                                    <option value="side">Perfil</option>
                                    <option value="back">Espalda</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fecha</label>
                                <input v-model="form.fecha" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Peso (opcional, kg)</label>
                            <input v-model.number="form.peso" type="number" step="0.1" min="0" max="500" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" placeholder="Ej: 70.5" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Notas (opcional)</label>
                            <textarea v-model="form.notas" maxlength="500" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm" placeholder="Cómo venís sintiéndote, éclairage, etc."></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button
                                type="submit"
                                :disabled="!form.foto || subiendo"
                                class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md text-sm disabled:opacity-50"
                            >
                                {{ subiendo ? 'Subiendo...' : 'Subir' }}
                            </button>
                            <button
                                type="button"
                                @click="mostrarUpload = false"
                                class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>
        </Teleport>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="lightboxFoto"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    @click.self="lightboxFoto = null"
                >
                    <button
                        @click="lightboxFoto = null"
                        class="absolute top-4 right-4 p-2 text-white hover:bg-white/10 rounded-full"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button
                        v-if="lightboxIndex > 0"
                        @click="lightboxIndex--"
                        class="absolute left-4 p-3 text-white hover:bg-white/10 rounded-full"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        v-if="lightboxIndex < fotos.length - 1"
                        @click="lightboxIndex++"
                        class="absolute right-4 p-3 text-white hover:bg-white/10 rounded-full"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <div class="max-w-3xl max-h-[90vh] flex flex-col items-center gap-3">
                        <img
                            v-if="lightboxFoto.url"
                            :src="lightboxFoto.url"
                            :alt="`Foto del ${lightboxFoto.fecha}`"
                            class="max-h-[80vh] max-w-full rounded-xl shadow-2xl"
                        />
                        <div class="text-center text-white">
                            <p class="font-bold">{{ formatFechaLarga(lightboxFoto.fecha) }}</p>
                            <p v-if="lightboxFoto.peso" class="text-sm text-white/70">Peso: {{ lightboxFoto.peso }}kg</p>
                            <p v-if="lightboxFoto.notas" class="text-sm text-white/80 mt-1 max-w-md">{{ lightboxFoto.notas }}</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
const { formatDateLong, formatDateMedium } = useFormatters();
    import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from '../../composables/useToast';
import { useFormatters } from '@/composables/useFormatters';

const toast = useToast();

const TIPO_OPTIONS = [
    { value: 'all', label: 'Todos', emoji: '🖼️' },
    { value: 'front', label: 'Frente', emoji: '🪞' },
    { value: 'side', label: 'Perfil', emoji: '↔️' },
    { value: 'back', label: 'Espalda', emoji: '🔄' },
];

const fotos = ref([]);
const loading = ref(true);
const filtroTipo = ref('all');

const mostrarUpload = ref(false);
const subiendo = ref(false);
const fileInputRef = ref(null);
const previewUrl = ref(null);
const form = ref({
    foto: null,
    fecha: new Date().toISOString().split('T')[0],
    tipo: 'front',
    peso: null,
    notas: '',
});

const lightboxFoto = ref(null);
const lightboxIndex = ref(0);

const cargar = async () => {
    loading.value = true;
    try {
        const params = filtroTipo.value !== 'all' ? { tipo: filtroTipo.value } : {};
        const { data } = await axios.get('/api/progreso/fotos', { params });
        fotos.value = Array.isArray(data) ? data : [];
    } catch (e) {
        toast.apiError(e, 'No se pudieron cargar las fotos.');
    } finally {
        loading.value = false;
    }
};

const fotosAgrupados = computed(() => {
    const grupos = {};
    fotos.value.forEach((f) => {
        const key = f.fecha;
        if (!grupos[key]) grupos[key] = { fecha: key, fotos: [] };
        grupos[key].fotos.push(f);
    });
    return Object.values(grupos).sort((a, b) => b.fecha.localeCompare(a.fecha));
});

watch(filtroTipo, cargar);

const onFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    form.value.foto = file;
    // Liberar URL anterior si existía
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(file);
};

const subir = async () => {
    if (!form.value.foto) return;
    subiendo.value = true;
    try {
        const data = new FormData();
        data.append('foto', form.value.foto);
        data.append('fecha', form.value.fecha);
        data.append('tipo', form.value.tipo);
        if (form.value.peso != null) data.append('peso', form.value.peso);
        if (form.value.notas) data.append('notas', form.value.notas);

        await axios.post('/api/progreso/fotos', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        toast.success('Foto subida ✓');
        mostrarUpload.value = false;
        // Reset form
        form.value = { foto: null, fecha: new Date().toISOString().split('T')[0], tipo: 'front', peso: null, notas: '' };
        previewUrl.value = null;
        if (fileInputRef.value) fileInputRef.value.value = '';
        await cargar();
    } catch (e) {
        toast.apiError(e, 'No se pudo subir la foto.');
    } finally {
        subiendo.value = false;
    }
};

const confirmarBorrar = async (foto) => {
    const ok = await toast.confirm(
        '¿Eliminar esta foto? Esta acción no se puede deshacer.',
        { title: 'Eliminar foto', confirmLabel: 'Sí, eliminar', type: 'error' }
    );
    if (!ok) return;
    try {
        await axios.delete(`/api/progreso/fotos/${foto.id}`);
        toast.success('Foto eliminada');
        await cargar();
    } catch (e) {
        toast.apiError(e, 'No se pudo eliminar la foto.');
    }
};

const abrirLightbox = (foto) => {
    if (!foto.url) return;
    const idx = fotos.value.findIndex(f => f.id === foto.id);
    lightboxIndex.value = idx >= 0 ? idx : 0;
    lightboxFoto.value = fotos.value[lightboxIndex.value] || foto;
};

watch(lightboxIndex, (i) => {
    if (fotos.value[i]) lightboxFoto.value = fotos.value[i];
});

const formatFechaCorta = (s) => {
    if (!s) return '';
    const d = new Date(s + 'T00:00:00');
    return dformatDateMedium(17327);
};

const formatFechaLarga = (s) => {
    if (!s) return '';
    const d = new Date(s + 'T00:00:00');
    return dformatDateLong(17466);
};

onMounted(cargar);
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
