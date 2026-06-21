<template>
  <div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
      <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-500">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Gestión de Usuarios
        </h2>
      </div>

      <!-- Crear nuevo usuario -->
      <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
          Crear Nuevo Usuario
        </h3>
        <form @submit.prevent="$emit('crear', nuevoUsuario)" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 animate-fadeIn">
          <CampoForm v-model="nuevoUsuario.nick" label="Nick *" type="text" required placeholder="usuario123" />
          <CampoForm v-model="nuevoUsuario.name" label="Nombre Completo *" type="text" required placeholder="Juan Pérez" />
          <CampoForm v-model="nuevoUsuario.email" label="Email *" type="email" required placeholder="correo@ejemplo.com" />
          <CampoForm v-model="nuevoUsuario.telefono" label="Teléfono" type="text" placeholder="11-1234-5678" />
          <CampoForm v-model="nuevoUsuario.password" label="Contraseña *" type="password" required placeholder="Mínimo 6 caracteres" minlength="6" />

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol *</label>
            <select v-model="nuevoUsuario.role" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">Seleccionar rol</option>
              <option value="comun">Común</option>
              <option value="alumno">Alumno</option>
              <option value="trainer">Trainer</option>
              <option value="administrador">Administrador</option>
            </select>
          </div>

          <div v-if="nuevoUsuario.role === 'alumno'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trainer Asignado</label>
            <select v-model="nuevoUsuario.trainer_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option :value="null">Sin trainer</option>
              <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">{{ trainer.name }}</option>
            </select>
          </div>

          <div class="md:col-span-2 lg:col-span-3 flex items-end pt-2">
            <button type="submit" :disabled="creando" class="ripple px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors flex items-center gap-2">
              <svg v-if="creando" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              {{ creando ? 'Creando...' : 'Crear Usuario' }}
            </button>
          </div>
        </form>
        <div v-if="errorCrear" class="mt-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300">{{ errorCrear }}</div>
        <div v-if="successCrear" class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300">{{ successCrear }}</div>
      </div>

      <!-- Listado -->
      <div class="p-6">
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
          <div class="relative flex-1">
            <input
              :value="searchFilter"
              @input="$emit('update:searchFilter', $event.target.value)"
              type="text"
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500"
              placeholder="Buscar por nombre, nick o email..."
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 hidden md:block">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="u in users" :key="u.id" :class="[u.suspended ? 'bg-red-50/40 dark:bg-red-950/10' : '']" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-sm">{{ (u.name || u.nick || '?').charAt(0).toUpperCase() }}</div>
                    <div>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ u.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">@{{ u.nick }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ u.email }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs font-bold rounded-full capitalize" :class="roleClass(u.role)">{{ u.role }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-center">
                  <span v-if="u.suspended" class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Suspendido</span>
                  <span v-else class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activo</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                  <div class="inline-flex gap-1">
                    <button @click="$emit('abrir-editar', u)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg transition-colors" :aria-label="`Editar usuario ${u.name}`" title="Editar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button v-if="u.id !== currentUserId" @click="$emit('toggle-suspend', u)" class="p-1.5 rounded-lg transition-colors" :class="u.suspended ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-950/30' : 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30'" :aria-label="(u.suspended ? 'Activar' : 'Suspender') + ' usuario ' + u.name" :title="u.suspended ? 'Activar' : 'Suspender'">
                      <svg v-if="u.suspended" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                    </button>
                    <button v-if="u.id !== currentUserId" @click="$emit('eliminar', u)" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors" :aria-label="`Eliminar usuario ${u.name}`" title="Eliminar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No se encontraron usuarios.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile: cards -->
        <div class="md:hidden rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 overflow-hidden">
          <div
            v-for="u in users"
            :key="u.id"
            class="p-4 space-y-3"
            :class="u.suspended ? 'bg-red-50/40 dark:bg-red-950/10' : ''"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold flex-shrink-0">
                {{ (u.name || u.nick || '?').charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ u.nick }}</p>
              </div>
              <span class="px-2 py-1 text-xs font-bold rounded-full capitalize flex-shrink-0" :class="roleClass(u.role)">{{ u.role }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ u.email }}</p>
            <div class="flex items-center justify-between">
              <span v-if="u.suspended" class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Suspendido</span>
              <span v-else class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activo</span>
              <div class="inline-flex gap-1">
                <button @click="$emit('abrir-editar', u)" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg transition-colors" :aria-label="`Editar usuario ${u.name}`" title="Editar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>
                <button v-if="u.id !== currentUserId" @click="$emit('toggle-suspend', u)" class="p-2 rounded-lg transition-colors" :class="u.suspended ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-950/30' : 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30'" :aria-label="(u.suspended ? 'Activar' : 'Suspender') + ' usuario ' + u.name" :title="u.suspended ? 'Activar' : 'Suspender'">
                  <svg v-if="u.suspended" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                </button>
                <button v-if="u.id !== currentUserId" @click="$emit('eliminar', u)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors" :aria-label="`Eliminar usuario ${u.name}`" title="Eliminar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>
          </div>
          <div v-if="users.length === 0">
            <EmptyState
              emoji="👤"
              :title="searchFilter ? 'Sin resultados' : 'No hay usuarios todavía'"
              :description="searchFilter ? 'No se encontraron usuarios con ese criterio de búsqueda.' : 'Cuando se registren usuarios van a aparecer acá para que puedas gestionarlos.'"
              variant="compact"
            />
          </div>
        </div>

        <Paginador
          v-if="totalPages > 1"
          :currentPage="currentPage"
          :totalPages="totalPages"
          @prev="currentPage--"
          @next="currentPage++"
        />
      </div>
    </div>

    <!-- Modal Editar Usuario -->
    <Teleport to="body">
      <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center px-4" @click.self="$emit('cerrar-modal')">
        <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-950/80 transition-opacity"></div>

        <div ref="modalRef" class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full relative z-10 border border-gray-200 dark:border-gray-700" role="dialog" aria-modal="true">
          <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-500 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold">Editar Usuario: @{{ formularioEdit.nick }}</h3>
            <button @click="$emit('cerrar-modal')" class="text-white/80 hover:text-white">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="$emit('guardar-edicion', formularioEdit)" class="p-6 space-y-4">
            <CampoForm v-model="formularioEdit.name" label="Nombre Completo *" type="text" required />
            <CampoForm v-model="formularioEdit.email" label="Email *" type="email" required />
            <CampoForm v-model="formularioEdit.telefono" label="Teléfono" type="text" placeholder="11-1234-5678" />
            <CampoForm v-model="formularioEdit.password" label="Contraseña (dejar en blanco para no cambiar)" type="password" placeholder="Mínimo 6 caracteres" minlength="6" />

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol *</label>
              <select v-model="formularioEdit.role" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <option value="comun">Común</option>
                <option value="alumno">Alumno</option>
                <option value="trainer">Trainer</option>
                <option value="administrador">Administrador</option>
              </select>
            </div>

            <div v-if="formularioEdit.role === 'alumno'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trainer Asignado</label>
              <select v-model="formularioEdit.trainer_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <option :value="null">Sin trainer</option>
                <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">{{ trainer.name }}</option>
              </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
              <button type="button" @click="$emit('cerrar-modal')" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold">Cancelar</button>
              <button type="submit" :disabled="guardando" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-lg text-sm font-semibold flex items-center gap-2">
                <svg v-if="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, toRef } from 'vue';
import CampoForm from './CampoForm.vue';
import Paginador from './Paginador.vue';
import EmptyState from '../EmptyState.vue';
import { useFocusTrap } from '../../composables/useFocusTrap';

const props = defineProps({
    users: { type: Array, required: true },
    trainers: { type: Array, required: true },
    currentUserId: { type: [Number, null], default: null },
    creando: { type: Boolean, required: true },
    guardando: { type: Boolean, required: true },
    errorCrear: { type: String, default: '' },
    successCrear: { type: String, default: '' },
    searchFilter: { type: String, default: '' },
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
    mostrarModal: { type: Boolean, required: true },
    formularioEdit: { type: Object, required: true },
});

const emit = defineEmits([
    'crear',
    'abrir-editar',
    'cerrar-modal',
    'guardar-edicion',
    'toggle-suspend',
    'eliminar',
    'update:searchFilter',
    'update:currentPage',
    'update:nuevoUsuario',
]);

const localSearch = ref(props.searchFilter);
const localPage = ref(props.currentPage);
const modalRef = ref(null);
useFocusTrap(modalRef, { when: toRef(props, 'mostrarModal') });
const nuevoUsuario = ref({
    nick: '',
    name: '',
    email: '',
    telefono: '',
    password: '',
    role: '',
    trainer_id: null,
});

watch(localSearch, (val) => emit('update:searchFilter', val));
watch(() => props.searchFilter, (val) => { localSearch.value = val; });
watch(localPage, (val) => emit('update:currentPage', val));
watch(() => props.currentPage, (val) => { localPage.value = val; });

const roleClass = (role) => {
    return {
        administrador: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        trainer: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        alumno: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        comun: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    }[role] || 'bg-gray-100 text-gray-700';
};
</script>
