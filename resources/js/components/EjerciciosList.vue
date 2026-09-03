<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <Breadcrumbs :items="[{ label: 'Inicio', href: '/dashboard' }, { label: 'Ejercicios' }]" class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Ejercicios</h2>
        <button
          v-if="userRole === 'trainer' || userRole === 'administrador'"
          @click="mostrarModal = true"
          class="hidden md:inline-flex bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg"
        >
          + Agregar Ejercicio
        </button>
      </div>

      <!-- Layout principal:
           - Mobile: body map arriba (sticky al top) + lista abajo
           - Desktop: body map en sidebar izquierda (sticky) + lista a la derecha -->
      <div class="md:grid md:grid-cols-[400px_1fr] md:gap-6">

        <!-- ===== Body map (sidebar en desktop, top bar en mobile) ===== -->
        <aside class="md:sticky md:top-20 md:self-start mb-6 md:mb-0">
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-100 dark:border-gray-700">
              <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex-shrink-0">Mapa</span>
                <span class="text-xs text-gray-700 dark:text-gray-300 truncate">· {{ bodyMapModoLabel }}</span>
              </div>
              <button
                v-if="musculoFiltroBodyMap"
                @click="limpiarFiltroMusculo"
                class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex-shrink-0"
                title="Quitar filtro de músculo"
              >
                Limpiar
              </button>
            </div>
            <!-- Mobile: altura fija con cuerpo completo de pies a cabeza.
                 Desktop: ambos lados (frente + espalda) lado a lado. -->
            <div class="bg-gray-50 dark:bg-gray-900/40 h-[300px] md:h-auto md:max-h-[calc(100vh-7rem)] overflow-hidden flex items-center justify-center p-2">
              <!-- Mobile: solo frente -->
              <BodyMap
                :levels="bodyMapLevels"
                :comparison-levels="bodyMapComparisonLevels"
                :muscle-labels="muscleLabels"
                mode="balance"
                :compact="true"
                class="h-full w-full md:hidden flex flex-col items-center justify-center"
                @muscle-click="onMuscleClickBodyMap"
              />
              <!-- Desktop: frente + espalda lado a lado -->
              <BodyMap
                :levels="bodyMapLevels"
                :comparison-levels="bodyMapComparisonLevels"
                :muscle-labels="muscleLabels"
                mode="balance"
                :compact="true"
                :show-both-views="true"
                class="hidden md:block p-2"
                @muscle-click="onMuscleClickBodyMap"
              />
            </div>
            <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
              <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
                Tocá un músculo para filtrar
              </p>
              <button
                @click="bodyMapExpandido = true"
                class="px-2.5 py-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg inline-flex items-center gap-1.5 flex-shrink-0 transition-colors"
                title="Ver cuerpo humano en grande"
                aria-label="Expandir mapa corporal"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                Expandir en grande
              </button>
            </div>
            <!-- Leyenda compacta: cambia segun el modo del body map. -->
            <!-- Leyenda cambia segun el modo: comparar, highlight de ejercicio, o recencia. -->
            <div
              v-if="modoComparar"
              class="px-3 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
            >
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                Comparando
              </p>
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #a5b4fc"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Indigo</strong> · {{ ejercicioAComparar?.nombre }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #67e8f9"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Cyan</strong> · {{ ejercicioBComparar?.nombre }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #7c3aed"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Morado</strong> · músculos en común
                  </span>
                </div>
                <button
                  @click="limpiarSeleccion"
                  class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline mt-1"
                >
                  Limpiar selección
                </button>
              </div>
            </div>
            <div
              v-else-if="ejercicioAComparar"
              class="px-3 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
            >
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                Qué significa el color
              </p>
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #a5b4fc"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Indigo fuerte</strong> · músculo principal
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #4338ca"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Indigo oscuro</strong> · músculo secundario
                  </span>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 italic">
                  Tip: clickeá otro ejercicio para comparar
                </p>
              </div>
            </div>
            <div
              v-else
              class="px-3 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
            >
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                Recencia · cuánto hace entrenaste
              </p>
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #1f2937"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Gris</strong> · reciente (0-3 días)
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #4338ca"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Indigo</strong> · hace 1-2 semanas
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: #a5b4fc"></span>
                  <span class="text-[10px] text-gray-700 dark:text-gray-300 leading-tight">
                    <strong class="font-semibold">Indigo claro</strong> · +30 días o nunca
                  </span>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <!-- ===== Columna principal: filtros + lista ===== -->
        <div>
          <div v-if="musculoFiltroBodyMap" class="mb-4 flex items-center justify-between gap-3 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 rounded-lg">
            <p class="text-sm text-indigo-700 dark:text-indigo-300">
              Filtrando por: <strong>{{ muscleLabels[musculoFiltroBodyMap] || musculoFiltroBodyMap }}</strong>
              · {{ total }} resultado(s)
            </p>
            <button
              @click="limpiarFiltroMusculo"
              class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:underline flex-shrink-0"
            >
              Limpiar
            </button>
          </div>

      <div class="mb-6 space-y-3">
        <!-- Buscador -->
        <div class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre o equipamiento..."
            class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @keyup.enter="buscar"
          />
          <button
            @click="buscar"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg"
          >
            Buscar
          </button>
          <button
            v-if="busqueda || grupoMuscularFiltro || equipamientoFiltro || musculoFiltroBodyMap"
            @click="limpiarBusqueda"
            class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-3 rounded-lg text-sm font-medium transition-all"
          >
            Limpiar todo
          </button>
        </div>

        <!-- Filtros desplegables con buscador: Grupo Muscular y Equipamiento -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          <!-- Dropdown con búsqueda: Grupo Muscular -->
          <div class="relative" ref="grupoDropdownRef">
            <button
              type="button"
              @click="toggleGrupoDropdown"
              class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 text-sm font-medium transition-all shadow-xs"
              :class="grupoMuscularFiltro
                ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/20 bg-indigo-50/20 dark:bg-indigo-950/20'
                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-gray-400'"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="text-xs uppercase font-bold text-gray-400 dark:text-gray-500">Grupo:</span>
                <span class="truncate font-semibold">{{ grupoMuscularFiltro || 'Todos los grupos' }}</span>
              </div>
              <div class="flex items-center gap-1.5 ml-2 flex-shrink-0">
                <span
                  v-if="grupoMuscularFiltro"
                  @click.stop="seleccionarGrupo('')"
                  class="text-gray-400 hover:text-red-500 font-bold px-1 text-xs"
                  title="Quitar filtro de grupo"
                >✕</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': grupoSelectOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </button>

            <!-- Menú desplegable con input para buscar -->
            <div
              v-if="grupoSelectOpen"
              class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
              <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/50">
                <input
                  ref="grupoSearchInputRef"
                  v-model="grupoSearchText"
                  type="text"
                  placeholder="Escribí para buscar grupo..."
                  class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div class="max-h-56 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-750">
                <button
                  type="button"
                  @click="seleccionarGrupo('')"
                  class="w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                  :class="!grupoMuscularFiltro ? 'font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-gray-700 dark:text-gray-300'"
                >
                  Todos los grupos
                </button>
                <button
                  v-for="grupo in gruposFiltrados"
                  :key="grupo"
                  type="button"
                  @click="seleccionarGrupo(grupo)"
                  class="w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-950/40 flex items-center justify-between"
                  :class="grupoMuscularFiltro === grupo ? 'font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-gray-700 dark:text-gray-300'"
                >
                  <span>{{ grupo }}</span>
                  <span v-if="grupoMuscularFiltro === grupo" class="text-indigo-600 dark:text-indigo-400 text-xs">✓</span>
                </button>
                <div v-if="!gruposFiltrados.length" class="px-4 py-3 text-xs text-gray-400 text-center">
                  No se encontró "{{ grupoSearchText }}"
                </div>
              </div>
            </div>
          </div>

          <!-- Dropdown con búsqueda: Equipamiento -->
          <div class="relative" ref="equipoDropdownRef">
            <button
              type="button"
              @click="toggleEquipoDropdown"
              class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 text-sm font-medium transition-all shadow-xs"
              :class="equipamientoFiltro
                ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/20 bg-indigo-50/20 dark:bg-indigo-950/20'
                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-gray-400'"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="text-xs uppercase font-bold text-gray-400 dark:text-gray-500">Equipo:</span>
                <span class="truncate font-semibold">{{ equipamientoFiltro || 'Todo el equipamiento' }}</span>
              </div>
              <div class="flex items-center gap-1.5 ml-2 flex-shrink-0">
                <span
                  v-if="equipamientoFiltro"
                  @click.stop="seleccionarEquipamiento('')"
                  class="text-gray-400 hover:text-red-500 font-bold px-1 text-xs"
                  title="Quitar filtro de equipo"
                >✕</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': equipoSelectOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </button>

            <!-- Menú desplegable con input para buscar -->
            <div
              v-if="equipoSelectOpen"
              class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
              <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/50">
                <input
                  ref="equipoSearchInputRef"
                  v-model="equipoSearchText"
                  type="text"
                  placeholder="Escribí para buscar equipo..."
                  class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div class="max-h-56 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-750">
                <button
                  type="button"
                  @click="seleccionarEquipamiento('')"
                  class="w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                  :class="!equipamientoFiltro ? 'font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-gray-700 dark:text-gray-300'"
                >
                  Todo el equipamiento
                </button>
                <button
                  v-for="eq in equipamientosFiltrados"
                  :key="eq"
                  type="button"
                  @click="seleccionarEquipamiento(eq)"
                  class="w-full px-4 py-2 text-left text-sm transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-950/40 flex items-center justify-between"
                  :class="equipamientoFiltro === eq ? 'font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/20' : 'text-gray-700 dark:text-gray-300'"
                >
                  <span>{{ eq }}</span>
                  <span v-if="equipamientoFiltro === eq" class="text-indigo-600 dark:text-indigo-400 text-xs">✓</span>
                </button>
                <div v-if="!equipamientosFiltrados.length" class="px-4 py-3 text-xs text-gray-400 text-center">
                  No se encontró "{{ equipoSearchText }}"
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <!-- Desktop: tabla tradicional -->
        <div class="overflow-x-auto hidden md:block">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Equipamiento</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Grupo Muscular</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Última</th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Descripción</th>
                <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="ejercicio in ejercicios"
                :key="ejercicio.id"
                @click="seleccionarEjercicioBodyMap(ejercicio)"
                :class="[
                  'cursor-pointer transition-colors',
                  ejercicioSeleccionadoBodyMap?.id === ejercicio.id
                    ? 'bg-indigo-50 dark:bg-indigo-950/30 ring-1 ring-inset ring-indigo-300 dark:ring-indigo-700'
                    : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                ]"
              >
                <td class="px-4 py-4 font-medium text-gray-900 dark:text-white">
                  <div class="flex items-center gap-2">
                    <button
                      @click="toggleFavorito(ejercicio, $event)"
                      :title="ejercicio.is_favorite ? 'Quitar de favoritos' : 'Marcar como favorito'"
                      :aria-label="ejercicio.is_favorite ? 'Quitar de favoritos' : 'Marcar como favorito'"
                      class="text-lg leading-none transition-transform hover:scale-110 flex-shrink-0"
                      :class="ejercicio.is_favorite ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600 hover:text-yellow-400'"
                    >
                      {{ ejercicio.is_favorite ? '★' : '☆' }}
                    </button>
                    <span>{{ ejercicio.nombre }}</span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                    {{ ejercicio.equipamiento }}
                  </span>
                </td>
                <td class="px-4 py-4 text-gray-700 dark:text-gray-300">{{ ejercicio.grupo_muscular || '-' }}</td>
                <td class="px-4 py-4">
                  <span :class="['inline-block px-2 py-0.5 rounded text-xs font-medium', relativeTimeBadge(relativeTime(ejercicio.last_trained_at).color)]">
                    {{ relativeTime(ejercicio.last_trained_at).text }}
                  </span>
                </td>
                <td class="px-4 py-4 text-gray-500 dark:text-gray-400 text-xs max-w-xs">{{ ejercicio.descripcion?.substring(0, 80) || '-' }}{{ ejercicio.descripcion?.length > 80 ? '...' : '' }}</td>
                <td class="px-4 py-4 text-center">
                  <div class="inline-flex items-center gap-2">
                    <button
                      @click="quickLog(ejercicio, $event)"
                      title="Marcar como hecho hoy"
                      aria-label="Marcar como hecho hoy"
                      class="text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 px-2 py-1 rounded text-sm font-bold transition-all"
                    >
                      ✓
                    </button>
                    <button
                      @click="verDetalle(ejercicio)"
                      class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 px-3 py-1 rounded text-sm font-medium transition-all"
                    >
                      Ver
                    </button>
                    <button
                      v-if="userRole === 'trainer' || userRole === 'administrador'"
                      @click="eliminar(ejercicio.id)"
                      class="text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 px-3 py-1 rounded text-sm font-medium transition-all"
                    >
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="ejercicios.length === 0">
                <td colspan="5">
                  <EmptyStateIllustrated
                    variant="no-ejercicios"
                    title="No hay ejercicios"
                    :description="busqueda || grupoMuscularFiltro || equipamientoFiltro ? 'No se encontraron ejercicios con esos filtros. Probá con otros criterios.' : 'Cuando crees rutinas o agregues ejercicios, van a aparecer acá.'"
                    :cta-text="(userRole === 'trainer' || userRole === 'administrador') && !busqueda ? 'Agregar el primero' : null"
                    cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    @cta="mostrarModal = true"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile: cards -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
          <div
            v-for="ejercicio in ejercicios"
            :key="ejercicio.id"
            @click="seleccionarEjercicioBodyMap(ejercicio)"
            :class="[
              'p-4 space-y-2 cursor-pointer transition-colors',
              ejercicioSeleccionadoBodyMap?.id === ejercicio.id
                ? 'bg-indigo-50 dark:bg-indigo-950/30 ring-1 ring-inset ring-indigo-300 dark:ring-indigo-700'
                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
            ]"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2 flex-1 min-w-0">
                <button
                  @click="toggleFavorito(ejercicio, $event)"
                  :title="ejercicio.is_favorite ? 'Quitar de favoritos' : 'Marcar como favorito'"
                  :aria-label="ejercicio.is_favorite ? 'Quitar de favoritos' : 'Marcar como favorito'"
                  class="text-lg leading-none transition-transform active:scale-90 flex-shrink-0"
                  :class="ejercicio.is_favorite ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                >
                  {{ ejercicio.is_favorite ? '★' : '☆' }}
                </button>
                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ ejercicio.nombre }}</p>
              </div>
              <button
                @click="quickLog(ejercicio, $event)"
                title="Marcar como hecho hoy"
                aria-label="Marcar como hecho hoy"
                class="flex-shrink-0 px-2.5 py-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded text-sm font-bold transition-all"
              >
                ✓
              </button>
              <button
                v-if="userRole === 'trainer' || userRole === 'administrador'"
                @click="eliminar(ejercicio.id)"
                class="flex-shrink-0 px-2.5 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded text-xs font-medium transition-all"
              >
                Eliminar
              </button>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                {{ ejercicio.equipamiento }}
              </span>
              <span v-if="ejercicio.grupo_muscular" class="px-2 py-0.5 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 rounded text-xs font-medium">
                {{ ejercicio.grupo_muscular }}
              </span>
              <span :class="['px-2 py-0.5 rounded text-xs font-medium', relativeTimeBadge(relativeTime(ejercicio.last_trained_at).color)]">
                {{ relativeTime(ejercicio.last_trained_at).text }}
              </span>
            </div>
            <p v-if="ejercicio.descripcion" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
              {{ ejercicio.descripcion }}
            </p>
          </div>

          <div v-if="ejercicios.length === 0">
            <EmptyState
              emoji="🏋️"
              title="No hay ejercicios"
              :description="busqueda || grupoMuscularFiltro || equipamientoFiltro ? 'No se encontraron ejercicios con esos filtros. Probá con otros criterios.' : 'Cuando crees rutinas o agregues ejercicios, van a aparecer acá.'"
              :cta-text="(userRole === 'trainer' || userRole === 'administrador') && !busqueda ? 'Agregar el primero' : null"
              cta-icon="M12 6v6m0 0v6m0-6h6m-6 0H6"
              @cta="mostrarModal = true"
              variant="compact"
            />
          </div>
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
        <div ref="modalRef" class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg shadow-2xl border border-gray-200 dark:border-gray-700" role="dialog" aria-modal="true">
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
        </div><!-- /columna derecha -->
      </div><!-- /md:grid -->

    <!-- FAB (mobile only): agregar nuevo ejercicio -->
    <button
      v-if="userRole === 'trainer' || userRole === 'administrador'"
      @click="mostrarModal = true"
      class="md:hidden fixed bottom-20 right-4 z-30 inline-flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/30 active:scale-95 transition-transform"
      aria-label="Agregar nuevo ejercicio"
      title="Agregar ejercicio"
    >
      <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
      </svg>
    </button>

    <!-- Modal de detalle del ejercicio (con video player) -->
    <EjercicioDetailModal
      v-model:open="mostrarDetail"
      :ejercicio="ejercicioSeleccionado"
    />

    <!-- Modal expand del body map (vista full + filtros por músculo) -->
    <Teleport to="body">
      <div
        v-if="bodyMapExpandido"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
        @click.self="bodyMapExpandido = false"
        role="dialog"
        aria-modal="true"
        aria-label="Mapa corporal expandido"
      >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mapa corporal</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Hacé click en un músculo para filtrar la lista de ejercicios</p>
            </div>
            <button
              @click="bodyMapExpandido = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              aria-label="Cerrar"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="p-5">
            <BodyMap
              :levels="bodyMapLevels"
              :muscle-labels="muscleLabels"
              mode="balance"
              :show-gender-toggle="true"
              @muscle-click="(slug) => { onMuscleClickBodyMap(slug); bodyMapExpandido = false; }"
            />
            <div v-if="musculoFiltroBodyMap" class="mt-4 flex items-center justify-between gap-3 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 rounded-lg">
              <p class="text-sm text-indigo-700 dark:text-indigo-300">
                Filtrando por: <strong>{{ muscleLabels[musculoFiltroBodyMap] || musculoFiltroBodyMap }}</strong>
                · {{ total }} resultado(s)
              </p>
              <button
                @click="limpiarFiltroMusculo"
                class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 hover:underline flex-shrink-0"
              >
                Limpiar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { useFocusTrap } from '../composables/useFocusTrap';
import { cachedAxiosGet } from '../composables/useApiCache';
import EmptyState from './EmptyState.vue'; // legacy, reemplazado por EmptyStateIllustrated gradualmente
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import EjercicioDetailModal from './EjercicioDetailModal.vue';
import BodyMap from './BodyMap.vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const { role: userRole } = storeToRefs(auth);

const toast = useToast();

const ejercicios = ref([]);
const busqueda = ref('');
const grupoMuscularFiltro = ref('');
const gruposMusculares = ref([]);
const equipamientoFiltro = ref('');
const equipamientos = ref([]);

// === Filtros desplegables con buscador (Grupo y Equipamiento) ===
const grupoSelectOpen = ref(false);
const grupoSearchText = ref('');
const grupoDropdownRef = ref(null);
const grupoSearchInputRef = ref(null);

const equipoSelectOpen = ref(false);
const equipoSearchText = ref('');
const equipoDropdownRef = ref(null);
const equipoSearchInputRef = ref(null);

const toggleGrupoDropdown = () => {
    grupoSelectOpen.value = !grupoSelectOpen.value;
    equipoSelectOpen.value = false;
    if (grupoSelectOpen.value) {
        grupoSearchText.value = '';
        nextTick(() => grupoSearchInputRef.value?.focus());
    }
};

const toggleEquipoDropdown = () => {
    equipoSelectOpen.value = !equipoSelectOpen.value;
    grupoSelectOpen.value = false;
    if (equipoSelectOpen.value) {
        equipoSearchText.value = '';
        nextTick(() => equipoSearchInputRef.value?.focus());
    }
};

const gruposFiltrados = computed(() => {
    if (!grupoSearchText.value.trim()) return gruposMusculares.value;
    const q = grupoSearchText.value.toLowerCase().trim();
    return gruposMusculares.value.filter((g) => g.toLowerCase().includes(q));
});

const equipamientosFiltrados = computed(() => {
    if (!equipoSearchText.value.trim()) return equipamientos.value;
    const q = equipoSearchText.value.toLowerCase().trim();
    return equipamientos.value.filter((e) => e.toLowerCase().includes(q));
});

const seleccionarGrupo = (grupo) => {
    grupoMuscularFiltro.value = grupo;
    grupoSelectOpen.value = false;
    grupoSearchText.value = '';
    fetchEjercicios();
};

const seleccionarEquipamiento = (eq) => {
    equipamientoFiltro.value = eq;
    equipoSelectOpen.value = false;
    equipoSearchText.value = '';
    fetchEjercicios();
};

const handleClickOutsideFiltros = (e) => {
    if (grupoDropdownRef.value && !grupoDropdownRef.value.contains(e.target)) {
        grupoSelectOpen.value = false;
    }
    if (equipoDropdownRef.value && !equipoDropdownRef.value.contains(e.target)) {
        equipoSelectOpen.value = false;
    }
};

const mostrarModal = ref(false);
const modalRef = ref(null);
useFocusTrap(modalRef, { when: mostrarModal });
const ejercicioSeleccionado = ref(null);
const mostrarDetail = ref(false);
const verDetalle = (ej) => {
    ejercicioSeleccionado.value = ej;
    mostrarDetail.value = true;
};
const paginaActual = ref(1);
const totalPages = ref(1);
const total = ref(0);
const nuevo = ref({
  nombre: '',
  equipamiento: '',
  grupo_muscular: '',
  descripcion: '',
});

// === Body map en la lista ===
// - ejercicioAComparar / ejercicioBComparar: hasta 2 ejercicios para el body map.
//   - 0 seleccionados: modo recencia
//   - 1 seleccionado: highlight indigo del ejercicio
//   - 2 seleccionados: comparar (A indigo, B cyan, ambos morado)
// - musculoFiltroBodyMap: filtra la lista cuando el user hace click en el mapa
// - bodyMapExpandido: modal fullscreen al clickear el mini mapa
// - muscleRecency: datos del endpoint /api/body-map/muscle-recency (modo default)
const musculosCatalogo = ref([]);     // [{slug, nombre_es, ...}]
const ejercicioAComparar = ref(null);
const ejercicioBComparar = ref(null);
const musculoFiltroBodyMap = ref(''); // slug
const bodyMapExpandido = ref(false);
const muscleRecency = ref([]);         // [{slug, days_since, ...}]

// Backwards-compat alias (se usa en el template y computed)
const ejercicioSeleccionadoBodyMap = ejercicioAComparar;

// Labels slug → nombre_es para tooltips del body map
const muscleLabels = computed(() => {
    const map = {};
    for (const m of musculosCatalogo.value) map[m.slug] = m.nombre_es;
    return map;
});

// Niveles 0-4 del body map:
//   1) Si hay ejercicio seleccionado: highlight de músculos del ejercicio.
//      Primario = 4, Secundario = 2.
//   2) Si no hay: modo "no entrenaste" basado en recencia.
//      0-3d = 0, 4-7d = 1, 8-14d = 2, 15-30d = 3, 30+d = 4.
const recencyLevels = computed(() => {
    const levels = {};
    for (const r of muscleRecency.value) {
        const d = r.days_since;
        if (d <= 3) levels[r.slug] = 0;
        else if (d <= 7) levels[r.slug] = 1;
        else if (d <= 14) levels[r.slug] = 2;
        else if (d <= 30) levels[r.slug] = 3;
        else levels[r.slug] = 4;
    }
    return levels;
});

const bodyMapLevels = computed(() => {
    const ej = ejercicioAComparar.value;
    if (ej?.musculos) {
        const levels = {};
        for (const m of ej.musculos) {
            levels[m.slug] = m.pivot?.tipo === 'primario' ? 4
                           : m.pivot?.tipo === 'secundario' ? 2
                           : 1;
        }
        return levels;
    }
    return recencyLevels.value;
});

// Levels del segundo ejercicio (modo comparar). null si no hay B seleccionado.
const bodyMapComparisonLevels = computed(() => {
    const ej = ejercicioBComparar.value;
    if (!ej?.musculos) return null;
    const levels = {};
    for (const m of ej.musculos) {
        levels[m.slug] = m.pivot?.tipo === 'primario' ? 4
                       : m.pivot?.tipo === 'secundario' ? 2
                       : 1;
    }
    return levels;
});

const modoComparar = computed(() => !!ejercicioAComparar.value && !!ejercicioBComparar.value);

// Etiqueta del modo actual (visible en el mini header)
const bodyMapModoLabel = computed(() => {
    if (ejercicioAComparar.value && ejercicioBComparar.value) {
        return `Comparando: ${ejercicioAComparar.value.nombre} ↔ ${ejercicioBComparar.value.nombre}`;
    }
    if (ejercicioAComparar.value) {
        return ejercicioAComparar.value.nombre;
    }
    if (musculoFiltroBodyMap.value) {
        return `Filtrando por: ${muscleLabels.value[musculoFiltroBodyMap.value] || musculoFiltroBodyMap.value}`;
    }
    return 'Rojo = sin entrenar hace mucho';
});

const fetchUserInfo = async () => {
  try {
    await auth.fetchUser();
  } catch (error) {
    console.error('Error al obtener rol:', error);
  }
};

const fetchGruposMusculares = async () => {
  try {
    const response = await cachedAxiosGet('/api/ejercicios/grupos-musculares', {}, { ttl: 5 * 60_000 });
    gruposMusculares.value = response.data;
  } catch (error) {
    console.error('Error al obtener grupos musculares:', error);
  }
};

const fetchEquipamientos = async () => {
  try {
    const response = await cachedAxiosGet('/api/ejercicios/equipamientos', {}, { ttl: 5 * 60_000 });
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
    if (musculoFiltroBodyMap.value) params.musculo_slug = musculoFiltroBodyMap.value;
    const response = await axios.get('/api/ejercicios', { params });
    ejercicios.value = response.data.data || response.data;
    paginaActual.value = response.data.current_page || 1;
    totalPages.value = response.data.last_page || 1;
    total.value = response.data.total || ejercicios.value.length;
  } catch (error) {
    console.error('Error:', error);
  }
};

const fetchMusculos = async () => {
  try {
    // Catálogo estático: cache 1h.
    const response = await cachedAxiosGet('/api/musculos', {}, { ttl: 60 * 60_000 });
    musculosCatalogo.value = response.data || [];
  } catch (error) {
    console.error('Error al obtener músculos:', error);
  }
};

// Carga el "days_since" de cada músculo desde el body map del user.
// Se usa en el body map por defecto (cuando no hay ejercicio seleccionado).
const fetchMuscleRecency = async () => {
  try {
    // Cache 5min (mismo TTL que el body map original).
    const response = await cachedAxiosGet('/api/body-map/muscle-recency', {}, { ttl: 5 * 60_000 });
    muscleRecency.value = response.data?.recency || [];
  } catch (error) {
    console.error('Error al obtener recencia de músculos:', error);
  }
};

// Click en una fila/card de la lista: ilumina el body map
// Click en una fila/card de la lista: ilumina el body map.
// Flujo de selección para comparar:
//   1ra vez: setea A
//   2da vez (distinto): setea B (modo comparar)
//   3ra vez: rota (B pasa a A, nuevo ejercicio pasa a B)
//   Click en A o B ya seleccionado: deselecciona ese
const seleccionarEjercicioBodyMap = (ej) => {
    const a = ejercicioAComparar.value;
    const b = ejercicioBComparar.value;
    if (a && a.id === ej.id) {
        ejercicioAComparar.value = null;
        ejercicioBComparar.value = null;
    } else if (b && b.id === ej.id) {
        ejercicioBComparar.value = null;
    } else if (!a) {
        ejercicioAComparar.value = ej;
    } else if (!b) {
        ejercicioBComparar.value = ej;
    } else {
        // Ya hay A y B, rota
        ejercicioAComparar.value = b;
        ejercicioBComparar.value = ej;
    }
    if (musculoFiltroBodyMap.value) musculoFiltroBodyMap.value = '';
};

// Click en un músculo del body map: filtra la lista
const onMuscleClickBodyMap = (slug) => {
    musculoFiltroBodyMap.value = slug;
    ejercicioAComparar.value = null;
    ejercicioBComparar.value = null;
    fetchEjercicios(1);
};

// Limpiar la selección de ejercicios (vuelve al modo recencia)
const limpiarSeleccion = () => {
    ejercicioAComparar.value = null;
    ejercicioBComparar.value = null;
};

// === Última vez / Favoritos ===

/**
 * Devuelve texto relativo ("hace 3 días", "hace 2 semanas", "nunca").
 * Devuelve también un color para el badge según la antigüedad.
 */
function relativeTime(fechaStr) {
    if (!fechaStr) return { text: 'Nunca', color: 'gray' };
    const fecha = new Date(fechaStr);
    const ahora = new Date();
    const diffMs = ahora - fecha;
    const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDias === 0) return { text: 'Hoy', color: 'green' };
    if (diffDias === 1) return { text: 'Ayer', color: 'green' };
    if (diffDias < 7) return { text: `Hace ${diffDias} días`, color: 'green' };
    if (diffDias < 14) return { text: 'Hace 1 sem', color: 'yellow' };
    if (diffDias < 30) return { text: `Hace ${Math.floor(diffDias / 7)} sem`, color: 'yellow' };
    if (diffDias < 90) return { text: `Hace ${Math.floor(diffDias / 30)} mes`, color: 'orange' };
    return { text: `Hace ${Math.floor(diffDias / 30)} meses`, color: 'red' };
}

const relativeTimeBadge = (color) => {
    // Mapeo de color → clases Tailwind. El "color" viene de relativeTime.
    const map = {
        gray:   'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        green:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
        red:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    };
    return map[color] || map.gray;
};

/**
 * Toggle de favorito. Actualiza el item en la lista localmente (optimista)
 * y rollback si falla.
 */
const toggleFavorito = async (ej, ev) => {
    // Evitar que el click propague al row (que selecciona el ejercicio)
    if (ev) ev.stopPropagation();
    const prev = ej.is_favorite;
    ej.is_favorite = !prev;
    try {
        await axios.post(`/api/ejercicios/${ej.id}/favorite`);
    } catch (err) {
        console.error('Error al togglear favorito:', err);
        ej.is_favorite = prev;  // rollback
        toast.error('No se pudo actualizar el favorito');
    }
};

/**
 * Quick log: marca el ejercicio como hecho HOY (crea un historial placeholder
 * con 1 set, peso=0, reps=0). El user despues va al dashboard a completar
 * los sets reales.
 */
const quickLog = async (ej, ev) => {
    if (ev) ev.stopPropagation();
    try {
        const resp = await axios.post(`/api/ejercicios/${ej.id}/quick-log`);
        ej.last_trained_at = new Date().toISOString();
        toast.success(`✓ ${ej.nombre} marcado como hecho hoy`);
    } catch (err) {
        console.error('Error en quick log:', err);
        toast.error('No se pudo registrar el ejercicio');
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
  musculoFiltroBodyMap.value = '';
  grupoSearchText.value = '';
  equipoSearchText.value = '';
  grupoSelectOpen.value = false;
  equipoSelectOpen.value = false;
  ejercicioAComparar.value = null;
  ejercicioBComparar.value = null;
  fetchEjercicios();
};

// Toggle de un chip de grupo muscular (selecciona o deselecciona)
const toggleGrupoMuscular = (grupo) => {
  grupoMuscularFiltro.value = grupoMuscularFiltro.value === grupo ? '' : grupo;
  fetchEjercicios();
};

const toggleEquipamiento = (eq) => {
  equipamientoFiltro.value = equipamientoFiltro.value === eq ? '' : eq;
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
  // 1) Confirmación visual moderna
  const confirmed = await toast.confirm(
    '¿Eliminar este ejercicio?',
    { title: 'Eliminar ejercicio', confirmLabel: 'Sí, eliminar', type: 'error' }
  );
  if (!confirmed) return;

  // 2) Snapshot + posición original para restaurar en el mismo lugar
  const idx = ejercicios.value.findIndex(e => e.id === id);
  const snapshot = idx >= 0 ? { ...ejercicios.value[idx] } : null;

  // 3) Undo real: optimistic + commit diferido + cancel
  await useUndoable({
    message: 'Ejercicio eliminado',
    apply: () => {
      ejercicios.value = ejercicios.value.filter(e => e.id !== id);
    },
    undo: () => {
      if (!snapshot) return;
      if (idx >= 0 && idx <= ejercicios.value.length) {
        ejercicios.value.splice(idx, 0, snapshot);
      } else {
        ejercicios.value.push(snapshot);
      }
    },
    commit: () => axios.delete(`/api/ejercicios/${id}`),
    onError: (err) => console.error('Error al eliminar:', err),
  });
};

onMounted(() => {
  document.addEventListener('click', handleClickOutsideFiltros);
  fetchUserInfo();
  fetchGruposMusculares();
  fetchEquipamientos();
  fetchMusculos();
  fetchMuscleRecency();
  fetchEjercicios();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutsideFiltros);
});
</script>