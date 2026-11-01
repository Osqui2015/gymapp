import { describe, it, expect } from 'vitest';
import {
    filtrarHistorial,
    ordenarHistorial,
    rutinasDisponibles,
    diasDisponibles,
    contarSeriesPorFecha,
} from './useHistorialFiltros';

/**
 * El payload de /api/historial devuelve 'rutina_nombre' y la fecha como
 * ISO completo ("2026-10-07T00:00:00.000000Z").
 */
const filas = [
    {
        id: 1, rutina_nombre: 'Push', dia: 'Día 1', ejercicio_nombre: 'Press de banca',
        peso: 60, reps_realizadas: 10, completado: true, fecha: '2026-10-07T00:00:00.000000Z',
    },
    {
        id: 2, rutina_nombre: 'Pull', dia: 'Día 2', ejercicio_nombre: 'Dominadas',
        peso: 20, reps_realizadas: 8, completado: false, fecha: '2026-09-15T00:00:00.000000Z',
    },
    {
        id: 3, rutina_nombre: 'Push', dia: 'Día 1', ejercicio_nombre: 'Aperturas',
        peso: 12, reps_realizadas: 15, completado: true, fecha: '2026-08-01T00:00:00.000000Z',
    },
];

describe('useHistorialFiltros', () => {
    it('filtra por rutina usando rutina_nombre (no "rutina")', () => {
        const out = filtrarHistorial(filas, { rutina: 'Push' });
        expect(out).toHaveLength(2);
        expect(out.every((f) => f.rutina_nombre === 'Push')).toBe(true);
    });

    it('no vacía la tabla si se filtra por una rutina que no existe', () => {
        const out = filtrarHistorial(filas, { rutina: 'Inexistente' });
        expect(out).toHaveLength(0);
    });

    it('el dropdown de rutinas lista los valores reales del payload', () => {
        expect(rutinasDisponibles(filas)).toEqual(['Pull', 'Push']);
    });

    it('el dropdown de días lista los valores reales del payload', () => {
        expect(diasDisponibles(filas)).toEqual(['Día 1', 'Día 2']);
    });

    it('filtra por búsqueda de texto en el nombre del ejercicio', () => {
        const out = filtrarHistorial(filas, { search: 'press' });
        expect(out).toHaveLength(1);
        expect(out[0].id).toBe(1);
    });

    it('filtra por solo completados', () => {
        const out = filtrarHistorial(filas, { soloCompletados: true });
        expect(out).toHaveLength(2);
        expect(out.every((f) => f.completado)).toBe(true);
    });

    it('filtra por los últimos N días', () => {
        const out = filtrarHistorial(filas, { periodo: '30d' });
        expect(out.map((f) => f.id)).toEqual([1]);
    });

    it('"Este año" filtra por el año en curso (no lo ignora)', () => {
        const anio = new Date().getFullYear();
        const dentro = {
            ...filas[0],
            fecha: `${anio}-03-01T00:00:00.000000Z`,
        };
        const fuera = {
            ...filas[2],
            fecha: `${anio - 3}-08-01T00:00:00.000000Z`,
        };
        const out = filtrarHistorial([dentro, fuera], { periodo: 'year' });
        expect(out).toHaveLength(1);
        expect(out[0].id).toBe(dentro.id);
    });

    it('"Rango personalizado" filtra por fechaDesde/fechaHasta', () => {
        const out = filtrarHistorial(filas, {
            periodo: 'custom',
            fechaDesde: '2026-09-01',
            fechaHasta: '2026-10-31',
        });
        expect(out.map((f) => f.id)).toEqual([1, 2]);
    });

    it('"Rango personalizado" con sólo fechaDesde incluye lo posterior', () => {
        const out = filtrarHistorial(filas, { periodo: 'custom', fechaDesde: '2026-10-01' });
        expect(out.map((f) => f.id)).toEqual([1]);
    });

    it('"Rango personalizado" con sólo fechaHasta incluye lo anterior', () => {
        const out = filtrarHistorial(filas, { periodo: 'custom', fechaHasta: '2026-09-30' });
        expect(out.map((f) => f.id)).toEqual([2, 3]);
    });

    it('ordena por volumen descendente', () => {
        const out = ordenarHistorial(filas, 'volumen_desc');
        // 60x10=600, 20x8=160, 12x15=180
        expect(out.map((f) => f.id)).toEqual([1, 3, 2]);
    });

    it('ordena por ejercicio A-Z', () => {
        const out = ordenarHistorial(filas, 'ejercicio_asc');
        expect(out.map((f) => f.ejercicio_nombre)).toEqual([
            'Aperturas',
            'Dominadas',
            'Press de banca',
        ]);
    });

    it('ordena por peso descendente', () => {
        const out = ordenarHistorial(filas, 'peso_desc');
        expect(out.map((f) => f.id)).toEqual([1, 2, 3]);
    });

    it('ordena por fecha descendente por default', () => {
        const out = ordenarHistorial(filas, 'fecha_desc');
        expect(out.map((f) => f.id)).toEqual([1, 2, 3]);
    });

    it('no muta el array original', () => {
        const original = [...filas];
        filtrarHistorial(filas, { orden: 'volumen_desc' });
        expect(filas).toEqual(original);
    });

    it('cuenta las series por día con clave YYYY-MM-DD', () => {
        const mapa = contarSeriesPorFecha(filas);
        expect(mapa['2026-10-07']).toBe(1);
        expect(mapa['2026-09-15']).toBe(1);
        expect(mapa['2026-08-01']).toBe(1);
    });

    it('la clave del calendario matchea el ISO completo de la API', () => {
        // El calendario consulta por 'YYYY-MM-DD': si la clave fuera el ISO
        // completo, el heatmap quedaría en cero.
        const mapa = contarSeriesPorFecha(filas);
        expect(mapa[filas[0].fecha]).toBeUndefined();
        expect(mapa['2026-10-07']).toBe(1);
    });
});
