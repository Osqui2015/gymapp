/**
 * rutinaPrescripcion.js
 *
 * Util COMPARTIDA para leer la prescripción de un ejercicio.
 *
 * Antes de este archivo, el mismo regex estaba copiado en tres lugares
 * distintos (RutinaAcordeon.vue, ActiveWorkoutModal.vue y SetConfigCard.vue)
 * y cada copia sólo reconocía "2x6 RIR 1". El rest-pause
 * ("descanso 5\" y fallo, descanso 10\" y fallo") no se podía parsear y
 * quedaba como texto muerto.
 *
 * DOS FUENTES DE VERDAD, en este orden:
 *
 *   1. `ejercicio.prescripcion` → objeto JSON estructurado que devuelve la API
 *      (columna `rutinas.prescripcion`). Es la fuente de verdad para los
 *      planes nuevos (ej. "3 Días V2") y no depende de parsear prosa.
 *
 *   2. `ejercicio.notas` → texto libre. Se parsea como fallback para las
 *      rutinas viejas (v1, rutinas públicas, planes de otros users) que
 *      todavía no tienen `prescripcion` cargada.
 *
 * Shape normalizado que devuelve `prescripcionDe()`:
 *
 *   {
 *     bloques: [{ series, reps, tipo, valor }],
 *     restPause: { minisets: [{ reps, descansoSeg, tipo, valor }] } | null,
 *     progresion: string | null,
 *     consejo: string | null,
 *     etiqueta: string | null,
 *     origen: 'estructurada' | 'notas' | null,
 *   }
 *
 * `tipo` es siempre 'rir' | 'rpe' | 'fallo'. Para 'fallo', `valor` es null.
 */

/**
 * Bloques de esfuerzo.
 *
 * Las REPS son opcionales: hay notas que dicen "2X al fallo técnico" sin
 * repeticiones ("Bloque: 2x8 RIR 1 + 2X al fallo técnico (RIR 0)."). En ese
 * caso las reps del bloque son las del bloque anterior (o 8 por defecto).
 */
const RE_BLOQUE = /(\d+)\s*[xX*]\s*(?:(\d+)\s*)?(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi;

/** Minutos de descanso intra-set ("descanso 5\" y fallo", "5\" fallo"). */
const RE_SEGUNDOS_FALLO = /(\d+)\s*(?:"|″|'')?\s*(?:y\s*)?fallo/gi;

/** Minutos de descanso intra-set sin "fallo" ("10\" descanso"). */
const RE_SEGUNDOS_DESCANSO = /(\d+)\s*(?:"|″|'')?\s*descanso/gi;

/** Reps del mini-set ("5 reps por serie", "8 reps → 5\" fallo"). */
const RE_REPS_MINISET = /(\d+)\s*reps?\s*(?:por\s*serie|→|->)/gi;

/** Frases que describen progresión de carga. */
const RE_PROGRESION =
    /\b(sumar|subir|agregar|aumentar|progresa\w*|progresi[oó]n)\b|al\s+completar/i;

/**
 * Normaliza un bloque de effort sin importar de dónde venga.
 */
const normalizarBloque = (b) => {
    const tipo = b.tipo === 'rpe' ? 'rpe' : b.tipo === 'fallo' ? 'fallo' : 'rir';
    const valor =
        tipo === 'fallo' ? null : b.valor === null || b.valor === undefined ? null : Number(b.valor);

    return {
        series: Math.max(1, Number(b.series) || 1),
        reps: Math.max(1, Number(b.reps) || 1),
        tipo,
        valor,
    };
};

/**
 * Parsea los bloques de esfuerzo de un texto de notas.
 *   "2x6 RIR 1"     → { series: 2, reps: 6, tipo: 'rir',   valor: 1 }
 *   "2*6 RIP 1"     → { series: 2, reps: 6, tipo: 'rir',   valor: 1 }
 *   "2x6 RPE 8"     → { series: 2, reps: 6, tipo: 'rpe',   valor: 8 }
 *   "2x8 FALLO"     → { series: 2, reps: 8, tipo: 'fallo', valor: null }
 *   "1x20 AL FALLO TÉCNICO" → { series: 1, reps: 20, tipo: 'fallo', valor: null }
 *   "2X AL FALLO TÉCNICO"   → { series: 2, reps: <del bloque anterior>, tipo: 'fallo', valor: null }
 */
export const parseBloques = (notas) => {
    if (!notas) return [];

    const bloques = [];
    // Reps del bloque anterior: se reutilizan cuando la nota escribe el
    // bloque sin repeticiones ("2X al fallo técnico").
    let repsPrevias = null;
    for (const m of String(notas).matchAll(RE_BLOQUE)) {
        const reps = m[2] !== undefined ? Number(m[2]) : repsPrevias !== null ? repsPrevias : 8;
        repsPrevias = reps;

        if (m[3] !== undefined) {
            bloques.push(normalizarBloque({ series: m[1], reps, tipo: 'rir', valor: m[3] }));
        } else if (m[4] !== undefined) {
            bloques.push(normalizarBloque({ series: m[1], reps, tipo: 'rpe', valor: m[4] }));
        } else {
            bloques.push(normalizarBloque({ series: m[1], reps, tipo: 'fallo', valor: null }));
        }
    }

    return bloques;
};

/**
 * Detecta si las notas describen un rest-pause y devuelve sus mini-sets.
 *
 * Semántica del mini-set: `descansoSeg` es el descanso ANTES de ese mini-set.
 *   "Cada serie = 1 set de 8 reps, descanso 5\" y fallo, descanso 10\" y fallo."
 *   → 8 reps (set principal) → 5" → 8 reps al fallo → 10" → 8 reps al fallo
 */
export const parseRestPause = (notas, bloques = []) => {
    if (!notas) return null;

    const texto = String(notas);
    if (!/rest[\s-]?pause/i.test(texto)) return null;

    const segundos = [];
    for (const m of texto.matchAll(RE_SEGUNDOS_FALLO)) {
        segundos.push(Number(m[1]));
    }
    if (!segundos.length) {
        for (const m of texto.matchAll(RE_SEGUNDOS_DESCANSO)) {
            segundos.push(Number(m[1]));
        }
    }
    if (!segundos.length) return null;

    // Reps del mini-set: explícitas ("5 reps por serie") o, por defecto, las
    // del set principal.
    let repsMini = bloques.length ? bloques[0].reps : 8;
    const mReps = texto.match(RE_REPS_MINISET);
    if (mReps && mReps[1]) {
        repsMini = Number(mReps[1]);
    }

    return {
        minisets: segundos.map((seg) => ({
            reps: repsMini,
            descansoSeg: seg,
            tipo: 'fallo',
            valor: null,
        })),
    };
};

/**
 * Extrae la frase de progresión ("sumar 2,5 kg de lastre al completar 3x8").
 */
export const parseProgresion = (notas) => {
    if (!notas) return null;

    const frases = String(notas)
        .split(/(?<=[.!?])\s+/)
        .map((f) => f.trim())
        .filter((f) => f && RE_PROGRESION.test(f));

    if (!frases.length) return null;

    return frases.join(' ');
};

/**
 * Texto que sobra de las notas una vez que se extrajeron los bloques, la
 * progresión y la mecánica del rest-pause. Sirve para mostrar el "consejo"
 * (técnica, condiciones, warnings) sin repetir el plan.
 */
export const parseConsejo = (notas) => {
    if (!notas) return null;

    const progresion = parseProgresion(notas);

    let limpio = String(notas)
        // Paréntesis que describen la mecánica del rest-pause.
        .replace(/\([^)]*(?:fallo|descanso|reps)[^)]*\)/gi, ' ')
        .replace(RE_BLOQUE, ' ')
        .replace(RE_SEGUNDOS_FALLO, ' ')
        .replace(RE_SEGUNDOS_DESCANSO, ' ')
        .replace(RE_REPS_MINISET, ' ')
        // Reps sueltas que ya están en los bloques ("1 set de 8 reps").
        .replace(/\b\d+\s*reps?\b/gi, ' ')
        .replace(/rest[\s-]?pause\s*(?:final|intra-set)?\s*:?/gi, ' ')
        .replace(/cada\s+serie\s*=\s*1\s*set\s+de/gi, ' ')
        .replace(/\(\s*\d+\s*por\s+lado\s*\)/gi, ' ')
        // "descanso" que quedó sin su número (la mecánica del rest-pause ya
        // se renderiza aparte).
        .replace(/\bdescanso\b/gi, ' ')
        .replace(/\b(bloques?|progresiv[oa])\b\s*:?/gi, ' ')
        .replace(/[+·|→()]/g, ' ')
        .replace(/\s{2,}/g, ' ')
        // Limpia el espacio SÓLO antes de la puntuación (" . " → "."),
        // preservando el espacio después ("RIR fijo, no busques").
        .replace(/\s+([.,;:])/g, '$1')
        .trim();

    // Sacar la frase de progresión (ya se muestra aparte).
    if (progresion) {
        limpio = limpio.replace(progresion, ' ').replace(/\s{2,}/g, ' ').trim();
    }

    // Limpia la puntuación suelta de los bordes, pero CONSERVA el punto final
    // de la frase ("... la lumbar.").
    limpio = limpio.replace(/^[.·\s,;:]+/, '').replace(/[\s,;:]+$/, '').trim();

    // Si sólo queda ruido de formato ("RIR fijo", "+", etc.), no hay consejo.
    if (!limpio || limpio.length < 4) return null;
    if (/^(rir\s*fijo|n\/?a|sin\s+rir|final|rir\s*\d*|rpe\s*\d*)\.?$/i.test(limpio)) return null;

    return limpio;
};

/**
 * Etiqueta corta del tipo de bloque (espejo de
 * App\Services\RutinaPrescripcionService::etiqueta).
 */
export const parseEtiqueta = (notas) => {
    if (!notas) return null;
    if (/bloque\s+progresivo/i.test(notas)) return 'Bloque progresivo';
    if (/rest[\s-]?pause/i.test(notas)) return 'Rest-pause';
    return 'Bloque';
};

/**
 * Normaliza la `prescripcion` estructurada que viene de la API.
 */
const normalizarEstructurada = (p) => {
    if (!p || typeof p !== 'object') return null;

    const bloques = Array.isArray(p.bloques) ? p.bloques.map(normalizarBloque) : [];
    if (!bloques.length) return null;

    let restPause = null;
    if (p.rest_pause && Array.isArray(p.rest_pause.minisets) && p.rest_pause.minisets.length) {
        restPause = {
            minisets: p.rest_pause.minisets.map((m) => ({
                reps: Math.max(1, Number(m.reps) || 1),
                descansoSeg: Math.max(0, Number(m.descanso_seg ?? m.descansoSeg) || 0),
                tipo: m.tipo === 'rir' || m.tipo === 'rpe' ? m.tipo : 'fallo',
                valor: m.valor === undefined ? null : m.valor,
            })),
        };
    }

    return {
        bloques,
        restPause,
        progresion: p.progresion || null,
        consejo: p.consejo || null,
        etiqueta: p.etiqueta || null,
        origen: 'estructurada',
    };
};

/**
 * Normaliza el resultado de parsear las notas.
 */
const normalizarDesdeNotas = (notas) => {
    const bloques = parseBloques(notas);
    if (!bloques.length) {
        return {
            bloques: [],
            restPause: null,
            progresion: parseProgresion(notas),
            consejo: parseConsejo(notas),
            etiqueta: parseEtiqueta(notas),
            origen: notas ? 'notas' : null,
        };
    }

    return {
        bloques,
        restPause: parseRestPause(notas, bloques),
        progresion: parseProgresion(notas),
        consejo: parseConsejo(notas),
        etiqueta: parseEtiqueta(notas),
        origen: 'notas',
    };
};

/**
 * ¿El ejercicio tiene un plan de series que se pueda mostrar como chips?
 * Si NO, conviene mostrar las notas crudas (ej. "3x8 (estocadas, sin RIR
 * objetivo prescrito)" no tiene bloques de esfuerzo).
 */
export const tienePlan = (ejercicio) => prescripcionDe(ejercicio).bloques.length > 0;

/**
 * Prescripción normalizada de un ejercicio.
 * Prefiere el JSON estructurado y cae al parseo de notas.
 */
export const prescripcionDe = (ejercicio) => {
    if (!ejercicio) {
        return {
            bloques: [],
            restPause: null,
            progresion: null,
            consejo: null,
            etiqueta: null,
            origen: null,
        };
    }

    const estructurada = normalizarEstructurada(ejercicio.prescripcion);
    if (estructurada) return estructurada;

    return normalizarDesdeNotas(ejercicio.notas);
};

/**
 * Target de esfuerzo de una serie concreta (1-indexed).
 * Respeta el orden de los bloques: "2x6 RIR 1 + 2x4 RIR 0" → series 1-2 RIR 1,
 * series 3-4 RIR 0.
 */
export const targetParaSerie = (bloques, numeroSerie) => {
    if (!Array.isArray(bloques) || !bloques.length) return null;

    let acumulado = 0;
    for (const b of bloques) {
        acumulado += b.series;
        if (numeroSerie <= acumulado) {
            return { tipo: b.tipo, valor: b.valor, reps: b.reps, bloque: b };
        }
    }

    return null;
};

/**
 * Texto legible del tipo de esfuerzo.
 */
export const etiquetaEsfuerzo = (tipo, valor) => {
    if (tipo === 'fallo') return 'AL FALLO';
    if (tipo === 'rpe') return `RPE ${valor}`;
    return `RIR ${valor}`;
};

/**
 * Resumen corto del plan: "2×6 RIR 1 → 2×4 RIR 0".
 */
export const resumenPlan = (bloques) => {
    if (!Array.isArray(bloques) || !bloques.length) return '';

    return bloques
        .map((b) => `${b.series}×${b.reps} ${etiquetaEsfuerzo(b.tipo, b.valor)}`)
        .join(' → ');
};

/**
 * Clave semántica del esfuerzo, para elegir color en la UI.
 *   'fallo' → al fallo (o RIR 0)
 *   'max'   → RIR 1 / RPE 9-10 (casi al límite)
 *   'normal'→ el resto
 */
export const claveEsfuerzo = (tipo, valor) => {
    if (tipo === 'fallo') return 'fallo';
    if (tipo === 'rir' && Number(valor) <= 1) return 'max';
    if (tipo === 'rpe' && Number(valor) >= 9) return 'max';
    return 'normal';
};

/**
 * Series totales del plan (suma de los bloques).
 */
export const seriesPlanificadas = (prescripcion) => {
    if (!prescripcion || !Array.isArray(prescripcion.bloques)) return 0;
    return prescripcion.bloques.reduce((acc, b) => acc + (Number(b.series) || 0), 0);
};

/**
 * Sets EFFECTIVOS de trabajo: en rest-pause cada "serie" son varios sets
 * (el principal + los mini-sets). Sirve para mostrar el volumen real.
 */
export const setsEfectivos = (prescripcion) => {
    const series = seriesPlanificadas(prescripcion);
    if (!prescripcion || !prescripcion.restPause) return series;

    const miniSets = prescripcion.restPause.minisets.length + 1; // set principal + mini-sets
    return series * miniSets;
};

/**
 * Descripción corta del rest-pause: "3 series × 3 sets (5\" / 10\")".
 */
export const resumenRestPause = (prescripcion) => {
    if (!prescripcion || !prescripcion.restPause) return null;

    const descanses = prescripcion.restPause.minisets.map((m) => `${m.descansoSeg}"`);
    const series = seriesPlanificadas(prescripcion);
    const sets = setsEfectivos(prescripcion);

    return `${series} ${series === 1 ? 'serie' : 'series'} × ${sets} sets (descanso ${descanses.join(' / ')})`;
};

/**
 * Tiempo estimado de una sesión (en minutos) a partir de los ejercicios.
 * Estimación gruesa: 45s de trabajo por set + el descanso configurado entre
 * series del mismo ejercicio.
 */
export const estimarTiempoSesion = (ejercicios = []) => {
    let segundos = 0;

    ejercicios.forEach((ej) => {
        const p = prescripcionDe(ej);
        const series = seriesPlanificadas(p) || Number(ej.series) || 1;
        const descanso = Number(ej.descanso_min) || 0;
        const sets = setsEfectivos(p);

        segundos += sets * 45;
        segundos += Math.max(0, series - 1) * descanso * 60;
    });

    return Math.max(0, Math.round(segundos / 60));
};

/**
 * Volume muscular aproximado por grupo (para badges del resumen del día).
 * Mapeo por nombre de ejercicio → grupos.
 */
const GRUPOS = [
    { re: /sentadilla|goblet|press de pierna|prensa de piernas|extensi[oó]n de cu[aá]driceps/i, grupo: 'Pierna' },
    { re: /peso muerto|hip thrust|buenos d[ií]as|rumano/i, grupo: 'Posterior' },
    { re: /press de banca|press inclinado|press militar|press de hombros/i, grupo: 'Pecho' },
    { re: /jal[oó]n|remo|dominada|pull/i, grupo: 'Espalda' },
    { re: /curl de b[ií]ceps|curl martillo|curl de isquio/i, grupo: 'Bíceps' },
    { re: /extensi[oó]n de tr[ií]ceps|fondos/i, grupo: 'Tríceps' },
    { re: /elevaciones laterales|elevaci[oó]n lateral/i, grupo: 'Hombros' },
    { re: /talones|pantorrilla/i, grupo: 'Gemelos' },
    { re: /crunch|plancha|abs|abdominal/i, grupo: 'Core' },
];

/**
 * Devuelve [{ grupo, series }] agregado por grupo muscular.
 */
export const volumenPorGrupo = (ejercicios = []) => {
    const acc = new Map();

    ejercicios.forEach((ej) => {
        const nombre = ej.ejercicio_nombre || ej.nombre || '';
        const match = GRUPOS.find((g) => g.re.test(nombre));
        const grupo = match ? match.grupo : 'General';
        const series = seriesPlanificadas(prescripcionDe(ej)) || Number(ej.series) || 1;

        acc.set(grupo, (acc.get(grupo) || 0) + series);
    });

    return Array.from(acc.entries())
        .map(([grupo, series]) => ({ grupo, series }))
        .sort((a, b) => b.series - a.series);
};
