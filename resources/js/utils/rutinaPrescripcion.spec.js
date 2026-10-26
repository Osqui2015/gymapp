/**
 * Tests de la util compartida de prescripción.
 *
 * Los casos usan las notas REALES de la rutina "3 Días V2" (3 días × 8
 * ejercicios) y de la v1, así si alguien cambia el texto del seeder y rompe
 * el parseo, estos tests lo detectan.
 */
import { describe, it, expect } from 'vitest';
import {
    parseBloques,
    parseRestPause,
    parseProgresion,
    parseConsejo,
    prescripcionDe,
    targetParaSerie,
    etiquetaEsfuerzo,
    resumenPlan,
    seriesPlanificadas,
    setsEfectivos,
    resumenRestPause,
    estimarTiempoSesion,
    volumenPorGrupo,
    tienePlan,
    parseEtiqueta,
} from './rutinaPrescripcion';

describe('parseBloques', () => {
    it('parsea un bloque progresivo "2x6 RIR 1 + 2x4 RIR 0"', () => {
        expect(parseBloques('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.')).toEqual([
            { series: 2, reps: 6, tipo: 'rir', valor: 1 },
            { series: 2, reps: 4, tipo: 'rir', valor: 0 },
        ]);
    });

    it('parsea el bloque con serie al fallo "2x10 RIR 1 + 1x8 FALLO"', () => {
        expect(parseBloques('Bloque: 2x10 RIR 1 + 1x8 FALLO.')).toEqual([
            { series: 2, reps: 10, tipo: 'rir', valor: 1 },
            { series: 1, reps: 8, tipo: 'fallo', valor: null },
        ]);
    });

    it('acepta RPE, RIP y AL FALLO TÉCNICO', () => {
        expect(parseBloques('2x6 RPE 8')).toEqual([
            { series: 2, reps: 6, tipo: 'rpe', valor: 8 },
        ]);
        expect(parseBloques('2*6 RIP 1')).toEqual([
            { series: 2, reps: 6, tipo: 'rir', valor: 1 },
        ]);
        expect(parseBloques('1x20 AL FALLO TÉCNICO')).toEqual([
            { series: 1, reps: 20, tipo: 'fallo', valor: null },
        ]);
    });

    it('devuelve vacío si no hay bloques', () => {
        expect(parseBloques(null)).toEqual([]);
        expect(parseBloques('1x40 (Pull-up australiana, completar todas las reps)')).toEqual([]);
    });
});

describe('parseRestPause', () => {
    const notas =
        'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.';

    it('detecta el rest-pause y sus mini-sets', () => {
        const rp = parseRestPause(notas, parseBloques(notas));

        expect(rp).not.toBeNull();
        expect(rp.minisets).toHaveLength(2);
        expect(rp.minisets[0]).toMatchObject({ reps: 8, descansoSeg: 5, tipo: 'fallo' });
        expect(rp.minisets[1]).toMatchObject({ reps: 8, descansoSeg: 10, tipo: 'fallo' });
    });

    it('devuelve null si las notas no mencionan rest-pause', () => {
        expect(parseRestPause('2x6 RIR 1 + 2x4 RIR 0.', parseBloques('2x6 RIR 1'))).toBeNull();
    });
});

describe('parseProgresion', () => {
    it('extrae la progresión de las dominadas', () => {
        expect(
            parseProgresion(
                'Bloque progresivo: 3x8 RIR 1. Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.'
            )
        ).toBe('Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.');
    });

    it('extrae la progresión del crunch con polea', () => {
        expect(
            parseProgresion(
                '3x12 RIR 1. Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.'
            )
        ).toBe(
            'Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.'
        );
    });

    it('NO confunde la etiqueta "Bloque progresivo" con una progresión', () => {
        expect(parseProgresion('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.')).toBeNull();
    });
});

describe('parseConsejo', () => {
    it('saca el consejo de carga del peso muerto rumano', () => {
        expect(
            parseConsejo(
                'Bloque progresivo: 2x8 RIR 1 + 2x6 RIR 0. Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.'
            )
        ).toBe('Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.');
    });

    it('saca el consejo del crunch abdominal', () => {
        expect(
            parseConsejo(
                '3x15 RIR 1. RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.'
            )
        ).toBe(
            'RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.'
        );
    });

    it('saca el consejo del crunch bicicleta sin dejar el "(12 por lado)"', () => {
        expect(
            parseConsejo(
                '3x16 RIR 1 (12 por lado). RIR fijo: flexión con fatiga es lo que factura la lumbar.'
            )
        ).toBe('RIR fijo: flexión con fatiga es lo que factura la lumbar.');
    });

    it('devuelve null cuando sólo hay plan (sin consejo)', () => {
        expect(parseConsejo('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.')).toBeNull();
        expect(parseConsejo('3x10 RIR 1.')).toBeNull();
    });
});

describe('prescripcionDe', () => {
    it('prefiere la prescripción estructurada sobre las notas', () => {
        const p = prescripcionDe({
            notas: 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
            prescripcion: {
                bloques: [
                    { series: 3, reps: 10, tipo: 'rir', valor: 1 },
                    { series: 1, reps: 10, tipo: 'fallo', valor: null },
                ],
                rest_pause: null,
                progresion: 'Subir 2,5 kg al completar 3x10.',
                consejo: 'Bajada controlada.',
                etiqueta: 'Bloque',
            },
        });

        expect(p.origen).toBe('estructurada');
        expect(p.bloques).toHaveLength(2);
        expect(p.bloques[0]).toEqual({ series: 3, reps: 10, tipo: 'rir', valor: 1 });
        expect(p.progresion).toBe('Subir 2,5 kg al completar 3x10.');
        expect(p.consejo).toBe('Bajada controlada.');
    });

    it('cae al parseo de notas cuando no hay prescripción estructurada', () => {
        const p = prescripcionDe({
            notas: 'Bloque: 2x10 RIR 1 + 1x8 FALLO.',
        });

        expect(p.origen).toBe('notas');
        expect(p.bloques).toHaveLength(2);
    });

    it('normaliza la estructura snake_case de la API', () => {
        const p = prescripcionDe({
            prescripcion: {
                bloques: [{ series: 3, reps: 12, tipo: 'rir', valor: 1 }],
                rest_pause: {
                    minisets: [{ reps: 12, descanso_seg: 5, tipo: 'fallo', valor: null }],
                },
            },
        });

        expect(p.restPause.minisets[0].descansoSeg).toBe(5);
        expect(p.restPause.minisets[0].reps).toBe(12);
    });

    it('devuelve una prescripción vacía si no hay nada', () => {
        const p = prescripcionDe(null);
        expect(p.bloques).toEqual([]);
        expect(p.origen).toBeNull();
    });
});

describe('targetParaSerie', () => {
    const bloques = parseBloques('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.');

    it('mapea cada serie a su bloque', () => {
        expect(targetParaSerie(bloques, 1)).toMatchObject({ tipo: 'rir', valor: 1, reps: 6 });
        expect(targetParaSerie(bloques, 2)).toMatchObject({ tipo: 'rir', valor: 1, reps: 6 });
        expect(targetParaSerie(bloques, 3)).toMatchObject({ tipo: 'rir', valor: 0, reps: 4 });
        expect(targetParaSerie(bloques, 4)).toMatchObject({ tipo: 'rir', valor: 0, reps: 4 });
    });

    it('devuelve null fuera de rango o sin bloques', () => {
        expect(targetParaSerie(bloques, 5)).toBeNull();
        expect(targetParaSerie([], 1)).toBeNull();
    });
});

describe('helpers de etiqueta y resumen', () => {
    it('etiqueta cada tipo de esfuerzo', () => {
        expect(etiquetaEsfuerzo('rir', 1)).toBe('RIR 1');
        expect(etiquetaEsfuerzo('rpe', 8)).toBe('RPE 8');
        expect(etiquetaEsfuerzo('fallo', null)).toBe('AL FALLO');
    });

    it('arma el resumen del plan', () => {
        const bloques = parseBloques('Bloque: 2x10 RIR 1 + 1x8 FALLO.');
        expect(resumenPlan(bloques)).toBe('2×10 RIR 1 → 1×8 AL FALLO');
    });
});

describe('volumen real (rest-pause)', () => {
    const notas =
        'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.';

    it('cuenta los sets efectivos (3 series × 3 sets = 9)', () => {
        const p = prescripcionDe({ notas });
        expect(seriesPlanificadas(p)).toBe(3);
        expect(setsEfectivos(p)).toBe(9);
    });

    it('sin rest-pause, los sets efectivos son las series', () => {
        const p = prescripcionDe({ notas: '2x6 RIR 1 + 2x4 RIR 0.' });
        expect(setsEfectivos(p)).toBe(4);
    });

    it('resume el rest-pause con los descansos', () => {
        const p = prescripcionDe({ notas });
        expect(resumenRestPause(p)).toBe('3 series × 9 sets (descanso 5" / 10")');
    });
});

describe('tienePlan', () => {
    it('detecta si el ejercicio tiene bloques que mostrar', () => {
        expect(tienePlan({ notas: 'Bloque: 2x10 RIR 1 + 1x8 FALLO.' })).toBe(true);
        expect(tienePlan({ prescripcion: { bloques: [{ series: 3, reps: 8, tipo: 'rir', valor: 1 }] } })).toBe(
            true
        );
    });

    it('da false cuando las notas no describen esfuerzo (hay que mostrar la nota cruda)', () => {
        expect(tienePlan({ notas: '1x40 (Pull-up australiana, completar todas las reps)' })).toBe(false);
        expect(tienePlan({ notas: '3x8 (estocadas/zancadas, sin RIR objetivo prescrito)' })).toBe(false);
        expect(tienePlan({})).toBe(false);
        expect(tienePlan(null)).toBe(false);
    });
});

describe('parseEtiqueta', () => {
    it('etiqueta según el tipo de bloque', () => {
        expect(parseEtiqueta('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.')).toBe('Bloque progresivo');
        expect(parseEtiqueta('Rest-pause: 3x8 RIR 1.')).toBe('Rest-pause');
        expect(parseEtiqueta('3x10 RIR 1.')).toBe('Bloque');
        expect(parseEtiqueta(null)).toBeNull();
    });
});

describe('estimarTiempoSesion', () => {
    it('estima minutos a partir de series y descansos', () => {
        const mins = estimarTiempoSesion([
            { nombre: 'Sentadilla', series: 4, descanso_min: 2.5, notas: '2x6 RIR 1 + 2x4 RIR 0.' },
            { nombre: 'Curl', series: 3, descanso_min: 1, notas: '3x10 RIR 1' },
        ]);

        // 4 series: 4×45s + 3×150s = 630s ; 3 series: 3×45s + 2×60s = 255s
        // total 885s ≈ 15 min
        expect(mins).toBe(15);
    });
});

describe('volumenPorGrupo', () => {
    it('agrega series por grupo muscular', () => {
        const grupos = volumenPorGrupo([
            { ejercicio_nombre: 'Press de banca', series: 4, notas: '2x6 RIR 1 + 2x4 RIR 0.' },
            { ejercicio_nombre: 'Press inclinado', series: 3, notas: '3x8 RIR 1' },
            { ejercicio_nombre: 'Sentadilla con barra libre', series: 4, notas: '2x6 RIR 1 + 2x4 RIR 0.' },
        ]);

        expect(grupos.find((g) => g.grupo === 'Pecho').series).toBe(7);
        expect(grupos.find((g) => g.grupo === 'Pierna').series).toBe(4);
    });
});
