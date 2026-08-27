// useMuscleLoad.js — calcula carga por músculo a partir del historial del usuario.
//
// Modelo basado en openGym (frontend/src/lib/muscles.js, MIT vía ExerciseDB):
//   - Cada set "completo" suma 1.0 al músculo primario y 0.4 a los secundarios
//   - Para "balance" normalizamos 0-4 relativo al músculo más cargado
//   - Para "fatigue" usamos la fecha del último set
//   - Para "strength" usamos el mejor 1RM estimado (Epley: peso × (1 + reps/30))
//
// Input: array de { ejercicio_id, fecha, completado, peso, reps_realizadas,
//                   ejercicio: { musculos: [{ musculo_slug, tipo, peso }] } }
// Output: para cada slug de músculo, { load, level, lastTrained, best1Rm }
//
// Por convención openGym, los músculos secundarios cuentan 0.4 (no 1.0).
// Los sets NO completados (completado=false) no cuentan.

import { computed, ref } from 'vue';

export const MUSCLE_SLUGS = [
    'trapezius', 'deltoids', 'chest', 'upper-back', 'serratus',
    'biceps', 'triceps', 'forearm',
    'abs', 'obliques', 'lower-back',
    'gluteal', 'quadriceps', 'hamstring', 'adductors', 'hip-flexors',
    'calves', 'tibialis',
];

const SECUNDARIO = 0.4;

/**
 * Estima 1RM por Epley (cap a 12 reps, como openGym).
 */
function epley1RM(peso, reps) {
    if (!peso || !reps || reps < 1) return 0;
    if (reps === 1) return peso;
    if (reps > 12) return 0; // muy poco fiable
    return peso * (1 + reps / 30);
}

export function useMuscleLoad(historialRef, options = {}) {
    const { now = () => Date.now() } = options;

    /**
     * load: carga cruda por músculo (no normalizada).
     *   load[slug] = suma de (peso_set × peso_músculo) sobre todos los sets
     *                completados donde el músculo aparece.
     */
    const load = computed(() => {
        const out = Object.fromEntries(MUSCLE_SLUGS.map(s => [s, 0]));
        const data = historialRef.value || [];
        for (const h of data) {
            if (!h.completado) continue;
            const musculos = h.ejercicio?.musculos || [];
            for (const m of musculos) {
                const slug = m.musculo_slug;
                if (!out.hasOwnProperty(slug)) continue;
                const factorMusculo = m.tipo === 'primario' ? 1.0 : SECUNDARIO;
                out[slug] += factorMusculo;
            }
        }
        return out;
    });

    /**
     * lastTrained: timestamp del último set completado que trabajó cada músculo.
     */
    const lastTrained = computed(() => {
        const out = Object.fromEntries(MUSCLE_SLUGS.map(s => [s, null]));
        const data = historialRef.value || [];
        for (const h of data) {
            if (!h.completado || !h.fecha) continue;
            const musculos = h.ejercicio?.musculos || [];
            const t = new Date(h.fecha).getTime();
            for (const m of musculos) {
                const slug = m.musculo_slug;
                if (out[slug] === null || t > out[slug]) out[slug] = t;
            }
        }
        return out;
    });

    /**
     * best1Rm: mejor 1RM estimado en el período considerado.
     */
    const best1Rm = computed(() => {
        const out = Object.fromEntries(MUSCLE_SLUGS.map(s => [s, 0]));
        const data = historialRef.value || [];
        for (const h of data) {
            if (!h.completado) continue;
            const musculos = h.ejercicio?.musculos || [];
            const oneRm = epley1RM(parseFloat(h.peso), parseInt(h.reps_realizadas));
            if (!oneRm) continue;
            for (const m of musculos) {
                const slug = m.musculo_slug;
                if (oneRm > out[slug]) out[slug] = oneRm;
            }
        }
        return out;
    });

    /**
     * balance: distribution del volumen de entrenamiento.
     * Devuelve 0-4, donde 4 es el músculo más entrenado y 0 el no entrenado.
     */
    const balance = computed(() => {
        const l = load.value;
        const max = Math.max(0, ...Object.values(l));
        const out = {};
        for (const slug of MUSCLE_SLUGS) {
            const v = l[slug];
            if (!v || max <= 0) out[slug] = 0;
            else out[slug] = Math.max(1, Math.min(4, Math.ceil(v / max * 4)));
        }
        return out;
    });

    /**
     * fatigue: estado de recuperación.
     *   < 48h desde el último set  → 4 (fatigado, necesita descanso)
     *   48-72h                    → 2 (recuperándose)
     *   > 7 días sin entrenar     → 0 (ready, puede entrenar)
     *   Nunca entrenado            → 0
     */
    const fatigue = computed(() => {
        const lt = lastTrained.value;
        const n = now();
        const out = {};
        for (const slug of MUSCLE_SLUGS) {
            const last = lt[slug];
            if (!last) { out[slug] = 0; continue; }
            const hoursAgo = (n - last) / 3_600_000;
            if (hoursAgo < 48) out[slug] = 4;
            else if (hoursAgo < 72) out[slug] = 2;
            else out[slug] = 0;
        }
        return out;
    });

    /**
     * strength: mejor 1RM reciente normalizado a 0-4.
     */
    const strength = computed(() => {
        const b = best1Rm.value;
        const max = Math.max(0, ...Object.values(b));
        const out = {};
        for (const slug of MUSCLE_SLUGS) {
            const v = b[slug];
            if (!v || max <= 0) out[slug] = 0;
            else out[slug] = Math.max(1, Math.min(4, Math.ceil(v / max * 4)));
        }
        return out;
    });

    /**
     * Devuelve los levels para la vista activa.
     */
    function levelsFor(mode) {
        if (mode === 'balance') return balance.value;
        if (mode === 'fatigue') return fatigue.value;
        if (mode === 'strength') return strength.value;
        return balance.value;
    }

    return {
        load,
        lastTrained,
        best1Rm,
        balance,
        fatigue,
        strength,
        levelsFor,
    };
}
