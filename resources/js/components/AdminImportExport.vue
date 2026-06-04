<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <ToastNotification ref="toastRef" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">📂 Importar / Exportar</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Carga masiva de datos mediante archivos CSV</p>
        </div>
        <a href="/dashboard" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Volver
        </a>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Exportar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">📤</span> Exportar Datos
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">Descarga los datos actuales en formato CSV.</p>

          <div class="space-y-3">
            <button @click="exportarUsuarios" class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">👥</span>
                </div>
                <div class="text-left">
                  <p class="font-semibold text-gray-900 dark:text-white">Usuarios</p>
                  <p class="text-sm text-gray-500">{{ totalUsuarios }} registros</p>
                </div>
              </div>
              <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            </button>

            <button @click="exportarEjercicios" class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition-colors">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                  <span class="text-2xl">🏋️</span>
                </div>
                <div class="text-left">
                  <p class="font-semibold text-gray-900 dark:text-white">Ejercicios</p>
                  <p class="text-sm text-gray-500">{{ totalEjercicios }} registros</p>
                </div>
              </div>
              <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            </button>
          </div>
        </div>

        <!-- Importar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">📥</span> Importar Datos
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">Sube archivos CSV para importar datos masivamente.</p>

          <div class="space-y-4">
            <!-- Importar Usuarios -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
              <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                <span>👥</span> Importar Usuarios
              </h4>
              <div class="flex items-center gap-4">
                <label class="flex-1 cursor-pointer">
                  <input @change="handleUserFile" type="file" accept=".csv,.txt" class="hidden" />
                  <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors">
                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                    <p class="text-sm text-gray-500">Seleccionar archivo CSV</p>
                    <p class="text-xs text-gray-400 mt-1">Nombre, Email, Rol, etc.</p>
                  </div>
                </label>
                <button @click="importarUsuarios" :disabled="!userFile || importingUsers" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                  {{ importingUsers ? 'Importando...' : 'Importar' }}
                </button>
              </div>
              <p v-if="userFile" class="mt-2 text-sm text-indigo-600 dark:text-indigo-400">📄 {{ userFile.name }}</p>
            </div>

            <!-- Importar Ejercicios -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
              <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                <span>🏋️</span> Importar Ejercicios
              </h4>
              <div class="flex items-center gap-4">
                <label class="flex-1 cursor-pointer">
                  <input @change="handleEjercicioFile" type="file" accept=".csv,.txt" class="hidden" />
                  <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-green-500 dark:hover:border-green-400 transition-colors">
                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                    <p class="text-sm text-gray-500">Seleccionar archivo CSV</p>
                    <p class="text-xs text-gray-400 mt-1">Nombre, Grupo Muscular, Equipamiento</p>
                  </div>
                </label>
                <button @click="importarEjercicios" :disabled="!ejercicioFile || importingEjercicios" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                  {{ importingEjercicios ? 'Importando...' : 'Importar' }}
                </button>
              </div>
              <p v-if="ejercicioFile" class="mt-2 text-sm text-green-600 dark:text-green-400">📄 {{ ejercicioFile.name }}</p>
            </div>
          </div>

          <!-- Resultados de Importación -->
          <div v-if="importResult" class="mt-6 p-4 rounded-xl" :class="importResult.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200' : 'bg-red-50 dark:bg-red-900/20 border border-red-200'">
            <h4 class="font-semibold mb-2" :class="importResult.success ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
              {{ importResult.success ? '✅ Importación Exitosa' : '❌ Error en Importación' }}
            </h4>
            <p class="text-sm" :class="importResult.success ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
              Creados: {{ importResult.created }} | Procesados: {{ importResult.processed }}
            </p>
            <div v-if="importResult.errors?.length" class="mt-2">
              <p class="text-sm font-medium text-red-600 dark:text-red-400">Errores:</p>
              <ul class="text-xs text-red-500 dark:text-red-400 list-disc list-inside">
                <li v-for="(err, i) in importResult.errors.slice(0, 5)" :key="i">{{ err }}</li>
                <li v-if="importResult.errors.length > 5">... y {{ importResult.errors.length - 5 }} más</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Template Download -->
      <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📋 Plantillas de Ejemplo</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
            <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Plantilla Usuarios (CSV)</h4>
            <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto">Nombre,Nick,Email,Rol,Teléfono
Juan Pérez,juan.perez,juan@email.com,comun,123456789
María García,maria.garcia,maria@email.com,alumno,987654321
Carlos Trainer,carlos.trainer,carlos@email.com,trainer,555555555</pre>
          </div>
          <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
            <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Plantilla Ejercicios (CSV)</h4>
            <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-x-auto">Nombre,Grupo Muscular,Equipamiento,Visible,Descripción
Press de Banca,Pecho,Barra, Sí,Ejercicio compound para pecho
Sentadilla,Cuádriceps,Sin peso, Sí,Poids corporal para piernas</pre>
          </div>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div v-if="toast.show" :class="['fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50', toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ToastNotification from './ToastNotification.vue';

const toastRef = ref(null);
const userFile = ref(null);
const ejercicioFile = ref(null);
const importingUsers = ref(false);
const importingEjercicios = ref(false);
const importResult = ref(null);
const totalUsuarios = ref(0);
const totalEjercicios = ref(0);

const toast = ref({ show: false, message: '', type: 'success' });

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => { toast.value.show = false; }, 4000);
};

const handleUserFile = (e) => { userFile.value = e.target.files[0]; importResult.value = null; };
const handleEjercicioFile = (e) => { ejercicioFile.value = e.target.files[0]; importResult.value = null; };

const exportarUsuarios = () => { window.location.href = '/api/admin/export/users'; showToast('Descargando usuarios...'); };
const exportarEjercicios = () => { window.location.href = '/api/admin/export/ejercicios'; showToast('Descargando ejercicios...'); };

const importarUsuarios = async () => {
  if (!userFile.value) return;
  importingUsers.value = true;
  const formData = new FormData();
  formData.append('archivo', userFile.value);
  try {
    const response = await axios.post('/api/admin/import/users', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    importResult.value = response.data;
    showToast(`Importados ${response.data.created} usuarios`, 'success');
  } catch (error) {
    console.error(error);
    showToast('Error al importar usuarios', 'error');
  } finally { importingUsers.value = false; }
};

const importarEjercicios = async () => {
  if (!ejercicioFile.value) return;
  importingEjercicios.value = true;
  const formData = new FormData();
  formData.append('archivo', ejercicioFile.value);
  try {
    const response = await axios.post('/api/admin/import/ejercicios', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    importResult.value = response.data;
    showToast(`Importados ${response.data.created} ejercicios`, 'success');
  } catch (error) {
    console.error(error);
    showToast('Error al importar ejercicios', 'error');
  } finally { importingEjercicios.value = false; }
};

onMounted(async () => {
  try {
    const users = await axios.get('/api/admin/users');
    const ejercicios = await axios.get('/api/ejercicios');
    totalUsuarios.value = users.data.length || 0;
    totalEjercicios.value = Array.isArray(ejercicios.data) ? ejercicios.data.length : 0;
  } catch (e) { console.error(e); }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
pre { font-family: monospace; }
</style>