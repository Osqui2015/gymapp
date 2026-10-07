<!--
    SetConfigCard.vue

    Panel de configuración y registro de UNA serie para un ejercicio concreto.
    Se usa desde ActiveWorkoutModal tanto para ejercicios simples (1 panel) como
    para superseries (2 paneles side-by-side en desktop / stacked en mobile).

    Props:
      - ejercicio: objeto del ejercicio (nombre, series_objetivo, reps_min/max, sets[])
      - ejercicioIndex: índice en session.ejercicios (para que el padre registre contra el store)
      - label: pill superior ('CONFIGURAR SERIE #1', 'CALENTAMIENTO #1', etc.)
      - accentColor: 'violet' | 'emerald' | 'amber' para diferenciar visualmente los paneles
      - hideHeader: oculta el header del card (lo usa el padre cuando ya muestra titulos fuera)

    Emite:
      - complete({ ejercicioIndex, peso, reps, tipo_srie, esfuerzo_tipo, esfuerzo_valor, nota_user })
      - deshacer(ejercicioIndex)
-->
<template>
    <section
        class="obs-card-elevated p-3.5 sm:p-5 space-y-3 sm:space-y-4 shadow-[0_12px_40px_rgba(0,0,0,0.5)] relative"
        :data-testid="`set-config-card-${ejercicioIndex}`"
    >
        <!-- Header: label + selector de tipo de serie -->
        <div class="flex items-center justify-between border-b border-[var(--color-obsidian-border)] pb-2.5 flex-wrap gap-2">
            <span class="flex items-center gap-2 text-xs sm:text-sm font-black uppercase tracking-wider text-gray-300 min-w-0">
                <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="
                        form.tipo_serie === 'calentamiento'
                            ? 'bg-indigo-400'
                            : 'bg-emerald-400'
                    "
                ></span>
                <span class="truncate">
                    <template v-if="form.tipo_serie === 'calentamiento'">
                        Calentamiento #{{ calentamientoNumero }}
                    </template>
                    <template v-else>
                        Serie #{{ serieNumero }} de {{ ejercicio.series_objetivo || serieNumero }}
                    </template>
                </span>
                <span
                    v-if="ejercicio.superserie_grupo"
                    class="obs-pill obs-pill-emerald text-[9px] ml-1"
                >
                    SS {{ ejercicio.superserie_grupo }}
                </span>
            </span>

            <!-- Selector de Tipo de Serie -->
            <div class="inline-flex rounded-xl bg-[var(--color-obsidian-elevated)] p-0.5 sm:p-1 text-[10px] sm:text-xs font-bold border border-[var(--color-obsidian-border)]">
                <button
                    v-for="tipo in tiposSerie"
                    :key="tipo.id"
                    type="button"
                    @click="form.tipo_serie = tipo.id"
                    :class="[
                        'px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg transition-all cursor-pointer whitespace-nowrap',
                        form.tipo_serie === tipo.id
                            ? tipo.activeClass
                            : 'text-gray-400 hover:text-white',
                    ]"
                >
                    {{ tipo.label }}
                </button>
            </div>
        </div>

        <!-- Tracker visual de series del ejercicio con detalle de objetivo por serie -->
        <div
            v-if="Number(ejercicio.series_objetivo) > 0"
            class="flex items-center gap-1 sm:gap-1.5 overflow-x-auto py-0.5"
            data-testid="sets-progress-tracker"
        >
            <div
                v-for="idx in Number(ejercicio.series_objetivo)"
                :key="idx"
                class="flex-1 min-w-[55px] py-1 px-1 rounded-xl text-center border text-[10px] sm:text-[11px] font-black transition-all flex flex-col items-center justify-center gap-0.5 select-none"
                :class="[
                    idx < serieNumero
                        ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300'
                        : idx === serieNumero && form.tipo_serie !== 'calentamiento'
                          ? 'bg-violet-600/30 border-violet-500 text-white shadow-[0_0_12px_rgba(139,92,246,0.25)] ring-1 ring-violet-400'
                          : 'bg-[var(--color-obsidian-surface)] border-[var(--color-obsidian-border)] text-gray-500'
                ]"
            >
                <div class="flex items-center gap-1">
                    <span v-if="idx < serieNumero" class="text-emerald-400">✓</span>
                    <span v-else-if="idx === serieNumero && form.tipo_serie !== 'calentamiento'" class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"></span>
                    <span>Serie {{ idx }}</span>
                </div>
                <span
                    v-if="getTargetForSetIndex(idx)"
                    class="text-[9px] font-black tabular-nums tracking-tight leading-none"
                    :class="[
                        idx < serieNumero
                            ? 'text-emerald-400/80'
                            : idx === serieNumero && form.tipo_serie !== 'calentamiento'
                              ? 'text-violet-200'
                              : 'text-gray-400'
                    ]"
                >
                    {{ getTargetForSetIndex(idx).reps }}r · {{ getTargetForSetIndex(idx).tipo === 'fallo' ? 'FALLO' : 'RIR ' + getTargetForSetIndex(idx).valor }}
                </span>
            </div>
        </div>

        <!-- Nombre del ejercicio (header prominente cuando se muestra; clave
             en superseries para identificar cada card sin mirar la col izq) -->
        <div
            v-if="showExerciseName"
            class="flex items-center justify-between gap-2 px-3 py-1.5 rounded-xl bg-violet-500/10 border border-violet-500/25"
            :data-testid="`set-card-name-${ejercicioIndex}`"
        >
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2 h-2 rounded-full bg-violet-400 shrink-0"></span>
                <span
                    class="text-xs sm:text-sm font-black text-white truncate uppercase tracking-tight"
                    :title="ejercicio.nombre"
                >
                    {{ ejercicio.nombre }}
                </span>
            </div>
            <span class="text-[10px] sm:text-xs font-bold text-emerald-300 shrink-0">
                {{ ejercicio.series_objetivo }}s × {{ repsObjetivoTexto }}
            </span>
        </div>

        <!-- Prescripción o plan del ejercicio (solo si hay notas personalizadas o target de esfuerzo) -->
        <div
            v-if="ejercicio.notas || targetEsfuerzo"
            class="flex items-center justify-between gap-2.5 px-3 py-2 rounded-xl bg-violet-500/10 border border-violet-500/25 text-left"
            data-testid="set-plan-banner"
        >
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-lg bg-violet-600/40 text-violet-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0 leading-tight">
                    <span class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                        {{ ejercicio.notas ? 'PLAN Y NOTAS' : 'OBJETIVO PROGRAMADO' }}
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-white truncate" :title="ejercicio.notas || `${ejercicio.series_objetivo} series de ${repsObjetivoTexto}`">
                        {{ ejercicio.notas || `${ejercicio.series_objetivo} series programadas de ${repsObjetivoTexto}` }}
                    </span>
                </div>
            </div>
            <span
                v-if="targetEsfuerzo"
                class="px-2 py-0.5 rounded-full border border-teal-500/50 bg-teal-500/15 text-teal-300 text-[10px] sm:text-[11px] font-black shrink-0 whitespace-nowrap"
            >
                Serie #{{ serieNumero }}: {{ targetEsfuerzo.bloque?.reps }} reps · {{ targetLabel }}
            </span>
            <span
                v-else
                class="px-2 py-0.5 rounded-full border border-emerald-500/50 bg-emerald-500/15 text-emerald-300 text-[10px] sm:text-[11px] font-black shrink-0 whitespace-nowrap"
            >
                {{ ejercicio.series_objetivo }} series × {{ repsObjetivoTexto }}
            </span>
        </div>

        <!-- Historial última vez (Mínimo / Máximo) del ejercicio: solo se muestra en superseries para no duplicar el hero card -->
        <div
            v-if="showExerciseName && lastData && lastData.encontrado"
            class="flex items-center justify-between gap-2 px-3 py-1.5 rounded-xl bg-[var(--color-obsidian-surface)] border border-violet-500/25 text-left"
            :data-testid="`set-card-last-history-${ejercicioIndex}`"
        >
            <div class="flex items-center gap-1.5 text-gray-300 font-bold uppercase text-[9px] sm:text-[10px] tracking-wider shrink-0">
                <span>⏱️</span>
                <span>Última vez:</span>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 tabular-nums text-[10px] sm:text-xs">
                <span class="text-teal-300 font-bold">
                    Mín: <strong class="text-white">{{ formatPeso(lastData.peso_min ?? lastData.peso_top ?? 0) }} kg</strong>
                    <span class="text-gray-400 text-[9px]"> (x{{ lastData.reps_en_peso_min ?? lastData.reps_en_peso_top }} reps)</span>
                </span>
                <span class="text-gray-600">|</span>
                <span class="text-fuchsia-300 font-bold">
                    Máx: <strong class="text-white">{{ formatPeso(lastData.peso_max ?? lastData.peso_top ?? 0) }} kg</strong>
                    <span class="text-gray-400 text-[9px]"> (x{{ lastData.reps_en_peso_max ?? lastData.reps_en_peso_top }} reps)</span>
                </span>
            </div>
        </div>

        <!-- CARGA / PESO -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-[10px] sm:text-xs font-black uppercase tracking-[0.16em] text-gray-300 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a3 3 0 00-3 3v1H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-2V6a3 3 0 00-3-3zm-1 4a1 1 0 012 0v1h-2V7z" />
                    </svg>
                    <span>CARGA / PESO</span>
                </label>
                <span class="text-[10px] sm:text-xs text-violet-300 font-bold">Toques rápidos (kg)</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <div class="grid grid-cols-3 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarPeso(-5)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(-2.5)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -2.5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(-1)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -1
                    </button>
                </div>

                <div
                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-violet-500/30 rounded-2xl h-10 sm:h-11 shadow-[0_0_16px_rgba(139,92,246,0.12)]"
                >
                    <input
                        v-model.number="form.peso"
                        type="number"
                        inputmode="decimal"
                        step="0.5"
                        min="0"
                        class="w-full bg-transparent text-center text-xl sm:text-2xl font-black text-white outline-none tabular-nums"
                        placeholder="0"
                    />
                    <span
                        class="absolute right-2 sm:right-3 text-[10px] sm:text-xs font-black text-violet-300 uppercase tracking-wider"
                    >kg</span>
                </div>

                <div class="grid grid-cols-3 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarPeso(1)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +1
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(2.5)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +2.5
                    </button>
                    <button
                        type="button"
                        @click="ajustarPeso(5)"
                        class="w-9 sm:w-10 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +5
                    </button>
                </div>
            </div>
        </div>

        <!-- REPETICIONES -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-[10px] sm:text-xs font-black uppercase tracking-[0.16em] text-gray-300 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>REPETICIONES</span>
                </label>
                <span class="px-2 py-0.5 rounded-md bg-emerald-500/15 border border-emerald-500/30 text-[10px] sm:text-xs text-emerald-300 font-black tabular-nums">
                    <template v-if="targetEsfuerzo?.bloque?.reps">
                        Objetivo Serie #{{ serieNumero }}: {{ targetEsfuerzo.bloque.reps }} reps{{ targetEsfuerzo.tipo === 'fallo' ? ' (AL FALLO)' : ' (RIR ' + targetEsfuerzo.valor + ')' }}
                    </template>
                    <template v-else>
                        Objetivo: {{ repsObjetivoTexto }}
                    </template>
                </span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <div class="grid grid-cols-2 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarReps(-2)"
                        class="w-9.5 sm:w-11 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -2
                    </button>
                    <button
                        type="button"
                        @click="ajustarReps(-1)"
                        class="w-9.5 sm:w-11 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-rose-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-rose-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        -1
                    </button>
                </div>

                <div
                    class="flex-1 relative flex items-center justify-center bg-gradient-to-br from-[var(--color-obsidian-elevated)] to-[var(--color-obsidian-surface)] border-2 border-emerald-500/30 rounded-2xl h-10 sm:h-11 shadow-[0_0_16px_rgba(16,185,129,0.12)]"
                >
                    <input
                        v-model.number="form.reps"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        class="w-full bg-transparent text-center text-xl sm:text-2xl font-black text-white outline-none tabular-nums"
                        placeholder="0"
                    />
                    <span
                        class="absolute right-2 sm:right-3 text-[10px] sm:text-xs font-black text-emerald-300 uppercase tracking-wider"
                    >reps</span>
                </div>

                <div class="grid grid-cols-2 gap-1 shrink-0">
                    <button
                        type="button"
                        @click="ajustarReps(1)"
                        class="w-9.5 sm:w-11 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +1
                    </button>
                    <button
                        type="button"
                        @click="ajustarReps(2)"
                        class="w-9.5 sm:w-11 h-10 sm:h-11 bg-[var(--color-obsidian-elevated)] hover:bg-emerald-500/20 active:scale-95 rounded-xl font-bold text-xs sm:text-sm text-emerald-300 cursor-pointer border border-[var(--color-obsidian-border)] transition-colors"
                    >
                        +2
                    </button>
                </div>
            </div>
        </div>

        <!-- Esfuerzo (RIR / RPE / AL FALLO) -->
        <div class="bg-[var(--color-obsidian-surface)] rounded-2xl p-2.5 sm:p-3.5 border border-[var(--color-obsidian-border)] space-y-2 sm:space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs sm:text-sm font-bold text-gray-300 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <circle cx="12" cy="12" r="6" />
                        <circle cx="12" cy="12" r="2" />
                    </svg>
                    <span>Esfuerzo percibido:</span>
                </span>
                <div class="inline-flex rounded-xl bg-[var(--color-obsidian-elevated)] p-0.5 sm:p-1 text-[10px] sm:text-xs font-black border border-[var(--color-obsidian-border)]">
                    <button
                        type="button"
                        @click="selectEsfuerzoTipo('rir')"
                        :class="
                            form.esfuerzo_tipo === 'rir'
                                ? 'bg-emerald-500 text-white shadow-[0_0_12px_rgba(16,185,129,0.4)]'
                                : 'text-gray-400'
                        "
                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg transition-colors cursor-pointer"
                        data-testid="esfuerzo-tipo-rir"
                    >
                        RIR
                    </button>
                    <button
                        type="button"
                        @click="selectEsfuerzoTipo('rpe')"
                        :class="
                            form.esfuerzo_tipo === 'rpe'
                                ? 'bg-amber-500 text-white shadow-[0_0_12px_rgba(245,158,11,0.4)]'
                                : 'text-gray-400'
                        "
                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg transition-colors cursor-pointer"
                        data-testid="esfuerzo-tipo-rpe"
                    >
                        RPE
                    </button>
                    <button
                        type="button"
                        @click="selectEsfuerzoTipo('fallo')"
                        :class="
                            form.esfuerzo_tipo === 'fallo'
                                ? 'bg-rose-500 text-white shadow-[0_0_12px_rgba(244,63,94,0.4)]'
                                : 'text-gray-400'
                        "
                        class="px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg transition-colors cursor-pointer flex items-center gap-1"
                        data-testid="esfuerzo-tipo-fallo"
                        title="Al fallo absoluto — no podés sacar ni una rep más"
                    >
                        <span>⚠</span>
                        <span>FALLO</span>
                    </button>
                </div>
            </div>

            <!-- Target esfuerzo prescrito en notas (p.ej. "2x6 RIR 1 + 2x6 RIR 0" o "2x8 FALLO") -->
            <div
                v-if="targetLabel"
                :class="[
                    'flex items-center justify-between px-2.5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-black uppercase tracking-wider border gap-2',
                    targetEsfuerzo?.tipo === 'fallo'
                        ? 'bg-rose-500/15 border-rose-500/35 text-rose-200'
                        : 'bg-amber-500/15 border-amber-500/35 text-amber-200',
                ]"
                data-testid="esfuerzo-target-banner"
            >
                <div class="flex items-center gap-1.5 min-w-0 truncate">
                    <span class="text-sm">🎯</span>
                    <span class="truncate">Objetivo Serie #{{ serieNumero }}: {{ targetEsfuerzo?.bloque?.reps ? targetEsfuerzo.bloque.reps + ' reps con ' : '' }}{{ targetLabel }}</span>
                </div>
                <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-black uppercase tracking-wider shrink-0 border border-amber-500/30">
                    Planificado
                </span>
            </div>

            <!-- Selector numérico (solo RIR / RPE). Si el tipo es 'fallo', mostramos
                 un panel de confirmación en lugar de los botones numéricos. -->
            <div
                v-if="form.esfuerzo_tipo !== 'fallo'"
                class="grid grid-cols-6 gap-1 sm:gap-1.5 py-0.5"
            >
                <button
                    v-for="val in esfuerzoOptions"
                    :key="val"
                    type="button"
                    @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                    :class="[
                        'relative w-full h-10 sm:h-11 rounded-xl font-black text-xs sm:text-sm transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5 py-0.5',
                        form.esfuerzo_valor === val
                            ? form.esfuerzo_tipo === 'rir'
                                ? 'bg-emerald-500 text-white shadow-[0_0_18px_rgba(16,185,129,0.5)] scale-105'
                                : 'bg-amber-500 text-white shadow-[0_0_18px_rgba(245,158,11,0.5)] scale-105'
                            : 'bg-[var(--color-obsidian-elevated)] text-gray-300 hover:bg-[var(--color-obsidian-overlay)] hover:text-white border',
                        targetEsfuerzo
                            && form.esfuerzo_tipo === 'rir'
                            && targetEsfuerzo.tipo === 'rir'
                            && targetEsfuerzo.valor === val
                            ? 'border-amber-400 ring-2 ring-amber-400/60'
                            : 'border-[var(--color-obsidian-border)]',
                    ]"
                >
                    <!-- Indicador de target (estrella) -->
                    <span
                        v-if="targetEsfuerzo
                            && form.esfuerzo_tipo === 'rir'
                            && targetEsfuerzo.tipo === 'rir'
                            && targetEsfuerzo.valor === val"
                        class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-400 text-[8px] font-black text-amber-950 flex items-center justify-center shadow-[0_0_8px_rgba(245,158,11,0.7)]"
                        aria-hidden="true"
                    >★</span>
                    <span>{{ val }}</span>
                    <span class="text-[8px] sm:text-[9px] uppercase opacity-80 leading-none">
                        {{ esfuerzoLabel(val) }}
                    </span>
                </button>
            </div>

            <!-- Panel "AL FALLO" cuando el tipo seleccionado es fallo absoluto -->
            <div
                v-else
                class="rounded-xl bg-rose-500/10 border border-rose-500/40 p-2.5 sm:p-3 text-rose-200 space-y-1"
                data-testid="esfuerzo-fallo-panel"
            >
                <div class="flex items-center gap-1.5">
                    <span class="text-sm">⚠</span>
                    <span class="text-xs sm:text-sm font-black uppercase tracking-wider">Serie al fallo absoluto</span>
                </div>
                <p class="text-[10px] sm:text-xs leading-snug text-rose-300/90">
                    Distinto de <strong class="text-rose-200">RIR 0</strong>: no podés sacar ni una rep más,
                    ni con técnica ni con trampa. El músculo/técnica/techo ya colapsaron.
                </p>
                <button
                    v-if="form.esfuerzo_valor !== null"
                    type="button"
                    @click="form.esfuerzo_valor = null"
                    class="text-xs underline opacity-80 hover:opacity-100"
                >
                    Limpiar selección
                </button>
            </div>
        </div>

        <!-- Botón COMPLETAR SERIE (se oculta en superseries para usar el botón unificado global) -->
        <template v-if="!hideSubmitButton">
            <button
                v-if="!ejercicioCompleto"
                type="button"
                @click="onCompletar"
                :data-testid="`btn-completar-${ejercicioIndex}`"
                class="w-full py-3 sm:py-3.5 mt-1 rounded-2xl bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] hover:brightness-110 active:scale-[0.98] text-white text-sm sm:text-base font-black tracking-wider shadow-[0_8px_24px_var(--color-violet-glow)] flex items-center justify-center gap-2 transition-all cursor-pointer border border-white/10"
            >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                <span v-if="form.tipo_serie === 'calentamiento'">
                    COMPLETAR CALENTAMIENTO #{{ calentamientoNumero }}
                </span>
                <span v-else>
                    COMPLETAR SERIE #{{ serieNumero }} DE {{ ejercicio.series_objetivo || serieNumero }}
                </span>
            </button>

            <div
                v-else
                class="w-full py-3 sm:py-3.5 mt-1 rounded-2xl bg-emerald-900/30 border border-emerald-500/40 text-emerald-300 text-sm sm:text-base font-black tracking-wider flex items-center justify-center gap-2"
            >
                ✓ Ejercicio completo
            </div>
        </template>
    </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    ejercicio: {
        type: Object,
        required: true,
    },
    ejercicioIndex: {
        type: Number,
        required: true,
    },
    showExerciseName: {
        type: Boolean,
        default: false,
    },
    hideSubmitButton: {
        type: Boolean,
        default: false,
    },
    lastData: {
        type: Object,
        default: null,
    },
});

const formatPeso = (val) => {
    const n = Number(val);
    if (!Number.isFinite(n)) return '0';
    return Number.isInteger(n) ? String(n) : n.toFixed(1).replace(/\.0$/, '');
};

const emit = defineEmits(['complete', 'deshacer']);

const tiposSerie = [
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-emerald-500 text-white' },
    { id: 'calentamiento', label: 'Calentamiento', activeClass: 'bg-indigo-500 text-white' },
    { id: 'dropset', label: 'Drop Set', activeClass: 'bg-amber-500 text-white' },
    { id: 'al_fallo', label: 'Al Fallo', activeClass: 'bg-rose-500 text-white' },
];

const form = ref({
    peso: 0,
    reps: 0,
    tipo_serie: 'efectiva',
    esfuerzo_tipo: 'rir',
    esfuerzo_valor: null,
    nota_user: '',
});

// Contadores independientes por ejercicio (clave para superseries, donde cada
// ejercicio tiene su propio avance). Se calculan contando sets en ej.sets
// directamente, NO desde currentSerieNumero global del store.
const setsEfectivos = computed(() => {
    return (props.ejercicio.sets || []).filter((s) => s.tipo_serie !== 'calentamiento');
});
const setsCalentamiento = computed(() => {
    return (props.ejercicio.sets || []).filter((s) => s.tipo_serie === 'calentamiento');
});

const serieNumero = computed(() => {
    // Si el último set es calentamiento, no queremos que 'saltemos' la serie;
    // usamos la cantidad de efectivos + 1, o si la ultima serie es efectiva
    // entonces la próxima serie es setsEfectivos.length + 1.
    return setsEfectivos.value.length + 1;
});

const calentamientoNumero = computed(() => {
    return setsCalentamiento.value.length + 1;
});

const ejercicioCompleto = computed(() => {
    return setsEfectivos.value.length >= Number(props.ejercicio.series_objetivo || 0);
});

const repsObjetivoTexto = computed(() => {
    const min = props.ejercicio?.reps_min;
    const max = props.ejercicio?.reps_max;
    if (min != null && max != null && min !== '' && max !== '') {
        return String(min) === String(max) ? `${min} reps` : `${min}–${max} reps`;
    }
    return `${min || max || 8} reps`;
});

const esfuerzoOptions = computed(() => {
    if (form.value.esfuerzo_tipo === 'rir') return [0, 1, 2, 3, 4, 5];
    if (form.value.esfuerzo_tipo === 'rpe') return [6, 7, 8, 9, 10];
    return []; // 'fallo' no usa botones numéricos
});

const esfuerzoLabel = (val) => {
    const rir = ['Límite', 'Máx', 'Óptimo', 'Medio', 'Fácil', 'Calent.'];
    const rpe = ['Fácil', 'Fácil+', 'Medio', 'Medio+', 'Máx'];
    if (form.value.esfuerzo_tipo === 'rir') {
        return rir[Math.min(val, 5)] || '';
    }
    return rpe[Math.min(Math.max(val - 6, 0), 4)] || '';
};

// Helper: cambia el tipo de esfuerzo y resetea el valor (a menos que el target
// de la serie actual indique un valor para el nuevo tipo).
const selectEsfuerzoTipo = (tipo) => {
    form.value.esfuerzo_tipo = tipo;
    if (tipo === 'fallo') {
        // Para 'fallo' no usamos valor numérico; usamos 1 como marcador interno
        // para que `form.esfuerzo_valor === null` represente "no seleccionado".
        form.value.esfuerzo_valor = null;
        return;
    }
    // Si veníamos de 'fallo' o cambiamos entre rir/rpe, dejamos que el usuario elija
    // un nuevo número (no auto-seleccionamos para no pisar la decisión manual).
};

// Parsea bloques de esfuerzo desde las notas del ejercicio. Soporta:
//   "2x6 RIR 1"     → { series:2, reps:6, tipo:'rir',   valor:1 }
//   "2*6 RIP 1"     → { series:2, reps:6, tipo:'rir',   valor:1 }
//   "2x6 RPE 8"     → { series:2, reps:6, tipo:'rpe',   valor:8 }
//   "2x8 FALLO"     → { series:2, reps:8, tipo:'fallo', valor:null }
//   "2x8 AL FALLO"  → idem
//   "1x20 AL FALLO TÉCNICO" → idem
const rirBlocks = computed(() => {
    const notas = props.ejercicio?.notas;
    if (!notas) return [];
    const blocks = [];
    const re = /(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi;
    for (const m of String(notas).matchAll(re)) {
        if (m[3] !== undefined) {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rir', valor: Number(m[3]) });
        } else if (m[4] !== undefined) {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'rpe', valor: Number(m[4]) });
        } else {
            blocks.push({ series: Number(m[1]), reps: Number(m[2]), tipo: 'fallo', valor: null });
        }
    }
    return blocks;
});

// Devuelve el target de esfuerzo y repeticiones para cualquier número de serie dado (1-indexed)
const getTargetForSetIndex = (idx) => {
    if (rirBlocks.value.length === 0) return null;
    let acumulado = 0;
    for (const b of rirBlocks.value) {
        acumulado += b.series;
        if (idx <= acumulado) {
            return { tipo: b.tipo, valor: b.valor, reps: b.reps, bloque: b };
        }
    }
    return null;
};

// Devuelve el target de esfuerzo (RIR/RPE/FALLO) para la serie actual según los bloques.
const targetEsfuerzo = computed(() => {
    return getTargetForSetIndex(setsEfectivos.value.length + 1);
});

// Etiqueta legible del target actual: "RIR 1 (Óptimo)" | "RPE 8 (Medio)" | "AL FALLO" | null
const targetLabel = computed(() => {
    const t = targetEsfuerzo.value;
    if (!t) return null;
    if (t.tipo === 'fallo') return 'AL FALLO';
    const rir = ['Límite', 'Máx', 'Óptimo', 'Medio', 'Fácil', 'Calent.'];
    const rpe = ['Fácil', 'Fácil+', 'Medio', 'Medio+', 'Máx'];
    const label = t.tipo === 'rir' ? rir[Math.min(t.valor, 5)] : rpe[Math.min(Math.max(t.valor - 6, 0), 4)];
    return `${t.tipo.toUpperCase()} ${t.valor}${label ? ` (${label})` : ''}`;
});

// Pre-rellenar el form con el último set del ejercicio (si hay) o con la
// recomendación por defecto de reps_min. Si el ejercicio trae bloques RIR
// en notas, pre-seleccionamos el RIR objetivo de la serie actual.
watch(
    () => [props.ejercicio?.nombre, (props.ejercicio?.sets || []).length, props.ejercicio?.notas],
    () => {
        const sets = props.ejercicio?.sets || [];
        if (sets.length > 0) {
            // Copiamos los valores del último set NO-calentamiento para
            // mantener la consistencia entre series del mismo ejercicio.
            const lastEffective = [...sets].reverse().find((s) => s.tipo_serie !== 'calentamiento');
            const source = lastEffective || sets[sets.length - 1];
            form.value.peso = source.peso || 0;
            // Si la serie actual tiene un target de repeticiones en el bloque actual
            if (targetEsfuerzo.value?.bloque?.reps) {
                form.value.reps = targetEsfuerzo.value.bloque.reps;
            } else {
                form.value.reps = source.reps || 0;
            }
            form.value.tipo_serie = source.tipo_serie || 'efectiva';
            if (targetEsfuerzo.value) {
                form.value.esfuerzo_tipo = targetEsfuerzo.value.tipo;
                form.value.esfuerzo_valor = targetEsfuerzo.value.valor;
            } else {
                form.value.esfuerzo_tipo = source.esfuerzo_tipo || 'rir';
                form.value.esfuerzo_valor = source.esfuerzo_valor ?? null;
            }
        } else {
            form.value.peso = 0;
            if (targetEsfuerzo.value?.bloque?.reps) {
                form.value.reps = targetEsfuerzo.value.bloque.reps;
            } else {
                form.value.reps = Number(props.ejercicio?.reps_min) || 8;
            }
            form.value.tipo_serie = 'efectiva';
            if (targetEsfuerzo.value) {
                form.value.esfuerzo_tipo = targetEsfuerzo.value.tipo;
                form.value.esfuerzo_valor = targetEsfuerzo.value.valor;
            } else {
                form.value.esfuerzo_tipo = 'rir';
                form.value.esfuerzo_valor = null;
            }
        }
    },
    { immediate: true }
);

const ajustarPeso = (delta) => {
    const nuevo = Math.max(0, (Number(form.value.peso) || 0) + delta);
    form.value.peso = Math.round(nuevo * 2) / 2;
};

const ajustarReps = (delta) => {
    form.value.reps = Math.max(0, (Number(form.value.reps) || 0) + delta);
};

const onCompletar = () => {
    emit('complete', {
        ejercicioIndex: props.ejercicioIndex,
        peso: Number(form.value.peso) || 0,
        reps: Number(form.value.reps) || 0,
        tipo_serie: form.value.tipo_serie,
        esfuerzo_tipo: form.value.esfuerzo_tipo,
        esfuerzo_valor: form.value.esfuerzo_valor,
        nota_user: form.value.nota_user,
    });
};

defineExpose({
    getPayload: () => ({
        ejercicioIndex: props.ejercicioIndex,
        peso: Number(form.value.peso) || 0,
        reps: Number(form.value.reps) || 0,
        tipo_serie: form.value.tipo_serie,
        esfuerzo_tipo: form.value.esfuerzo_tipo,
        esfuerzo_valor: form.value.esfuerzo_valor,
        nota_user: form.value.nota_user,
    }),
    ejercicioCompleto,
    form,
});
</script>
