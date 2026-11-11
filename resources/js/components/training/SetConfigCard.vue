<!--
    SetConfigCard.vue

    Panel de la SERIE ACTUAL de un ejercicio.

    Rediseño "Focus Mode · 5 datos, 1 acción" (modelo mobile):
      1. (nombre del ejercicio — solo en superseries, lo provee el padre)
      2. (historial — lo provee el padre)
      3. OBJETIVO de la serie  → tarjeta con reps + RIR en grande
      4. (imagen — la provee el padre)
      5. SERIES DE HOY         → chips con peso × reps de cada serie

    El resto (tipo de serie, esfuerzo percibido, plan completo) vive en un
    bloque colapsable "Ajustes de la serie" para que la acción principal quede
    siempre a la vista sin scrollear.

    El botón de registrar NO está acá: es único y lo renderiza el padre
    (`ActiveWorkoutModal`) en una barra fija en la zona del pulgar.

    Props:
      - ejercicio: objeto del ejercicio (nombre, series_objetivo, reps_min/max, sets[])
      - ejercicioIndex: índice en session.ejercicios (para que el padre registre contra el store)
      - showExerciseName: muestra el encabezado con el nombre (lo usa el padre en superseries)
      - lastData: historial (mín/máx) para el bloque de superserie
      - hideSubmitButton / hidePlanOnDesktop: compatibilidad con el padre

    Emite:
      - complete({ ejercicioIndex, peso, reps, tipo_serie, esfuerzo_tipo, esfuerzo_valor, nota_user })

    Expone (vía ref, para la barra sticky del padre):
      - getPayload(), form, ejercicioCompleto
-->
<template>
    <section
        class="space-y-4"
        :data-testid="`set-config-card-${ejercicioIndex}`"
    >
        <!-- Nombre del ejercicio (solo en superseries: cada card necesita su título) -->
        <div
            v-if="showExerciseName"
            class="flex items-center justify-between gap-2"
            :data-testid="`set-card-name-${ejercicioIndex}`"
        >
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-1.5 h-1.5 rounded-full bg-brand shrink-0"></span>
                <span
                    class="text-[13px] font-black text-text truncate uppercase tracking-tight"
                    :title="ejercicio.nombre"
                >
                    {{ ejercicio.nombre }}
                </span>
            </div>
            <span class="text-[10px] font-bold text-text-3 shrink-0">
                <template v-if="esBloque">Bloque sin series</template>
                <template v-else>{{ ejercicio.series_objetivo }}s × {{ repsObjetivoTexto }}</template>
            </span>
        </div>

        <!--
            BLOQUE 3 · OBJETIVO DE ESTA SERIE
            El dato central de la pantalla: reps + RIR/RPE/FALLO de la serie
            exacta que el usuario está por hacer, con el plan resumido debajo.
        -->
        <div
            data-testid="set-target-hero-card"
            class="relative overflow-hidden rounded-[20px] border px-4 py-4 transition-colors"
            :class="clasesTarget"
            :style="estiloTarget"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                    <template v-if="form.tipo_serie === 'calentamiento'">
                        Calentamiento {{ calentamientoNumero }}
                    </template>
                    <template v-else>Objetivo de esta serie</template>
                </span>
                <span class="flex items-center gap-1.5 shrink-0">
                    <span
                        v-if="ejercicio.superserie_grupo"
                        class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-surface-2 border border-border text-text-3"
                    >
                        SS {{ ejercicio.superserie_grupo }}
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                        <template v-if="form.tipo_serie === 'calentamiento'">Entrada en calor</template>
                        <template v-else>Serie {{ serieNumero }} de {{ numeroSeriesTexto }}</template>
                    </span>
                </span>
            </div>

            <div class="mt-2 flex items-baseline gap-3.5">
                <span class="text-[40px] leading-none font-black tracking-[-0.035em] text-text tabular-nums">
                    {{ repsGrande }}<span class="text-[15px] font-black text-text-2 tracking-normal"> reps</span>
                </span>
                <span class="w-px h-8 bg-border-strong self-center"></span>
                <span class="flex items-baseline gap-1.5">
                    <span
                        class="text-[30px] leading-none font-black tracking-[-0.03em] tabular-nums"
                        :class="colorEsfuerzo"
                    >
                        {{ esfuerzoGrande }}
                    </span>
                    <span
                        v-if="esfuerzoCualitativo"
                        class="text-[11px] font-black text-text-3 uppercase tracking-wide"
                    >
                        {{ esfuerzoCualitativo }}
                    </span>
                </span>
            </div>

            <p
                data-testid="set-plan-banner"
                class="mt-2.5 text-[12px] font-semibold text-text-3 leading-snug"
            >
                <span class="text-text-2 font-bold">Plan:</span> {{ tituloPlan }}
            </p>
        </div>

        <!--
            BLOQUE 5 · TUS SERIES
            Grilla siempre visible: peso y reps de cada serie (hechas en verde,
            la que viene resaltada, las que faltan con el objetivo prescrito).
        -->
        <div
            v-if="Number(ejercicio.series_objetivo) > 0"
            data-testid="sets-progress-tracker"
        >
            <div class="flex items-baseline justify-between gap-2 mb-2">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                    Tus series
                </p>
                <span class="text-[11px] font-bold text-text-3 tabular-nums">
                    {{ setsEfectivos.length }} de {{ ejercicio.series_objetivo }} hechas
                </span>
            </div>

            <div class="grid grid-cols-4 gap-2">
                <div
                    v-for="idx in Number(ejercicio.series_objetivo)"
                    :key="idx"
                    class="rounded-[14px] border px-1.5 py-2 text-center transition-all select-none flex flex-col justify-between gap-0.5 min-w-0"
                    :class="[
                        getCompletedSet(idx)
                            ? 'bg-success/10 border-success/40'
                            : idx === serieNumero && form.tipo_serie !== 'calentamiento'
                                ? 'bg-surface-2 border-brand ring-2 ring-brand/25'
                                : 'bg-surface border-border'
                    ]"
                >
                    <span
                        class="flex items-center justify-center gap-1 text-[9px] font-black uppercase tracking-[0.08em]"
                        :class="[
                            getCompletedSet(idx)
                                ? 'text-success'
                                : idx === serieNumero && form.tipo_serie !== 'calentamiento'
                                    ? 'text-brand'
                                    : 'text-text-3'
                        ]"
                    >
                        #{{ idx }}
                        <svg
                            v-if="getCompletedSet(idx)"
                            class="w-2.5 h-2.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="4"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span
                            v-else-if="idx === serieNumero && form.tipo_serie !== 'calentamiento'"
                            class="w-1.5 h-1.5 rounded-full bg-brand"
                        ></span>
                    </span>

                    <template v-if="getCompletedSet(idx)">
                        <span class="block text-[14px] font-black tabular-nums leading-tight tracking-[-0.01em]" :class="'text-success'">
                            {{ formatPeso(getCompletedSet(idx).peso) }} kg
                        </span>
                        <span class="block text-[10px] font-extrabold text-success tabular-nums leading-tight">
                            ×{{ getCompletedSet(idx).reps }}<template v-if="getCompletedSet(idx).esfuerzo_valor != null"> · {{ getCompletedSet(idx).esfuerzo_tipo?.toUpperCase() }} {{ getCompletedSet(idx).esfuerzo_valor }}</template>
                        </span>
                    </template>
                    <template v-else>
                        <span
                            class="block text-[14px] font-black tabular-nums leading-tight tracking-[-0.01em]"
                            :class="idx === serieNumero && form.tipo_serie !== 'calentamiento' ? 'text-text' : 'text-text-2'"
                        >
                            {{ pesoSugeridoSerie() }} kg
                        </span>
                        <span class="block text-[10px] font-extrabold text-text-3 tabular-nums leading-tight">
                            <template v-if="getTargetForSetIndex(idx)">
                                ×{{ getTargetForSetIndex(idx).reps }}
                                · {{ getTargetForSetIndex(idx).tipo === 'fallo' ? 'FALLO' : getTargetForSetIndex(idx).tipo.toUpperCase() + ' ' + getTargetForSetIndex(idx).valor }}
                            </template>
                            <!-- Sin plan estructurado: igual mostramos el rango de reps objetivo -->
                            <template v-else>× {{ rangoReps }}</template>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!--
            BLOQUE 4 · PESO y REPS de la serie
            Campos apilados a ancho completo (como el modelo): label + hint
            arriba, stepper −/valor/+ abajo con el número grande al centro.
        -->
        <div class="space-y-3.5">
            <!-- PESO -->
            <div>
                <div class="flex items-baseline justify-between gap-2 mb-1.5">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                        Peso
                    </span>
                    <span v-if="ultimoPesoTexto" class="text-[10px] font-bold text-text-3 tabular-nums">
                        Último: {{ ultimoPesoTexto }} kg
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="ajustarPeso(-1)"
                        class="w-[52px] h-14 shrink-0 rounded-[14px] bg-surface border border-border text-text-3 hover:text-text hover:bg-surface-2 active:scale-95 transition-all cursor-pointer flex items-center justify-center select-none"
                        aria-label="Bajar peso"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" d="M5 12h14" />
                        </svg>
                    </button>

                    <label
                        class="flex-1 min-w-0 h-14 rounded-[14px] bg-surface border border-brand flex items-baseline justify-center gap-1.5 ring-[3px] ring-brand/15"
                    >
                        <input
                            v-model.number="form.peso"
                            type="number"
                            inputmode="decimal"
                            step="0.5"
                            min="0"
                            class="w-full min-w-0 bg-transparent text-center text-[26px] font-black text-text outline-none tabular-nums tracking-[-0.03em] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            placeholder="0"
                            aria-label="Peso de la serie en kilogramos"
                        />
                        <span class="text-[11px] font-black text-text-3 uppercase tracking-[0.1em] shrink-0 pr-1">kg</span>
                    </label>

                    <button
                        type="button"
                        @click="ajustarPeso(1)"
                        class="w-[52px] h-14 shrink-0 rounded-[14px] bg-surface border border-border text-text-2 hover:text-text hover:bg-surface-2 active:scale-95 transition-all cursor-pointer flex items-center justify-center select-none"
                        aria-label="Subir peso"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- REPS -->
            <div>
                <div class="flex items-baseline justify-between gap-2 mb-1.5">
                    <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                        Repeticiones
                    </span>
                    <span class="text-[10px] font-bold text-text-3 tabular-nums">
                        Objetivo {{ rangoReps }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="ajustarReps(-1)"
                        class="w-[52px] h-14 shrink-0 rounded-[14px] bg-surface border border-border text-text-3 hover:text-text hover:bg-surface-2 active:scale-95 transition-all cursor-pointer flex items-center justify-center select-none"
                        aria-label="Bajar repeticiones"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" d="M5 12h14" />
                        </svg>
                    </button>

                    <label
                        class="flex-1 min-w-0 h-14 rounded-[14px] bg-surface border border-brand flex items-baseline justify-center gap-1.5 ring-[3px] ring-brand/15"
                    >
                        <input
                            v-model.number="form.reps"
                            type="number"
                            inputmode="numeric"
                            min="0"
                            class="w-full min-w-0 bg-transparent text-center text-[26px] font-black text-text outline-none tabular-nums tracking-[-0.03em] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            placeholder="0"
                            aria-label="Repeticiones de la serie"
                        />
                        <span class="text-[11px] font-black text-text-3 uppercase tracking-[0.1em] shrink-0 pr-1">reps</span>
                    </label>

                    <button
                        type="button"
                        @click="ajustarReps(1)"
                        class="w-[52px] h-14 shrink-0 rounded-[14px] bg-surface border border-border text-text-2 hover:text-text hover:bg-surface-2 active:scale-95 transition-all cursor-pointer flex items-center justify-center select-none"
                        aria-label="Subir repeticiones"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Historial última vez (solo superseries: el bloque de arriba lo muestra para el actual) -->
        <div
            v-if="showExerciseName && lastData && lastData.encontrado"
            class="flex items-center justify-between gap-2 text-[11px] tabular-nums"
            :data-testid="`set-card-last-history-${ejercicioIndex}`"
        >
            <span class="text-[9px] font-black uppercase tracking-wider text-text-3 shrink-0">Historial</span>
            <span class="text-text-2 truncate">
                {{ formatPeso(lastData.peso_min ?? lastData.peso_top ?? 0) }} kg
                <span class="text-text-3">({{ lastData.reps_en_peso_min ?? lastData.reps_en_peso_top ?? 0 }})</span>
                <span class="text-text-3 mx-1">→</span>
                <strong class="text-text">{{ formatPeso(lastData.peso_max ?? lastData.peso_top ?? 0) }} kg</strong>
                <span class="text-text-3">({{ lastData.reps_en_peso_max ?? lastData.reps_en_peso_top ?? 0 }})</span>
            </span>
        </div>

        <!--
            AJUSTES DE LA SERIE (colapsable).

            Todo lo que no es de las 5 cosas esenciales vive acá para no
            empujar el CTA fuera de la pantalla: tipo de serie, esfuerzo
            percibido y el plan completo con sus bloques.
            Se abre solo cuando el usuario quiere cambiarlo.
        -->
        <details class="rounded-2xl border border-border bg-surface overflow-hidden">
            <summary
                class="flex items-center justify-between gap-2 px-3.5 py-2.5 cursor-pointer list-none select-none"
            >
                <span class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                    Ajustes de la serie
                </span>
                <span class="flex items-center gap-2 min-w-0">
                    <span class="text-[11px] font-bold text-text-2 truncate">
                        {{ form.tipo_serie.toUpperCase() }} · {{ esfuerzoGrande }}
                    </span>
                    <svg
                        class="w-3.5 h-3.5 text-text-3 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </summary>

            <div class="px-3.5 pb-3.5 pt-3 border-t border-border space-y-3">
                <!-- Tipo de serie -->
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3 mb-1.5">
                        Tipo de serie
                    </p>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            v-for="tipo in tiposSerie"
                            :key="tipo.id"
                            type="button"
                            @click="form.tipo_serie = tipo.id"
                            :class="[
                                'h-9 rounded-lg text-[10px] font-black uppercase tracking-wide transition-all cursor-pointer truncate px-1',
                                form.tipo_serie === tipo.id
                                    ? tipo.activeClass
                                    : 'bg-surface-2 text-text-2 hover:text-text border border-border',
                            ]"
                        >
                            {{ tipo.label }}
                        </button>
                    </div>
                </div>

                <!-- Esfuerzo percibido -->
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-text-3">
                            Esfuerzo percibido
                        </p>
                        <div class="inline-flex rounded-lg bg-surface-2 p-0.5 border border-border">
                            <button
                                type="button"
                                @click="selectEsfuerzoTipo('rir')"
                                :class="
                                    form.esfuerzo_tipo === 'rir'
                                        ? 'bg-surface text-text shadow-sm'
                                        : 'text-text-3'
                                "
                                class="px-2 py-0.5 rounded-md text-[10px] font-black transition-colors cursor-pointer"
                                data-testid="esfuerzo-tipo-rir"
                            >
                                RIR
                            </button>
                            <button
                                type="button"
                                @click="selectEsfuerzoTipo('rpe')"
                                :class="
                                    form.esfuerzo_tipo === 'rpe'
                                        ? 'bg-surface text-text shadow-sm'
                                        : 'text-text-3'
                                "
                                class="px-2 py-0.5 rounded-md text-[10px] font-black transition-colors cursor-pointer"
                                data-testid="esfuerzo-tipo-rpe"
                            >
                                RPE
                            </button>
                            <button
                                type="button"
                                @click="selectEsfuerzoTipo('fallo')"
                                :class="
                                    form.esfuerzo_tipo === 'fallo'
                                        ? 'bg-danger text-white shadow-sm'
                                        : 'text-text-3'
                                "
                                class="px-2 py-0.5 rounded-md text-[10px] font-black transition-colors cursor-pointer"
                                data-testid="esfuerzo-tipo-fallo"
                                title="Al fallo absoluto — no podés sacar ni una rep más"
                            >
                                FALLO
                            </button>
                        </div>
                    </div>

                    <!-- Objetivo prescrito para esta serie -->
                    <div
                        v-if="targetLabel"
                        class="flex items-center justify-between gap-2 mb-2 text-[10px] font-bold text-text-3"
                        data-testid="esfuerzo-target-banner"
                    >
                        <span class="truncate">
                            Objetivo Serie #{{ serieNumero }}: {{ targetEsfuerzo?.bloque?.reps ? targetEsfuerzo.bloque.reps + ' reps con ' : '' }}{{ targetLabel }}
                        </span>
                        <span class="uppercase tracking-wider shrink-0">Planificado</span>
                    </div>

                    <div
                        v-if="form.esfuerzo_tipo !== 'fallo'"
                        class="grid grid-cols-6 gap-1.5"
                    >
                        <button
                            v-for="val in esfuerzoOptions"
                            :key="val"
                            type="button"
                            @click="form.esfuerzo_valor = form.esfuerzo_valor === val ? null : val"
                            :class="[
                                'relative h-10 rounded-lg font-black text-[11px] transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5',
                                form.esfuerzo_valor === val
                                    ? form.esfuerzo_tipo === 'rir'
                                        ? 'bg-success text-on-brand'
                                        : 'bg-warning text-on-brand'
                                    : 'bg-surface-2 text-text-2 hover:text-text border border-border',
                            ]"
                        >
                            <span class="leading-none">{{ val }}</span>
                            <span class="leading-none text-[8px] font-bold opacity-70">
                                {{ esfuerzoLabel(val) }}
                            </span>
                            <span
                                v-if="
                                    targetEsfuerzo
                                    && form.esfuerzo_tipo === 'rir'
                                    && targetEsfuerzo.tipo === 'rir'
                                    && targetEsfuerzo.valor === val
                                "
                                class="absolute top-0.5 right-0.5 w-1 h-1 rounded-full bg-warning"
                            ></span>
                        </button>
                    </div>

                    <p
                        v-else
                        class="text-[11px] leading-snug text-danger bg-danger/10 border border-danger/25 rounded-lg px-2.5 py-2"
                    >
                        <strong>Al fallo absoluto:</strong> no podés sacar ni una rep más.
                        Distinto de RIR 0.
                    </p>
                </div>

                <!-- Plan completo con bloques -->
                <div
                    data-testid="ejercicio-prescripcion-box"
                    class="pt-1"
                >
                    <PrescripcionCard
                        :ejercicio="ejercicio"
                        :prescripcion="prescripcionEjercicio"
                        :serie-actual="serieNumero"
                    />
                </div>
            </div>
        </details>
    </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import PrescripcionCard from './PrescripcionCard.vue';
import { prescripcionDe, targetParaSerie, resumenPlan } from '../../utils/rutinaPrescripcion';

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
    /**
     * Oculta el plan resumido en desktop cuando el padre ya lo renderiza.
     * Se mantiene por compatibilidad con el contenedor.
     */
    hidePlanOnDesktop: {
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
    { id: 'efectiva', label: 'Efectiva', activeClass: 'bg-success text-on-brand' },
    { id: 'calentamiento', label: 'Calent.', activeClass: 'bg-brand text-on-brand' },
    { id: 'dropset', label: 'Drop', activeClass: 'bg-warning text-on-brand' },
    { id: 'al_fallo', label: 'Fallo', activeClass: 'bg-danger text-white' },
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

/**
 * Bloque sin series efectivas (ej. el bloque de movilidad / calentamiento
 * del día). No tiene objetivo de series: se cierra registrando el
 * calentamiento y el store avanza al siguiente ejercicio.
 */
const esBloque = computed(() => {
    return Number(props.ejercicio?.series_objetivo || 0) === 0;
});

const ejercicioCompleto = computed(() => {
    if (props.ejercicio?.completed) return true;
    if (esBloque.value) return false;
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

/** Rango corto de reps ("8–10"), para los chips de la tira de series. */
const rangoReps = computed(() => {
    const min = props.ejercicio?.reps_min;
    const max = props.ejercicio?.reps_max;
    if (min != null && max != null && min !== '' && max !== '') {
        return String(min) === String(max) ? String(min) : `${min}–${max}`;
    }
    return String(min || max || 8);
});

// === Tipografía grande del objetivo (Bloque 3) ===

/** Total de series del ejercicio, con fallback a la serie actual. */
const numeroSeriesTexto = computed(() => {
    return Number(props.ejercicio?.series_objetivo || 0) || serieNumero.value;
});

/** Reps del objetivo, en grande. */
const repsGrande = computed(() => {
    return (
        form.value.reps ||
        targetEsfuerzo.value?.bloque?.reps ||
        props.ejercicio?.reps_min ||
        props.ejercicio?.reps_max ||
        8
    );
});

/**
 * "RIR 1" / "RPE 8" / "FALLO" en grande.
 *
 * Se prefiere el target prescrito para ESTA serie (`targetEsfuerzo`) y, si no
 * hay plan estructurado, se cae a lo que el usuario tenga seleccionado.
 */
const esfuerzoGrande = computed(() => {
    const t = targetEsfuerzo.value;
    if (t) {
        return t.tipo === 'fallo' ? 'FALLO' : `${t.tipo.toUpperCase()} ${t.valor}`;
    }
    if (form.value.esfuerzo_tipo === 'fallo') return 'FALLO';
    const v = form.value.esfuerzo_valor;
    return v == null
        ? form.value.esfuerzo_tipo.toUpperCase()
        : `${form.value.esfuerzo_tipo.toUpperCase()} ${v}`;
});

/** Palabra cualitativa del esfuerzo ("Máx", "Óptimo"...), o vacío. */
const esfuerzoCualitativo = computed(() => {
    const t = targetEsfuerzo.value;
    if (!t || t.tipo === 'fallo') return '';
    const rir = ['Límite', 'Máx', 'Óptimo', 'Medio', 'Fácil', 'Calent.'];
    const rpe = ['Fácil', 'Fácil+', 'Medio', 'Medio+', 'Máx'];
    return t.tipo === 'rir'
        ? rir[Math.min(t.valor, 5)] || ''
        : rpe[Math.min(Math.max(t.valor - 6, 0), 4)] || '';
});

/** Color del bloque de esfuerzo: ámbar para RIR/RPE, rojo para fallo. */
const colorEsfuerzo = computed(() => {
    if (esFallo.value) return 'text-danger';
    return 'text-warning';
});

/** ¿El esfuerzo objetivo (o el elegido) es al fallo absoluto? */
const esFallo = computed(() => {
    const t = targetEsfuerzo.value;
    return (
        t?.tipo === 'fallo' ||
        form.value.esfuerzo_tipo === 'fallo' ||
        (form.value.esfuerzo_tipo === 'rir' && form.value.esfuerzo_valor === 0)
    );
});

/** Fondo/borde de la tarjeta de objetivo. */
const clasesTarget = computed(() => {
    return esFallo.value
        ? 'bg-danger/[0.06] border-danger/35'
        : 'bg-surface border-border';
});

/** Acento radial de la esquina (ámbar / rojo), con el token del tema. */
const estiloTarget = computed(() => {
    const token = esFallo.value ? 'var(--color-danger)' : 'var(--color-warning)';
    return {
        backgroundImage: `radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, ${token} 14%, transparent), transparent 62%)`,
    };
});

/** Peso de la última serie registrada (o del historial), para el hint. */
const ultimoPesoTexto = computed(() => {
    const sets = setsEfectivos.value;
    const ultima = sets[sets.length - 1];
    if (ultima && Number(ultima.peso) > 0) return formatPeso(ultima.peso);
    const historico = props.lastData?.peso_max ?? props.lastData?.peso_top;
    return historico ? formatPeso(historico) : '';
});

/**
 * Set efectivo (1-indexed) ya registrado de este ejercicio.
 */
const getCompletedSet = (idx) => {
    return setsEfectivos.value[idx - 1] || null;
};

/**
 * Peso sugerido para una serie pendiente.
 *
 * Se usa el peso de la última serie registrada como base (la progresión
 * típica es mantener o subir levemente). Si el ejercicio todavía no tiene
 * ninguna serie registrada, se cae al máximo histórico del `lastData` y,
 * en último caso, al peso precargado en el formulario.
 *
 * Se muestra en GRIS porque es una sugerencia, no un dato hecho.
 */
const pesoSugeridoSerie = () => {
    const ultimaSerie = setsEfectivos.value[setsEfectivos.value.length - 1];
    if (ultimaSerie && ultimaSerie.peso) {
        return formatPeso(ultimaSerie.peso);
    }

    const historico = props.lastData?.peso_max ?? props.lastData?.peso_top;
    if (historico) {
        return formatPeso(historico);
    }

    return form.value.peso ? formatPeso(form.value.peso) : '—';
};

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
        // Para 'fallo' no usamos valor numérico; usamos null como "no
        // seleccionado" para que el panel de confirmación se muestre.
        form.value.esfuerzo_valor = null;
        return;
    }
    // Si veníamos de 'fallo' o cambiamos entre rir/rpe, dejamos que el usuario
    // elija un nuevo número (no auto-seleccionamos para no pisar su decisión).
};

// Prescripción del ejercicio: prefiere el JSON estructurado (`prescripcion`
// que devuelve la API) y cae al parseo de `notas` para las rutinas viejas.
// Ver utils/rutinaPrescripcion.js.
const prescripcionEjercicio = computed(() => prescripcionDe(props.ejercicio));

// Bloques de esfuerzo: { series, reps, tipo:'rir'|'rpe'|'fallo', valor }
const rirBlocks = computed(() => prescripcionEjercicio.value.bloques);

// Resumen del plan ("2×6 RIR 1 → 2×4 RIR 0") o, si no hay plan, el objetivo
// plano de series × reps.
const tituloPlan = computed(() => {
    if (esBloque.value) {
        return 'Bloque de calentamiento (sin series programadas)';
    }
    if (prescripcionEjercicio.value.bloques.length) {
        return resumenPlan(prescripcionEjercicio.value.bloques);
    }
    return `${props.ejercicio.series_objetivo} series de ${repsObjetivoTexto.value}`;
});

// Devuelve el target de esfuerzo y repeticiones para cualquier número de serie dado (1-indexed)
const getTargetForSetIndex = (idx) => {
    return targetParaSerie(rirBlocks.value, idx);
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

const copiarUltimaSesion = () => {
    if (!props.lastData?.encontrado) return;
    const p = Number(props.lastData.peso_max ?? props.lastData.peso_top ?? 0);
    const r = Number(props.lastData.reps_en_peso_max ?? props.lastData.reps_en_peso_top ?? 0);
    if (p > 0) form.value.peso = p;
    if (r > 0) form.value.reps = r;
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
    copiarUltimaSesion,
    onCompletar,
});
</script>
