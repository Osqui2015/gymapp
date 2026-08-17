<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Panel del Trainer' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard del Trainer</h2>
          <p class="text-gray-500 dark:text-gray-400 mt-1">Gestiona y monitorea a tus alumnos</p>
        </div>
        <a
          href="/dashboard"
          class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-all flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver
        </a>
      </div>

      <div v-if="cargando" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseSkeleton variant="stat-card" :count="3" />
        </div>
        <BaseSkeleton variant="table" :count="5" />
      </div>

      <div v-else>
        <!-- Métricas Clave -->
        <TrainerMetrics :metricas="metricas" />

        <!-- Lista de Alumnos -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-8">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mis Alumnos</h3>
              <input
                v-model="busqueda"
                type="text"
                placeholder="Buscar alumno..."
                class="w-full sm:w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <ResponsiveTable
            :rows="alumnosFiltrados"
            :columns="[
              { key: 'alumno', label: 'Alumno', thClass: 'text-left', tdClass: '', sortable: true, searchable: true },
              { key: 'rutina', label: 'Rutina', thClass: 'text-center', tdClass: '', sortable: true, searchable: true },
              { key: 'dia_actual', label: 'Día Actual', thClass: 'text-center', tdClass: '', sortable: true },
              { key: 'estado', label: 'Estado', thClass: 'text-center', tdClass: '', sortable: true },
              { key: 'acciones', label: 'Acciones', thClass: 'text-center', tdClass: '' },
            ]"
            thead-class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700"
            sortable
            filterable
            filter-placeholder="Buscar alumno o rutina…"
          >
            <template #rows="{ row: alumno }">
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center', alumno.activo_semana ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700']">
                      <span :class="['font-bold', alumno.activo_semana ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400']">
                        {{ alumno.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ alumno.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">@{{ alumno.nick }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <span v-if="alumno.rutina" class="px-3 py-1 bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 rounded-full text-xs font-semibold">
                    {{ alumno.rutina }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">Sin asignar</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="text-gray-700 dark:text-gray-300">{{ alumno.dia_actual || 'Día 1' }}</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span :class="['px-3 py-1 rounded-full text-xs font-semibold', alumno.activo_semana ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
                    {{ alumno.activo_semana ? 'Activo ✓' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <button
                    @click="verDetalleAlumno(alumno)"
                    class="inline-flex items-center gap-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Ver Progreso
                  </button>
                </td>
              </tr>
            </template>

            <template #cards="{ row: alumno }">
              <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-3">
                <!-- Header: avatar + nombre + estado -->
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div :class="['w-11 h-11 rounded-full flex items-center justify-center font-bold flex-shrink-0', alumno.activo_semana ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500']">
                      {{ alumno.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-semibold text-gray-900 dark:text-white truncate">{{ alumno.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ alumno.nick }}</p>
                    </div>
                  </div>
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap', alumno.activo_semana ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
                    {{ alumno.activo_semana ? 'Activo ✓' : 'Inactivo' }}
                  </span>
                </div>

                <!-- Rutina + día -->
                <div class="flex items-center justify-between text-sm">
                  <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rutina</span>
                  <span v-if="alumno.rutina" class="px-2.5 py-1 bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 rounded-full text-xs font-semibold">
                    {{ alumno.rutina }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">Sin asignar</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Día actual</span>
                  <span class="font-medium text-gray-700 dark:text-gray-300">{{ alumno.dia_actual || 'Día 1' }}</span>
                </div>

                <!-- Acción -->
                <button
                  @click="verDetalleAlumno(alumno)"
                  class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  Ver Progreso
                </button>
              </div>
            </template>
          </ResponsiveTable>

          <div v-if="alumnosFiltrados.length === 0">
            <EmptyState
              emoji="🎓"
              title="No tenés alumnos asignados"
              :description="busqueda ? 'No se encontraron alumnos con ese criterio.' : 'Cuando un admin te asigne alumnos van a aparecer acá.'"
              variant="compact"
            />
          </div>
        </div>

        <!-- Últimos Entrenamientos -->
        <RecentWorkouts :entrenos="metricas.ultimos_entrenamientos" />
      </div>
    </div>

    <!-- Modal de Detalle del Alumno -->
    <transition name="fade">
      <div v-if="mostrarModalAlumno" class="fixed inset-0 z-50 overflow-y-auto" @click.self="cerrarModalAlumno">
        <div class="flex items-start justify-center min-h-screen px-4 pt-8 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="cerrarModalAlumno"></div>

          <div ref="modalRef" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full" role="dialog" aria-modal="true">
            <!-- Header del Modal -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <span class="text-white text-xl font-bold">{{ alumnoSeleccionado.name?.charAt(0).toUpperCase() }}</span>
                </div>
                <div class="text-white">
                  <h3 class="text-xl font-bold">{{ alumnoSeleccionado.name }}</h3>
                  <p class="text-indigo-200 text-sm">@{{ alumnoSeleccionado.nick }}</p>
                </div>
              </div>
              <button @click="cerrarModalAlumno" class="text-white/80 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Contenido del Modal -->
            <div class="p-6 max-h-[70vh] overflow-y-auto">
              <div v-if="cargandoDetalle" class="space-y-4">
                <div class="flex gap-2 mb-4">
                  <BaseSkeleton variant="text" :count="4" class="w-32" />
                </div>
                <BaseSkeleton variant="card" :count="3" />
              </div>

              <div v-else>
                <!-- Tabs de Navegación -->
                <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700 pb-2">
                  <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="tabActivo = tab.id"
                    :class="[
                      'px-4 py-2 rounded-t-lg font-medium transition-all',
                      tabActivo === tab.id
                        ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                  >
                    {{ tab.nombre }}
                  </button>
                </div>

                <!-- Tab: Timeline -->
                <div v-if="tabActivo === 'timeline'">
                  <TrainerAlumnoTimeline :alumno="alumnoSeleccionado" />
                </div>

                <!-- Tab: Historial de Pesos -->
                <div v-if="tabActivo === 'pesos'">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historial de Pesos por Ejercicio</h4>
                  <div v-if="Object.keys(detalleAlumno.historial_pesos || {}).length > 0" class="space-y-4">
                    <div v-for="(registros, ejercicio) in detalleAlumno.historial_pesos" :key="ejercicio" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                      <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ ejercicio }}</h5>
                      <div class="space-y-1">
                        <div v-for="reg in registros.slice(-5)" :key="reg.fecha" class="flex justify-between text-sm">
                          <span class="text-gray-500 dark:text-gray-400">{{ reg.fecha }} - {{ reg.dia }}</span>
                          <span class="font-medium text-gray-900 dark:text-white">{{ reg.peso }} kg × {{ reg.reps }} reps</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-gray-500 dark:text-gray-400">No hay registro de pesos aún.</p>
                </div>

                <!-- Tab: Tonelaje -->
                <div v-if="tabActivo === 'tonelaje'">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tonelaje por Sesión</h4>
                  <div v-if="detalleAlumno.tonelaje_sesiones?.length > 0" class="space-y-2">
                    <div v-for="sesion in detalleAlumno.tonelaje_sesiones" :key="sesion.fecha" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                      <span class="text-gray-700 dark:text-gray-300">{{ sesion.fecha }}</span>
                      <div class="text-right">
                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ sesion.volumen_total }} kg</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ sesion.ejercicios_completados }} ejercicios)</span>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-gray-500 dark:text-gray-400">No hay datos de tonelaje aún.</p>
                </div>

                <!-- Tab: Medidas -->
                <div v-if="tabActivo === 'medidas'">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Medidas Corporales</h4>
                  <div v-if="detalleAlumno.medidas_corporales?.length > 0">
                    <ResponsiveTable
                      :rows="detalleAlumno.medidas_corporales.slice(-10).reverse()"
                      :columns="[
                        { key: 'fecha', label: 'Fecha', thClass: 'text-left', tdClass: '', sortable: true },
                        { key: 'peso', label: 'Peso', thClass: 'text-center', tdClass: '', sortable: true },
                        { key: 'pecho', label: 'Pecho', thClass: 'text-center', tdClass: '', sortable: true },
                        { key: 'cintura', label: 'Cintura', thClass: 'text-center', tdClass: '', sortable: true },
                        { key: 'brazos', label: 'Brazos', thClass: 'text-center', tdClass: '', sortable: true },
                        { key: 'muslos', label: 'Muslos', thClass: 'text-center', tdClass: '', sortable: true },
                      ]"
                      thead-class="bg-gray-100 dark:bg-gray-900"
                      sortable
                    >
                      <template #rows="{ row: medida }">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                          <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ medida.fecha }}</td>
                          <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ medida.peso || '-' }} kg</td>
                          <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ medida.pecho || '-' }}</td>
                          <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ medida.cintura || '-' }}</td>
                          <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ medida.brazos || '-' }}</td>
                          <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ medida.muslos || '-' }}</td>
                        </tr>
                      </template>

                      <template #cards="{ row: medida }">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-2">
                          <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ medida.fecha }}</span>
                          </div>
                          <div class="pt-2 border-t border-gray-100 dark:border-gray-700 grid grid-cols-5 gap-2 text-center">
                            <div>
                              <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase">Peso</p>
                              <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ medida.peso || '-' }}<span v-if="medida.peso" class="text-[10px] ml-0.5">kg</span></p>
                            </div>
                            <div>
                              <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase">Pecho</p>
                              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ medida.pecho || '-' }}</p>
                            </div>
                            <div>
                              <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase">Cintura</p>
                              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ medida.cintura || '-' }}</p>
                            </div>
                            <div>
                              <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase">Brazos</p>
                              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ medida.brazos || '-' }}</p>
                            </div>
                            <div>
                              <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 uppercase">Muslos</p>
                              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ medida.muslos || '-' }}</p>
                            </div>
                          </div>
                        </div>
                      </template>
                    </ResponsiveTable>
                  </div>
                  <p v-else class="text-gray-500 dark:text-gray-400">No hay registro de medidas aún.</p>
                </div>

                <!-- Tab: Historial Completo (para comentarios) -->
                <div v-if="tabActivo === 'historial'">
                  <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Historial de Entrenamientos</h4>
                  <div v-if="detalleAlumno.historial_completo?.length > 0" class="space-y-3">
                    <div v-for="item in detalleAlumno.historial_completo" :key="item.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                      <div class="flex justify-between items-start mb-2">
                        <div>
                          <span class="font-semibold text-gray-900 dark:text-white">{{ item.ejercicio_nombre }}</span>
                          <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">Serie {{ item.series_numero }}</span>
                        </div>
                        <div class="text-right">
                          <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ item.peso }} kg</span>
                          <span class="text-gray-500 ml-1">× {{ item.reps_realizadas }}</span>
                        </div>
                      </div>
                      <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                        <span>{{ item.fecha }} - {{ item.dia }}</span>
                        <span :class="item.completado ? 'text-green-600' : 'text-red-600'">
                          {{ item.completado ? '✓ Completado' : 'Pendiente' }}
                        </span>
                      </div>
                      
                      <!-- Comentario del Trainer -->
                      <div v-if="item.comentario_trainer" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-2">
                        <div class="flex items-center gap-2 mb-1">
                          <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                          </svg>
                          <span class="font-semibold text-yellow-800 dark:text-yellow-300 text-sm">{{ item.trainer_nombre || 'Trainer' }}</span>
                        </div>
                        <p class="text-yellow-800 dark:text-yellow-200 text-sm">{{ item.comentario_trainer }}</p>
                      </div>

                      <!-- Agregar Comentario -->
                      <div class="flex gap-2">
                        <input
                          v-model="comentariosForm[item.id]"
                          type="text"
                          placeholder="Agregar nota o corrección..."
                          class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500"
                        />
                        <button
                          @click="agregarComentario(item.id)"
                          :disabled="!comentariosForm[item.id]?.trim()"
                          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                        >
                          Guardar
                        </button>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-gray-500 dark:text-gray-400">No hay historial registrado aún.</p>
                </div>
              </div>
            </div>

            <!-- Footer del Modal -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end">
              <button
                @click="cerrarModalAlumno"
                class="px-6 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import Breadcrumbs from './Breadcrumbs.vue';
import { useFocusTrap } from '../composables/useFocusTrap';
import ResponsiveTable from './ResponsiveTable.vue';
import EmptyState from './EmptyState.vue';
import BaseSkeleton from './BaseSkeleton.vue';
import TrainerMetrics from './trainer/TrainerMetrics.vue';
import RecentWorkouts from './trainer/RecentWorkouts.vue';
import TrainerAlumnoTimeline from './trainer/TrainerAlumnoTimeline.vue';

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);
const cargando = ref(true);
const cargandoDetalle = ref(false);
const mostrarModalAlumno = ref(false);
const modalRef = ref(null);
useFocusTrap(modalRef, { when: mostrarModalAlumno });
const metricas = ref({
  alumnos_activos: 0,
  alumnos_inactivos: 0,
  alumnos_inactivos_7dias: [],
  ultimos_entrenamientos: [],
  total_alumnos: 0,
  alumnos: [],
});
const detalleAlumno = ref({});
const tabActivo = ref('pesos');
const comentariosForm = ref({});

const tabs = [
  { id: 'timeline', nombre: '⏱️ Timeline' },
  { id: 'pesos', nombre: '📊 Historial de Pesos' },
  { id: 'tonelaje', nombre: '🏋️ Tonelaje' },
  { id: 'medidas', nombre: '📏 Medidas Corporales' },
  { id: 'historial', nombre: '📝 Historial & Notas' },
];

const busqueda = ref('');

const alumnoSeleccionado = ref({
  id: null,
  name: '',
  nick: '',
});

const alumnosFiltrados = computed(() => {
  if (!busqueda.value.trim()) {
    return metricas.value.alumnos || [];
  }
  const search = busqueda.value.toLowerCase();
  return (metricas.value.alumnos || []).filter(a => 
    a.name.toLowerCase().includes(search) ||
    (a.nick && a.nick.toLowerCase().includes(search))
  );
});

const fetchDashboard = async () => {
  try {
    const response = await axios.get('/api/trainer/dashboard');
    metricas.value = response.data;
  } catch (error) {
    console.error('Error al cargar dashboard:', error);
    showToast('No se pudo cargar el dashboard', 'error');
  } finally {
    cargando.value = false;
  }
};

const verDetalleAlumno = async (alumno) => {
  alumnoSeleccionado.value = alumno;
  mostrarModalAlumno.value = true;
  cargandoDetalle.value = true;
  tabActivo.value = 'pesos';

  try {
    const response = await axios.get(`/api/trainer/alumno/${alumno.id}`);
    detalleAlumno.value = response.data;
    comentariosForm.value = {};
  } catch (error) {
    console.error('Error al cargar detalle:', error);
    showToast('No se pudo cargar el detalle del alumno', 'error');
  } finally {
    cargandoDetalle.value = false;
  }
};

const cerrarModalAlumno = () => {
  mostrarModalAlumno.value = false;
  detalleAlumno.value = {};
  comentariosForm.value = {};
};

const agregarComentario = async (historialId) => {
  const comentario = comentariosForm.value[historialId]?.trim();
  if (!comentario) return;

  try {
    await axios.post(`/api/trainer/alumno/${alumnoSeleccionado.value.id}/comentario`, {
      historial_id: historialId,
      comentario: comentario,
    });

    // Actualizar el historial local
    const item = detalleAlumno.value.historial_completo?.find(h => h.id === historialId);
    if (item) {
      item.comentario_trainer = comentario;
    }

    comentariosForm.value[historialId] = '';
    showToast('Comentario guardado correctamente');
  } catch (error) {
    console.error('Error al guardar comentario:', error);
    showToast('No se pudo guardar el comentario', 'error');
  }
};

onMounted(() => {
  fetchDashboard();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>