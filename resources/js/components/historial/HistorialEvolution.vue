<template>
    <div class="space-y-6">
        <!-- BEGIN: ProgressAndPRTableSection -->
        <section
            class="bg-[#131924] border border-[#232D42] rounded-xl overflow-hidden shadow-lg"
            data-purpose="progress-table-section"
        >
            <!-- Table Header & Quick Search -->
            <div
                class="p-4 bg-[#18202F]/60 border-b border-[#232D42] flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="w-2.5 h-2.5 rounded bg-indigo-500"></div>
                    <h2 class="text-sm font-bold text-white tracking-wide uppercase">
                        Resumen de Progreso y Pesos Máximos
                    </h2>
                    <span class="text-xs bg-[#232D42] text-slate-300 px-2 py-0.5 rounded-full font-mono">
                        {{ filteredRows.length }} {{ filteredRows.length === 1 ? 'registro' : 'ejercicios' }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <input
                            v-model="tableSearch"
                            type="text"
                            placeholder="Buscar en tabla..."
                            class="w-full bg-[#101521] border border-[#232D42] text-xs text-white rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                        />
                        <svg
                            class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                    </div>
                    <button
                        type="button"
                        @click="$emit('export-view')"
                        class="text-xs text-indigo-400 hover:text-indigo-300 font-medium whitespace-nowrap hidden sm:inline cursor-pointer"
                    >
                        Exportar vista
                    </button>
                </div>
            </div>

            <!-- Mobile Cards View (md:hidden) -->
            <div class="md:hidden divide-y divide-[#232D42]/60">
                <div
                    v-for="(fila, idx) in paginatedRows"
                    :key="`mob-${fila.fecha}-${fila.nombre}-${idx}`"
                    class="p-3.5 hover:bg-[#18202F]/60 transition-colors flex flex-col gap-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-white text-xs flex items-center gap-1.5 flex-wrap">
                                <span>{{ fila.nombre }}</span>
                                <span
                                    v-if="fila.superserie_grupo"
                                    class="text-[9px] uppercase font-mono px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/40"
                                >
                                    SS {{ fila.superserie_grupo }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                {{ fila.dia }} · {{ fila.fecha }} · {{ fila.seriesCount }} series
                            </div>
                        </div>

                        <span
                            v-if="fila.isPR"
                            class="inline-flex items-center gap-1 text-amber-400 font-mono text-xs font-bold bg-amber-400/10 border border-amber-400/30 px-2 py-0.5 rounded shrink-0"
                        >
                            ★ {{ fila.maxWeight }} kg
                        </span>
                        <span v-else class="font-mono text-xs font-bold text-white shrink-0">
                            {{ fila.maxWeight }} kg
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] font-mono bg-[#101521]/80 px-2.5 py-1.5 rounded-lg border border-[#232D42]/60">
                        <span class="text-slate-400">
                            Prom: <strong class="text-slate-200">{{ fila.avgWeight }} kg</strong>
                        </span>
                        <span
                            v-if="(fila.trendDelta ?? 0) > 0"
                            class="text-emerald-400 font-bold"
                        >
                            +{{ Number(fila.trendDelta).toFixed(1) }} kg ↑
                        </span>
                        <span
                            v-else-if="(fila.trendDelta ?? 0) < 0"
                            class="text-rose-400 font-bold"
                        >
                            {{ Number(fila.trendDelta).toFixed(1) }} kg ↓
                        </span>
                        <span v-else class="text-slate-400">
                            0.0 kg =
                        </span>
                    </div>
                </div>
                <div v-if="paginatedRows.length === 0" class="py-8 text-center text-xs text-slate-400">
                    No se encontraron registros para "{{ tableSearch }}".
                </div>
            </div>

            <!-- Desktop Data Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr
                            class="border-b border-[#232D42] bg-[#101521]/70 text-[11px] font-semibold text-slate-400 uppercase tracking-wider select-none"
                        >
                            <th
                                scope="col"
                                class="py-3 px-4 cursor-pointer hover:text-white transition-colors"
                                @click="toggleSort('nombre')"
                            >
                                Ejercicio
                            </th>
                            <th
                                scope="col"
                                class="py-3 px-4 cursor-pointer hover:text-white transition-colors"
                                @click="toggleSort('rawFecha')"
                            >
                                Última Fecha
                            </th>
                            <th
                                scope="col"
                                class="py-3 px-4 text-center cursor-pointer hover:text-white transition-colors"
                                @click="toggleSort('seriesCount')"
                            >
                                Series
                            </th>
                            <th
                                scope="col"
                                class="py-3 px-4 text-right cursor-pointer hover:text-white transition-colors"
                                @click="toggleSort('avgWeight')"
                            >
                                Peso Promedio
                            </th>
                            <th
                                scope="col"
                                class="py-3 px-4 text-right cursor-pointer hover:text-white transition-colors"
                                @click="toggleSort('maxWeight')"
                            >
                                Peso Máximo / PR
                            </th>
                            <th scope="col" class="py-3 px-4 text-center">Tendencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#232D42]/60 font-medium">
                        <tr
                            v-for="(fila, idx) in paginatedRows"
                            :key="`${fila.fecha}-${fila.nombre}-${idx}`"
                            class="hover:bg-[#18202F]/60 transition-colors"
                        >
                            <td class="py-3 px-4">
                                <div class="font-bold text-white flex items-center gap-2 flex-wrap">
                                    <span>{{ fila.nombre }}</span>
                                    <span
                                        v-if="fila.superserie_grupo"
                                        :class="[
                                            'text-[9px] uppercase font-mono px-1.5 py-0.5 rounded border',
                                            Number(fila.superserie_grupo) % 2 === 0
                                                ? 'bg-violet-500/20 text-violet-300 border-violet-500/40'
                                                : 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
                                        ]"
                                    >
                                        Superserie {{ fila.superserie_grupo }}
                                    </span>
                                    <span
                                        v-else-if="fila.isPR"
                                        class="w-2 h-2 rounded-full bg-indigo-400"
                                        title="Ejercicio Principal"
                                    ></span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-normal mt-0.5">
                                    {{ fila.dia }}
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-300 whitespace-nowrap">
                                {{ fila.fecha }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-slate-300 whitespace-nowrap">
                                {{ fila.seriesCount }} series
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-200 whitespace-nowrap">
                                {{ fila.avgWeight }} kg
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <span
                                    v-if="fila.isPR && idx % 2 === 0"
                                    class="inline-flex items-center gap-1 text-amber-400 font-mono font-bold bg-amber-400/10 border border-amber-400/30 px-2 py-0.5 rounded"
                                >
                                    <svg class="w-3 h-3 fill-amber-400" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        ></path>
                                    </svg>
                                    {{ fila.maxWeight }} kg
                                </span>
                                <span
                                    v-else-if="fila.isPR"
                                    class="inline-flex items-center gap-1 text-violet-400 font-mono font-bold bg-violet-500/10 border border-violet-500/30 px-2 py-0.5 rounded"
                                >
                                    ★ {{ fila.maxWeight }} kg
                                </span>
                                <span v-else class="font-mono font-bold text-white">
                                    {{ fila.maxWeight }} kg
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span
                                    v-if="(fila.trendDelta ?? 0) > 0"
                                    class="text-emerald-400 font-mono text-[11px] font-bold inline-flex items-center"
                                >
                                    +{{ Number(fila.trendDelta).toFixed(1) }} kg ↑
                                </span>
                                <span
                                    v-else-if="(fila.trendDelta ?? 0) < 0"
                                    class="text-rose-400 font-mono text-[11px] font-bold inline-flex items-center"
                                >
                                    {{ Number(fila.trendDelta).toFixed(1) }} kg ↓
                                </span>
                                <span v-else class="text-slate-400 font-mono text-[11px]">
                                    0.0 kg =
                                </span>
                            </td>
                        </tr>
                        <tr v-if="paginatedRows.length === 0">
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No se encontraron registros para "{{ tableSearch }}".
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Summary -->
            <div
                class="px-4 py-3 bg-[#101521] border-t border-[#232D42] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400"
            >
                <span>
                    Mostrando {{ paginatedRows.length }} de {{ filteredRows.length }} ejercicios activos
                </span>
                <div v-if="totalPages > 1" class="flex items-center gap-1.5 font-mono">
                    <button
                        type="button"
                        :disabled="currentPage <= 1"
                        @click="currentPage = Math.max(1, currentPage - 1)"
                        class="px-2 py-1 bg-[#18202F] rounded text-slate-400 hover:text-white border border-[#232D42] disabled:opacity-40 cursor-pointer"
                    >
                        Anterior
                    </button>
                    <button
                        v-for="p in visiblePages"
                        :key="p"
                        type="button"
                        @click="currentPage = p"
                        :class="[
                            'px-2 py-1 rounded border cursor-pointer',
                            currentPage === p
                                ? 'bg-indigo-600/30 text-indigo-300 border-indigo-500/40 font-bold'
                                : 'bg-[#18202F] text-slate-300 hover:text-white border-[#232D42]',
                        ]"
                    >
                        {{ p }}
                    </button>
                    <button
                        type="button"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        class="px-2 py-1 bg-[#18202F] rounded text-slate-400 hover:text-white border border-[#232D42] disabled:opacity-40 cursor-pointer"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </section>
        <!-- END: ProgressAndPRTableSection -->

        <!-- BEGIN: DetailedExerciseProgressionCards -->
        <section class="space-y-4 pt-2" data-purpose="exercise-progression-cards">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-indigo-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                            ></path>
                        </svg>
                        Análisis de Progresión y Curvas de Carga
                    </h2>
                    <p class="text-xs text-slate-400">
                        Evolución longitudinal del peso medio por sesión y desglose de series registradas.
                    </p>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Ventana de telemetría:
                    <span class="text-indigo-400">{{ telemetryWindow }}</span>
                </div>
            </div>

            <!-- Progression Cards Stack -->
            <div class="space-y-4">
                <article
                    v-for="(ejercicio, cardIdx) in resumenEjercicios"
                    :key="ejercicio.nombre"
                    class="bg-[#131924] border border-[#232D42] rounded-xl p-5 shadow-lg relative overflow-hidden"
                >
                    <!-- Card Header & Badges -->
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-[#232D42] gap-3"
                    >
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h3 class="text-lg font-extrabold text-white">
                                    {{ ejercicio.nombre }}
                                </h3>
                                <span
                                    v-if="ejercicio.superserie_grupo"
                                    class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-violet-500/15 text-violet-300 border border-violet-500/30"
                                >
                                    Superserie {{ ejercicio.superserie_grupo }}
                                </span>
                                <span
                                    v-else-if="ejercicio.diasBadge"
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-mono font-semibold border',
                                        cardIdx % 3 === 1
                                            ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                                            : 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
                                    ]"
                                >
                                    {{ ejercicio.diasBadge }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ ejercicio.totalSeries }} series registradas ·
                                {{ ejercicio.totalSesiones }}
                                {{ ejercicio.totalSesiones === 1 ? 'sesión completada' : 'sesiones completadas' }}
                            </p>
                        </div>

                        <!-- Stats badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="px-2.5 py-1 rounded-lg bg-indigo-600/15 border border-indigo-500/30 text-indigo-300 font-mono text-xs font-semibold"
                            >
                                Peso promedio:
                                <strong class="text-white">{{ ejercicio.pesoPromedio }} kg</strong>
                            </span>
                            <span
                                class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 font-mono text-xs font-semibold"
                            >
                                Reps promedio:
                                <strong class="text-white">{{ ejercicio.repsPromedio }}</strong>
                            </span>
                            <span
                                class="px-2.5 py-1 rounded-lg bg-violet-600/15 border border-violet-500/30 text-violet-300 font-mono text-xs font-semibold"
                            >
                                Último peso:
                                <strong class="text-white">{{ ejercicio.ultimoPeso }} kg</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Body: Chart Left + Timeline Right -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 pt-4">
                        <!-- Left Chart (8 cols) -->
                        <div class="lg:col-span-8 flex flex-col justify-between">
                            <div class="flex items-center justify-between mb-2 gap-2 flex-wrap">
                                <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Evolución del peso promedio por fecha
                                </span>
                                <span
                                    v-if="ejercicio.overloadPct"
                                    class="text-[11px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20"
                                >
                                    +{{ ejercicio.overloadPct }}% sobrecarga · PR: {{ ejercicio.pesoMaximo }} kg
                                </span>
                                <span
                                    v-else
                                    class="text-[11px] font-mono text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20"
                                >
                                    PR Actual: {{ ejercicio.pesoMaximo }} kg
                                </span>
                            </div>

                            <!-- SVG Progression Chart -->
                            <div
                                class="h-44 w-full relative bg-[#0B0F17]/70 rounded-lg p-2 border border-[#232D42]/80 flex items-center justify-center"
                            >
                                <!-- Interactive Selected Point Pill -->
                                <div
                                    v-if="selectedPoints[ejercicio.slug]"
                                    class="absolute top-2 left-2 z-10 px-2.5 py-1 rounded-lg bg-[#131924]/95 border border-indigo-500/40 text-[11px] font-mono text-slate-200 shadow-lg flex items-center gap-2"
                                >
                                    <span class="text-indigo-400 font-bold">{{ selectedPoints[ejercicio.slug].fechaLabel }}</span>
                                    <span class="text-white font-extrabold">{{ selectedPoints[ejercicio.slug].label }}</span>
                                    <span v-if="selectedPoints[ejercicio.slug].isMax" class="text-amber-400 font-bold">★ PR</span>
                                </div>
                                <svg
                                    class="w-full h-full overflow-visible"
                                    preserveAspectRatio="none"
                                    viewBox="0 0 500 140"
                                >
                                    <defs>
                                        <linearGradient
                                            :id="`grad-${ejercicio.slug}-${cardIdx}`"
                                            x1="0%"
                                            y1="0%"
                                            x2="0%"
                                            y2="100%"
                                        >
                                            <stop
                                                offset="0%"
                                                :stop-color="themeFor(cardIdx).stopColor"
                                                stop-opacity="0.38"
                                            ></stop>
                                            <stop
                                                offset="100%"
                                                :stop-color="themeFor(cardIdx).stopColor"
                                                stop-opacity="0.0"
                                            ></stop>
                                        </linearGradient>
                                    </defs>

                                    <!-- Horizontal Grid lines -->
                                    <line
                                        stroke="#232D42"
                                        stroke-dasharray="3 3"
                                        x1="0"
                                        x2="500"
                                        y1="25"
                                        y2="25"
                                    ></line>
                                    <line
                                        stroke="#232D42"
                                        stroke-dasharray="3 3"
                                        x1="0"
                                        x2="500"
                                        y1="65"
                                        y2="65"
                                    ></line>
                                    <line
                                        stroke="#232D42"
                                        stroke-dasharray="3 3"
                                        x1="0"
                                        x2="500"
                                        y1="105"
                                        y2="105"
                                    ></line>

                                    <!-- Filled Area -->
                                    <polygon
                                        v-if="buildChartGeometry(ejercicio).polygon"
                                        :fill="`url(#grad-${ejercicio.slug}-${cardIdx})`"
                                        :points="buildChartGeometry(ejercicio).polygon"
                                    ></polygon>

                                    <!-- Main Smooth Curve -->
                                    <path
                                        v-if="buildChartGeometry(ejercicio).path"
                                        :d="buildChartGeometry(ejercicio).path"
                                        fill="none"
                                        :stroke="themeFor(cardIdx).stroke"
                                        stroke-linecap="round"
                                        stroke-width="3"
                                    ></path>

                                    <!-- Data Points with Weight Labels -->
                                    <g
                                        v-for="(pt, ptIdx) in buildChartGeometry(ejercicio).points"
                                        :key="`${ejercicio.slug}-pt-${ptIdx}`"
                                        class="cursor-pointer"
                                        @click="selectChartPoint(ejercicio.slug, pt)"
                                    >
                                        <circle
                                            :cx="pt.x"
                                            :cy="pt.y"
                                            r="14"
                                            fill="transparent"
                                        ></circle>
                                        <circle
                                            :cx="pt.x"
                                            :cy="pt.y"
                                            :r="selectedPoints[ejercicio.slug]?.fechaLabel === pt.fechaLabel ? 7 : pt.isMax ? 6 : 4.5"
                                            :fill="pt.isMax ? themeFor(cardIdx).prFill : '#131924'"
                                            :stroke="selectedPoints[ejercicio.slug]?.fechaLabel === pt.fechaLabel || pt.isMax ? '#FFFFFF' : themeFor(cardIdx).stroke"
                                            :stroke-width="pt.isMax ? 2 : 2.5"
                                        ></circle>
                                        <text
                                            :x="pt.x"
                                            :y="pt.y - 10"
                                            text-anchor="middle"
                                            font-family="monospace"
                                            :font-size="pt.isMax ? 10 : 9"
                                            :font-weight="pt.isMax ? 'bold' : 'normal'"
                                            :fill="pt.isMax ? themeFor(cardIdx).prText : '#94A3B8'"
                                        >
                                            {{ pt.label }}{{ pt.isMax ? ' ★' : '' }}
                                        </text>
                                    </g>
                                </svg>
                            </div>

                            <!-- Chart X Axis Labels -->
                            <div
                                class="flex justify-between text-[11px] font-mono text-slate-400 mt-2 px-4 overflow-x-auto gap-2"
                            >
                                <span
                                    v-for="(pt, ptIdx) in buildChartGeometry(ejercicio).axisLabels"
                                    :key="`${ejercicio.slug}-lbl-${ptIdx}`"
                                    :class="
                                        ptIdx === buildChartGeometry(ejercicio).axisLabels.length - 1
                                            ? `${themeFor(cardIdx).accentText} font-bold`
                                            : ''
                                    "
                                >
                                    {{ pt.fechaLabel
                                    }}{{
                                        ptIdx === buildChartGeometry(ejercicio).axisLabels.length - 1
                                            ? ' (Último)'
                                            : ''
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Right: Details Timeline (4 cols) -->
                        <div
                            class="lg:col-span-4 bg-[#18202F]/60 rounded-xl p-3.5 border border-[#232D42] flex flex-col justify-between"
                        >
                            <div>
                                <span
                                    class="text-xs font-bold text-slate-300 uppercase tracking-wider block mb-2.5"
                                >
                                    Detalles del progreso
                                </span>

                                <!-- Min / Max pill -->
                                <div
                                    class="grid grid-cols-2 gap-2 bg-[#101521] p-2 rounded-lg border border-[#232D42] mb-3 text-center"
                                >
                                    <div>
                                        <div class="text-[10px] text-slate-400 uppercase">
                                            Min Registrado
                                        </div>
                                        <div class="font-mono text-xs font-bold text-slate-200">
                                            {{ ejercicio.pesoMinimo }} kg
                                        </div>
                                    </div>
                                    <div class="border-l border-[#232D42]">
                                        <div
                                            class="text-[10px] uppercase"
                                            :class="themeFor(cardIdx).accentText"
                                        >
                                            Max Histórico
                                        </div>
                                        <div
                                            class="font-mono text-xs font-bold"
                                            :class="themeFor(cardIdx).accentText"
                                        >
                                            {{ ejercicio.pesoMaximo }} kg
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Sessions Timeline list -->
                                <div class="space-y-2">
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase">
                                        Sesiones registradas
                                    </div>
                                    <div
                                        v-for="(sesion, sIdx) in visibleSessions(ejercicio)"
                                        :key="`${ejercicio.slug}-ses-${sIdx}`"
                                        class="flex items-center justify-between text-xs bg-[#101521]/70 p-1.5 px-2.5 rounded border border-[#232D42]/60"
                                    >
                                        <span class="font-mono text-slate-300">
                                            {{ sesion.fecha }}
                                        </span>
                                        <span
                                            class="font-mono font-bold"
                                            :class="
                                                sIdx === 0 || sesion.isPR
                                                    ? themeFor(cardIdx).accentText
                                                    : 'text-slate-200'
                                            "
                                        >
                                            {{ sesion.pesoMaximo || sesion.pesoPromedio }} kg
                                        </span>
                                        <span
                                            v-if="sesion.rir !== null && sesion.rir !== undefined"
                                            class="text-[10px] text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded font-mono"
                                        >
                                            RIR {{ sesion.rir }}
                                        </span>
                                        <span
                                            v-else-if="sesion.isPR"
                                            class="text-[10px] text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded font-mono"
                                        >
                                            PR Set
                                        </span>
                                        <span
                                            v-else
                                            class="text-[10px] text-slate-400 bg-slate-500/10 px-1.5 py-0.5 rounded font-mono"
                                        >
                                            {{ Math.round(Number(sesion.repsPromedio || 0)) }} reps
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button
                                v-if="(ejercicio.sesiones || []).length > 3"
                                type="button"
                                @click="toggleExpandSessions(ejercicio.slug)"
                                :class="[
                                    'w-full mt-3 py-1.5 text-center text-xs font-semibold rounded-lg border transition-colors cursor-pointer',
                                    themeFor(cardIdx).buttonClass,
                                ]"
                            >
                                {{
                                    expandedSlugs.has(ejercicio.slug)
                                        ? 'Mostrar menos sesiones ↑'
                                        : `Ver historial de series completo (${ejercicio.sesiones.length}) →`
                                }}
                            </button>
                            <div
                                v-else
                                :class="[
                                    'w-full mt-3 py-1.5 text-center text-xs font-semibold rounded-lg border',
                                    themeFor(cardIdx).buttonClass,
                                ]"
                            >
                                Historial de series completo ({{ (ejercicio.sesiones || []).length }})
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
        <!-- END: DetailedExerciseProgressionCards -->
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    tablaProgreso: { type: Array, required: true },
    resumenEjercicios: { type: Array, required: true },
    globalMaxWeight: { type: Number, required: true },
});

defineEmits(['export-view']);

// Table search, sort & pagination state
const tableSearch = ref('');
const sortKey = ref('rawFecha');
const sortAsc = ref(false);
const currentPage = ref(1);
const pageSize = 10;

watch(tableSearch, () => {
    currentPage.value = 1;
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = key === 'nombre';
    }
};

const filteredRows = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();
    let list = [...(props.tablaProgreso || [])];
    if (q) {
        list = list.filter(
            (r) =>
                (r.nombre || '').toLowerCase().includes(q) ||
                (r.dia || '').toLowerCase().includes(q) ||
                (r.fecha || '').toLowerCase().includes(q)
        );
    }
    list.sort((a, b) => {
        let va = a[sortKey.value];
        let vb = b[sortKey.value];
        if (sortKey.value === 'maxWeight' || sortKey.value === 'avgWeight' || sortKey.value === 'seriesCount') {
            va = Number(va) || 0;
            vb = Number(vb) || 0;
        } else if (sortKey.value === 'rawFecha') {
            va = va ? new Date(va).getTime() : 0;
            vb = vb ? new Date(vb).getTime() : 0;
        } else {
            va = String(va || '').toLowerCase();
            vb = String(vb || '').toLowerCase();
        }
        if (va < vb) return sortAsc.value ? -1 : 1;
        if (va > vb) return sortAsc.value ? 1 : -1;
        return 0;
    });
    return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / pageSize)));

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredRows.value.slice(start, start + pageSize);
});

const visiblePages = computed(() => {
    const pages = [];
    for (let i = 1; i <= Math.min(totalPages.value, 5); i++) {
        pages.push(i);
    }
    return pages;
});

const telemetryWindow = computed(() => {
    if (!props.resumenEjercicios?.length) return 'Ciclo actual';
    const first = props.resumenEjercicios[0]?.fechaInicial;
    const last = props.resumenEjercicios[0]?.fechaFinal;
    if (first && last && first !== '-') {
        return `${first} – ${last}`;
    }
    return 'Ciclo actual';
});

// Expandable session lists per exercise card
const expandedSlugs = ref(new Set());
const selectedPoints = ref({});

const selectChartPoint = (slug, pt) => {
    if (selectedPoints.value[slug]?.fechaLabel === pt.fechaLabel) {
        const next = { ...selectedPoints.value };
        delete next[slug];
        selectedPoints.value = next;
    } else {
        selectedPoints.value = { ...selectedPoints.value, [slug]: pt };
    }
};

const toggleExpandSessions = (slug) => {
    const next = new Set(expandedSlugs.value);
    if (next.has(slug)) {
        next.delete(slug);
    } else {
        next.add(slug);
    }
    expandedSlugs.value = next;
};

const visibleSessions = (ejercicio) => {
    const list = ejercicio.sesiones || [];
    if (expandedSlugs.value.has(ejercicio.slug)) {
        return list;
    }
    return list.slice(0, 3);
};

// Card visual themes matching Stitch (Indigo/Amber, Emerald, Violet)
const themes = [
    {
        stopColor: '#6366F1',
        stroke: '#818CF8',
        prFill: '#F59E0B',
        prText: '#FCD34D',
        accentText: 'text-amber-400',
        buttonClass:
            'text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border-indigo-500/20',
    },
    {
        stopColor: '#10B981',
        stroke: '#34D399',
        prFill: '#10B981',
        prText: '#6EE7B7',
        accentText: 'text-emerald-400',
        buttonClass:
            'text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border-emerald-500/20',
    },
    {
        stopColor: '#8B5CF6',
        stroke: '#A78BFA',
        prFill: '#8B5CF6',
        prText: '#DDD6FE',
        accentText: 'text-violet-400',
        buttonClass:
            'text-violet-400 hover:text-violet-300 bg-violet-500/10 hover:bg-violet-500/20 border-violet-500/20',
    },
];

const themeFor = (idx) => themes[idx % themes.length];

// Build SVG coordinates (500 x 140 viewBox) for each exercise card
const buildChartGeometry = (ejercicio) => {
    const timeline = ejercicio.timeline || [];
    if (!timeline.length) {
        return { points: [], path: '', polygon: '', axisLabels: [] };
    }

    const padX = 45;
    const usableW = 500 - padX * 2;
    const topY = 25;
    const bottomY = 112;
    const usableH = bottomY - topY;

    const values = timeline.map((t) => Number(t.maxPeso || t.avgPeso || 0));
    const maxVal = Math.max(...values, 1);
    const minVal = Math.min(...values, 0);
    const span = maxVal - minVal > 0 ? maxVal - minVal : maxVal || 1;

    const points = timeline.map((t, i) => {
        const val = Number(t.maxPeso || t.avgPeso || 0);
        const x =
            timeline.length === 1
                ? 250
                : Math.round(padX + (i / (timeline.length - 1)) * usableW);
        const norm = maxVal === minVal ? 0.65 : (val - minVal) / span;
        const y = Math.round(bottomY - norm * usableH);
        return {
            x,
            y,
            val,
            label: `${val.toFixed(1)}kg`,
            fechaLabel: t.fechaLabel,
            isMax: val === maxVal && maxVal > 0 && i === values.lastIndexOf(maxVal),
        };
    });

    let path = '';
    if (points.length === 1) {
        path = `M ${padX},${points[0].y} L ${500 - padX},${points[0].y}`;
    } else {
        path = points.reduce((acc, pt, i) => {
            if (i === 0) return `M ${pt.x},${pt.y}`;
            const prev = points[i - 1];
            const cx = Math.round((prev.x + pt.x) / 2);
            return `${acc} Q ${cx},${prev.y} ${pt.x},${pt.y}`;
        }, '');
    }

    const polygon =
        points.length === 1
            ? `${padX},115 ${padX},${points[0].y} ${500 - padX},${points[0].y} ${500 - padX},115`
            : `${points[0].x},115 ${points.map((p) => `${p.x},${p.y}`).join(' ')} ${points[points.length - 1].x},115`;

    const axisLabels =
        points.length <= 5
            ? points
            : [
                  points[0],
                  points[Math.floor(points.length / 3)],
                  points[Math.floor((points.length * 2) / 3)],
                  points[points.length - 1],
              ];

    return { points, path, polygon, axisLabels };
};
</script>
