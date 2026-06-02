<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Usuarios</h2>
      </div>

      <div v-if="cargando" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Nick</th>
                <th class="px-4 py-4 text-left font-bold text-gray-700 dark:text-gray-300">Nombre</th>
                <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Email</th>
                <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Rol</th>
                <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Trainer</th>
                <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Estado</th>
                <th class="px-4 py-4 text-center font-bold text-gray-700 dark:text-gray-300">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="usuario in usuarios" :key="usuario.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div :class="getAvatarColor(usuario.role)" class="w-10 h-10 rounded-full flex items-center justify-center">
                      <span class="text-white font-bold text-sm">{{ usuario.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">@{{ usuario.nick }}</span>
                  </div>
                </td>
                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ usuario.name }}</td>
                <td class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">{{ usuario.email }}</td>
                <td class="px-4 py-4 text-center">
                  <span :class="getRoleClass(usuario.role)" class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ formatRole(usuario.role) }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span v-if="usuario.trainer" class="text-gray-600 dark:text-gray-400">
                    {{ usuario.trainer.name }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span
                    :class="usuario.suspended ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'"
                    class="px-3 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ usuario.suspended ? 'Suspendido' : 'Activo' }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="abrirModalEditar(usuario)"
                      class="p-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors"
                      title="Editar usuario"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      v-if="usuario.id !== usuarioActualId"
                      @click="toggleSuspender(usuario)"
                      :class="usuario.suspended ? 'bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-600 dark:text-green-400' : 'bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-600 dark:text-orange-400'"
                      class="p-2 rounded-lg transition-colors"
                      :title="usuario.suspended ? 'Activar usuario' : 'Suspender usuario'"
                    >
                      <svg v-if="!usuario.suspended" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="usuarios.length === 0" class="p-12 text-center">
          <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400">No hay usuarios registrados</p>
        </div>
      </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="cerrarModal">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="cerrarModal"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
              Editar Usuario
            </h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nick</label>
                <input
                  v-model="formularioEdit.nick"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                  disabled
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre</label>
                <input
                  v-model="formularioEdit.name"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                <input
                  v-model="formularioEdit.email"
                  type="email"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rol</label>
                <select
                  v-model="formularioEdit.role"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="comun">Común</option>
                  <option value="alumno">Alumno</option>
                  <option value="trainer">Trainer</option>
                  <option value="administrador">Administrador</option>
                </select>
              </div>

              <div v-if="formularioEdit.role === 'alumno'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trainer</label>
                <select
                  v-model="formularioEdit.trainer_id"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">Sin trainer</option>
                  <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                    {{ trainer.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nueva Contraseña (opcional)</label>
                <input
                  v-model="formularioEdit.password"
                  type="password"
                  placeholder="Dejar vacío para no cambiar"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:flex sm:flex-row-reverse">
            <button
              @click="guardarEdicion"
              :disabled="guardando"
              class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
            <button
              @click="cerrarModal"
              class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const usuarios = ref([]);
const trainers = ref([]);
const cargando = ref(true);
const mostrarModal = ref(false);
const guardando = ref(false);
const usuarioActualId = ref(null);

const formularioEdit = ref({
  id: null,
  nick: '',
  name: '',
  email: '',
  role: '',
  trainer_id: '',
  password: '',
});

const obtenerUsuarios = async () => {
  try {
    const response = await axios.get('/api/admin/users');
    usuarios.value = response.data.users || response.data;
    trainers.value = response.data.trainers || [];
    usuarioActualId.value = response.data.current_user_id;
  } catch (error) {
    console.error('Error:', error);
  } finally {
    cargando.value = false;
  }
};

const getAvatarColor = (role) => {
  const colors = {
    'administrador': 'bg-red-500',
    'trainer': 'bg-blue-500',
    'alumno': 'bg-green-500',
    'comun': 'bg-gray-500',
  };
  return colors[role] || 'bg-gray-500';
};

const getRoleClass = (role) => {
  const classes = {
    'administrador': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    'trainer': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'alumno': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'comun': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
  };
  return classes[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
};

const formatRole = (role) => {
  const roles = {
    'administrador': 'Admin',
    'trainer': 'Trainer',
    'alumno': 'Alumno',
    'comun': 'Común',
  };
  return roles[role] || role;
};

const abrirModalEditar = (usuario) => {
  formularioEdit.value = {
    id: usuario.id,
    nick: usuario.nick,
    name: usuario.name,
    email: usuario.email,
    role: usuario.role,
    trainer_id: usuario.trainer_id || '',
    password: '',
  };
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
  formularioEdit.value = {
    id: null,
    nick: '',
    name: '',
    email: '',
    role: '',
    trainer_id: '',
    password: '',
  };
};

const guardarEdicion = async () => {
  guardando.value = true;
  try {
    const data = {
      name: formularioEdit.value.name,
      email: formularioEdit.value.email,
      role: formularioEdit.value.role,
      trainer_id: formularioEdit.value.role === 'alumno' ? formularioEdit.value.trainer_id : null,
    };
    
    if (formularioEdit.value.password) {
      data.password = formularioEdit.value.password;
    }

    await axios.put(`/api/admin/users/${formularioEdit.value.id}`, data);
    
    await obtenerUsuarios();
    cerrarModal();
    alert('Usuario actualizado correctamente');
  } catch (error) {
    console.error('Error:', error);
    alert(error.response?.data?.error || 'No se pudo actualizar el usuario');
  } finally {
    guardando.value = false;
  }
};

const toggleSuspender = async (usuario) => {
  if (!confirm(usuario.suspended 
    ? `¿Activar al usuario ${usuario.name}?` 
    : `¿Suspender al usuario ${usuario.name}?`)) {
    return;
  }

  try {
    await axios.patch(`/api/admin/users/${usuario.id}/toggle-suspend`);
    await obtenerUsuarios();
    alert(usuario.suspended ? 'Usuario activado' : 'Usuario suspendido');
  } catch (error) {
    console.error('Error:', error);
    alert(error.response?.data?.error || 'No se pudo cambiar el estado del usuario');
  }
};

onMounted(() => {
  obtenerUsuarios();
});
</script>