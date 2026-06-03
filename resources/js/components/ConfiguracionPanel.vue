<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Configuración</h1>

            <!-- Gestión de Usuarios (solo administradores) -->
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
                    <form @submit.prevent="crearUsuario" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nick *</label>
                            <input v-model="nuevoUsuario.nick" type="text" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="usuario123">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo *</label>
                            <input v-model="nuevoUsuario.name" type="text" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Juan Pérez">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                            <input v-model="nuevoUsuario.email" type="email" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="correo@ejemplo.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña *</label>
                            <input v-model="nuevoUsuario.password" type="password" required minlength="6"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol *</label>
                            <select v-model="nuevoUsuario.role" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Seleccionar rol</option>
                                <option value="comun">Común</option>
                                <option value="alumno">Alumno</option>
                                <option value="trainer">Trainer</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                        <div v-if="nuevoUsuario.role === 'alumno'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trainer Asignado</label>
                            <select v-model="nuevoUsuario.trainer_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="null">Sin trainer</option>
                                <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                                    {{ trainer.name }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-2 lg:col-span-3 flex items-end pt-2">
                            <button type="submit" :disabled="creando"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors flex items-center gap-2">
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
                    <div v-if="errorCrear" class="mt-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300">
                        {{ errorCrear }}
                    </div>
                    <div v-if="successCrear" class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300">
                        {{ successCrear }}
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Lista de Usuarios ({{ totalUsers }})
                        </h3>
                        
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                            <!-- Buscador -->
                            <div class="relative w-full sm:w-72">
                                <input v-model="searchFilter" type="text"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    placeholder="Buscar por nombre, nick, email...">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <button @click="cargarUsuarios" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors border border-gray-200 dark:border-gray-700 flex items-center justify-center gap-1 text-sm text-gray-600 dark:text-gray-300" title="Actualizar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Nick</th>
                                    <th class="px-4 py-3 font-semibold">Nombre</th>
                                    <th class="px-4 py-3 font-semibold">Email</th>
                                    <th class="px-4 py-3 font-semibold">Rol</th>
                                    <th class="px-4 py-3 font-semibold">Trainer</th>
                                    <th class="px-4 py-3 font-semibold">Estado</th>
                                    <th class="px-4 py-3 font-semibold text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users" :key="user.id" 
                                    :class="[user.suspended ? 'bg-red-50 dark:bg-red-900/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700']"
                                    class="transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ user.nick }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ user.name }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ user.email }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="{
                                            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': user.role === 'administrador',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': user.role === 'trainer',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': user.role === 'alumno',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': user.role === 'comun'
                                        }" class="px-2 py-1 rounded-full text-xs font-medium capitalize">
                                            {{ user.role === 'comun' ? 'común' : user.role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ user.trainer?.name || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="user.suspended" class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                            Suspendido
                                        </span>
                                        <span v-else class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            Activo
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Editar -->
                                            <button @click="abrirModalEditar(user)"
                                                title="Editar Usuario"
                                                class="p-2 rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 text-indigo-600 dark:text-indigo-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            
                                            <!-- Suspender/Activar -->
                                            <button @click="toggleSuspend(user)" :disabled="user.id === currentUserId"
                                                :title="user.suspended ? 'Activar' : 'Suspender'"
                                                class="p-2 rounded-lg transition-colors"
                                                :class="user.id === currentUserId ? 'text-gray-300 cursor-not-allowed opacity-50' : (user.suspended ? 'hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600' : 'hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600')">
                                                <svg v-if="user.suspended" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </button>

                                            <!-- Eliminar -->
                                            <button @click="eliminarUsuario(user)" :disabled="user.id === currentUserId"
                                                title="Eliminar"
                                                class="p-2 rounded-lg transition-colors"
                                                :class="user.id === currentUserId ? 'text-gray-300 cursor-not-allowed opacity-50' : 'hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No se encontraron usuarios.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6 mt-4">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button @click="currentPage > 1 ? currentPage-- : null" :disabled="currentPage === 1"
                                class="relative inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                                Anterior
                            </button>
                            <button @click="currentPage < totalPages ? currentPage++ : null" :disabled="currentPage === totalPages"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                                Siguiente
                            </button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Mostrando
                                    <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                                    a
                                    <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, totalUsers) }}</span>
                                    de
                                    <span class="font-medium">{{ totalUsers }}</span>
                                    usuarios
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm bg-white dark:bg-gray-800" aria-label="Pagination">
                                    <button @click="currentPage = 1" :disabled="currentPage === 1"
                                        class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 disabled:opacity-50">
                                        <span class="sr-only">Primero</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M15.79 14.77a.75.75 0 01-1.06.02L10 11.06 6.27 14.79a.75.75 0 11-1.04-1.08l4.25-4.25a.75.75 0 011.06 0l4.25 4.25a.75.75 0 01-.02 1.06zm0-6a.75.75 0 01-1.06.02L10 5.06 6.27 8.79a.75.75 0 01-1.04-1.08l4.25-4.25a.75.75 0 011.06 0l4.25 4.25a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button @click="currentPage > 1 ? currentPage-- : null" :disabled="currentPage === 1"
                                        class="relative inline-flex items-center px-2 py-2 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 disabled:opacity-50">
                                        <span class="sr-only">Anterior</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600">
                                        Página {{ currentPage }} de {{ totalPages }}
                                    </span>
                                    <button @click="currentPage < totalPages ? currentPage++ : null" :disabled="currentPage === totalPages"
                                        class="relative inline-flex items-center px-2 py-2 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 disabled:opacity-50">
                                        <span class="sr-only">Siguiente</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 11-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                                        class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 disabled:opacity-50">
                                        <span class="sr-only">Último</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.21 14.77a.75.75 0 01.02-1.06L7.94 10 4.23 6.27a.75.75 0 111.08-1.04l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06-.02zm6 0a.75.75 0 01.02-1.06L13.94 10l-3.71-3.73a.75.75 0 111.08-1.04l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Usuario -->
        <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center px-4" @click.self="cerrarModal">
            <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-950/80 transition-opacity"></div>
            
            <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full relative z-10 border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-500 text-white flex justify-between items-center">
                    <h3 class="text-lg font-bold">Editar Usuario: @{{ formularioEdit.nick }}</h3>
                    <button @click="cerrarModal" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarEdicion" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo *</label>
                        <input v-model="formularioEdit.name" type="text" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                        <input v-model="formularioEdit.email" type="email" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol *</label>
                        <select v-model="formularioEdit.role" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="comun">Común</option>
                            <option value="alumno">Alumno</option>
                            <option value="trainer">Trainer</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>

                    <div v-if="formularioEdit.role === 'alumno'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trainer Asignado</label>
                        <select v-model="formularioEdit.trainer_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option :value="null">Sin trainer</option>
                            <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                                {{ trainer.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña (dejar en blanco para no cambiar)</label>
                        <input v-model="formularioEdit.password" type="password" minlength="6"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="cerrarModal"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-lg text-sm font-semibold flex items-center gap-2">
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

        <!-- Toast Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 pointer-events-auto overflow-hidden">
                <div class="p-4 flex items-center gap-3">
                    <div class="shrink-0">
                        <svg v-if="toast.type === 'success'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 col-span-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ toast.type === 'success' ? 'Éxito' : 'Error' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ toast.message }}
                        </p>
                    </div>
                    <button @click="toast.show = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const users = ref([]);
const trainers = ref([]);
const currentUserId = ref(null);
const creando = ref(false);
const guardando = ref(false);
const errorCrear = ref('');
const successCrear = ref('');
const mostrarModal = ref(false);

const nuevoUsuario = ref({
    nick: '',
    name: '',
    email: '',
    password: '',
    role: '',
    trainer_id: null
});

const formularioEdit = ref({
    id: null,
    nick: '',
    name: '',
    email: '',
    password: '',
    role: '',
    trainer_id: null
});

// Búsqueda y paginación
const searchFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalUsers = ref(0);
const totalPages = ref(1);

// Toast
const toast = ref({
    show: false,
    message: '',
    type: 'success'
});

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 4000);
};

const cargarDatos = async () => {
    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                page: currentPage.value,
                search: searchFilter.value,
                per_page: itemsPerPage.value
            }
        });
        
        if (response.data.users !== undefined) {
            users.value = response.data.users;
            totalUsers.value = response.data.total;
            currentPage.value = response.data.current_page;
            totalPages.value = response.data.last_page;
            trainers.value = response.data.trainers || [];
            currentUserId.value = response.data.current_user_id;
        } else {
            users.value = response.data;
            totalUsers.value = response.data.length;
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
};

// watch page and search term for server-side updates
watch([currentPage, searchFilter], () => {
    cargarDatos();
});

// Reset page to 1 when search term changes
watch(searchFilter, () => {
    currentPage.value = 1;
});

const crearUsuario = async () => {
    errorCrear.value = '';
    successCrear.value = '';
    creando.value = true;

    try {
        await axios.post('/admin/users', nuevoUsuario.value);
        showToast('Usuario creado exitosamente', 'success');
        nuevoUsuario.value = {
            nick: '',
            name: '',
            email: '',
            password: '',
            role: '',
            trainer_id: null
        };
        await cargarDatos();
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            errorCrear.value = Object.values(errors).flat().join(', ');
        } else {
            errorCrear.value = error.response?.data?.message || 'Error al crear usuario';
        }
        showToast('Error al crear usuario', 'error');
    } finally {
        creando.value = false;
        setTimeout(() => {
            successCrear.value = '';
            errorCrear.value = '';
        }, 5000);
    }
};

const abrirModalEditar = (user) => {
    formularioEdit.value = {
        id: user.id,
        nick: user.nick,
        name: user.name,
        email: user.email,
        password: '',
        role: user.role,
        trainer_id: user.trainer_id
    };
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
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
        await cargarDatos();
        cerrarModal();
        showToast('Usuario actualizado correctamente', 'success');
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.error || 'Error al guardar cambios';
        showToast(msg, 'error');
    } finally {
        guardando.value = false;
    }
};

const toggleSuspend = async (user) => {
    const accion = user.suspended ? 'activar' : 'suspender';
    if (!confirm(`¿Estás seguro de ${accion} al usuario "${user.name}"?`)) return;

    try {
        await axios.patch(`/api/admin/users/${user.id}/toggle-suspend`);
        user.suspended = !user.suspended;
        showToast(`Usuario ${user.suspended ? 'suspendido' : 'activado'} correctamente`, 'success');
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.error || 'Error al cambiar estado';
        showToast(msg, 'error');
    }
};

const eliminarUsuario = async (user) => {
    if (!confirm(`¿Estás seguro de eliminar al usuario "${user.name}"?`)) return;
    
    try {
        await axios.delete(`/api/admin/users/${user.id}`);
        await cargarDatos();
        showToast('Usuario eliminado correctamente', 'success');
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.error || 'Error al eliminar usuario';
        showToast(msg, 'error');
    }
};

const cargarUsuarios = () => {
    cargarDatos();
};

onMounted(() => {
    cargarDatos();
});
</script>
