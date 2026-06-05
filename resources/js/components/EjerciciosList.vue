<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Ejercicios</h2>
        <button
          v-if="userRole === 'trainer' || userRole === 'administrador'"
          @click="mostrarModal = true"
          class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg"
        >
          + Agregar Ejercicio
        </button>
      </div>

      <div class="mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre o equipamiento..."
            class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @keyup.enter="buscar"
          />
          <select
            v-model="grupoMuscularFiltro"
            @change="buscar"
            class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-w-[200px]"
          >
            <option value="">Todos los grupos musculares</option>
            <option v-for="grupo in gruposMusculares" :key="grupo" :value="grupo">{{ grupo }}</option>
          </select>
          <select
            v-model="equipamientoFiltro"
            @change="buscar"
            class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-w-[200px]"
          >
            <option value="">Todos los equipamientos</option>
            <option v-for="eq in equipamientos" :key="eq" :value="eq">{{ eq }}</option>
          </select>
          <button
            @click="buscar"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg"
          >
            Buscar
          </button>
          <button
            v-if="busqueda || grupoMuscularFiltro || equipamientoFiltro"
            @click="limpiarBusqueda"
            class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-3 rounded-lg text-sm font-medium transition-all"
          >
            Limpiar
          </button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Equipamiento</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Grupo Muscular</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Descripción</th>
                <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="ejercicio in ejercicios" :key="ejercicio.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-4 font-medium text-gray-900 dark:text-white">{{ ejercicio.nombre }}</td>
                <td class="px-4 py-4">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                    {{ ejercicio.equipamiento }}
                  </span>
                </td>
                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ ejercicio.grupo_muscular || '-' }}</td>
                <td class="px-4 py-4 text-gray-500 dark:text-gray-400 text-xs max-w-xs hidden md:table-cell">{{ ejercicio.descripcion?.substring(0, 80) || '-' }}{{ ejercicio.descripcion?.length > 80 ? '...' : '' }}</td>
                <td class="px-4 py-4 text-center">
                  <button
                    v-if="userRole === 'trainer' || userRole === 'administrador'"
                    @click="eliminar(ejercicio.id)"
                    class="text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 px-3 py-1 rounded text-sm font-medium transition-all"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="ejercicios.length === 0">
                <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                  <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  No hay ejercicios
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Mostrando {{ ejercicios.length }} ejercicios de {{ total }} total
      </div>

      <div v-if="totalPages > 1" class="mt-4 flex justify-center gap-2">
        <button
          @click="cambiarPagina(paginaActual - 1)"
          :disabled="paginaActual === 1"
          class="px-3 py-2 rounded-lg text-sm font-medium transition-all"
          :class="paginaActual === 1 ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'"
        >
          ← Anterior
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="cambiarPagina(page)"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
          :class="page === paginaActual ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'"
        >
          {{ page }}
        </button>
        <button
          @click="cambiarPagina(paginaActual + 1)"
          :disabled="paginaActual === totalPages"
          class="px-3 py-2 rounded-lg text-sm font-medium transition-all"
          :class="paginaActual === totalPages ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'"
        >
          Siguiente →
        </button>
      </div>

      <div v-if="mostrarModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg shadow-2xl border border-gray-200 dark:border-gray-700">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Agregar Ejercicio</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
              <input
                v-model="nuevo.nombre"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Equipamiento *</label>
              <input
                v-model="nuevo.equipamiento"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grupo Muscular</label>
              <input
                v-model="nuevo.grupo_muscular"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
              <textarea
                v-model="nuevo.descripcion"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="mostrarModal = false"
              class="px-5 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all"
            >
              Cancelar
            </button>
            <button
              @click="agregar"
              class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg"
            >
              Agregar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const ejercicios = ref([]);
const busqueda = ref('');
const grupoMuscularFiltro = ref('');
const gruposMusculares = ref([]);
const equipamientoFiltro = ref('');
const equipamientos = ref([]);
const mostrarModal = ref(false);
const paginaActual = ref(1);
const totalPages = ref(1);
const total = ref(0);
const userRole = ref('comun');
const nuevo = ref({
  nombre: '',
  equipamiento: '',
  grupo_muscular: '',
  descripcion: '',
});

const fetchUserInfo = async () => {
  try {
    const response = await axios.get('/api/user-info');
    userRole.value = response.data.role;
  } catch (error) {
    console.error('Error al obtener rol:', error);
  }
};

const fetchGruposMusculares = async () => {
  try {
    const response = await axios.get('/api/ejercicios/grupos-musculares');
    gruposMusculares.value = response.data;
  } catch (error) {
    console.error('Error al obtener grupos musculares:', error);
  }
};

const fetchEquipamientos = async () => {
  try {
    const response = await axios.get('/api/ejercicios/equipamientos');
    equipamientos.value = response.data;
  } catch (error) {
    console.error('Error al obtener equipamientos:', error);
  }
};

const fetchEjercicios = async (page = 1) => {
  try {
    const params = { page };
    if (busqueda.value) params.busqueda = busqueda.value;
    if (grupoMuscularFiltro.value) params.grupo_muscular = grupoMuscularFiltro.value;
    if (equipamientoFiltro.value) params.equipamiento = equipamientoFiltro.value;
    const response = await axios.get('/api/ejercicios', { params });
    ejercicios.value = response.data.data || response.data;
    paginaActual.value = response.data.current_page || 1;
    totalPages.value = response.data.last_page || 1;
    total.value = response.data.total || ejercicios.value.length;
  } catch (error) {
    console.error('Error:', error);
  }
};

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, paginaActual.value - Math.floor(maxVisible / 2));
  let end = Math.min(totalPages.value, start + maxVisible - 1);
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1);
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

const cambiarPagina = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    fetchEjercicios(page);
  }
};

const buscar = () => {
  fetchEjercicios();
};

const limpiarBusqueda = () => {
  busqueda.value = '';
  grupoMuscularFiltro.value = '';
  equipamientoFiltro.value = '';
  fetchEjercicios();
};

const agregar = async () => {
  if (!nuevo.value.nombre || !nuevo.value.equipamiento) {
    alert('Nombre y equipamiento son requeridos');
    return;
  }
  try {
    await axios.post('/api/ejercicios', nuevo.value);
    nuevo.value = { nombre: '', equipamiento: '', grupo_muscular: '', descripcion: '' };
    mostrarModal.value = false;
    fetchEjercicios();
  } catch (error) {
    console.error('Error:', error);
  }
};

const eliminar = async (id) => {
  if (confirm('¿Eliminar este ejercicio?')) {
    try {
      await axios.delete(`/api/ejercicios/${id}`);
      fetchEjercicios();
    } catch (error) {
      console.error('Error:', error);
    }
  }
};

onMounted(() => {
  fetchUserInfo();
  fetchGruposMusculares();
  fetchEquipamientos();
  fetchEjercicios();
});
</script>