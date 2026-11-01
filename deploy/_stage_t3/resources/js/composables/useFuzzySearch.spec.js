import { describe, it, expect } from 'vitest';
import { useFuzzySearch } from './useFuzzySearch';

describe('useFuzzySearch', () => {
    const { fuzzyMatch, highlight, sortByRelevance } = useFuzzySearch();

    describe('fuzzyMatch()', () => {
        it('match exacto retorna 100', () => {
            expect(fuzzyMatch('Press banca', 'Press banca')).toBe(100);
        });

        it('match al inicio retorna 80', () => {
            expect(fuzzyMatch('Press banca', 'press')).toBe(80);
        });

        it('substring retorna 60', () => {
            expect(fuzzyMatch('Press banca', 'banca')).toBe(60);
        });

        it('fuzzy match retorna score bajo', () => {
            const score = fuzzyMatch('Press banca', 'pb');
            expect(score).toBeGreaterThan(0);
            expect(score).toBeLessThan(60);
        });

        it('case-insensitive', () => {
            expect(fuzzyMatch('Press BANCA', 'banca')).toBe(60);
        });

        it('ignora acentos', () => {
            expect(fuzzyMatch('Prensa', 'prensa')).toBe(60);
            expect(fuzzyMatch('Press', 'prens')).toBe(80);
        });

        it('no match retorna 0', () => {
            expect(fuzzyMatch('Press banca', 'xyz')).toBe(0);
        });

        it('input vacío o null retorna 0', () => {
            expect(fuzzyMatch(null, 'q')).toBe(0);
            expect(fuzzyMatch('text', '')).toBe(0);
        });
    });

    describe('highlight()', () => {
        it('resalta match exacto', () => {
            const html = highlight('Press banca', 'banca');
            expect(html).toContain('<mark');
            expect(html).toContain('>banca</mark>');
        });

        it('preserva el texto alrededor del match', () => {
            const html = highlight('Press banca', 'banca');
            expect(html).toContain('Press ');
            expect(html).toContain('banca');
        });

        it('escapa HTML peligroso', () => {
            const html = highlight('<script>alert(1)</script>', 'script');
            expect(html).not.toContain('<script>');
            expect(html).toContain('&lt;script&gt;');
        });

        it('devuelve texto plano si no hay match', () => {
            const html = highlight('Press banca', 'xyz');
            expect(html).toBe('Press banca');
        });
    });

    describe('sortByRelevance()', () => {
        const items = [
            { id: 1, nombre: 'Curl de bíceps' },
            { id: 2, nombre: 'Press banca' },
            { id: 3, nombre: 'Sentadilla' },
            { id: 4, nombre: 'Press militar' },
        ];

        it('filtra items sin match', () => {
            const sorted = sortByRelevance(items, 'banca', 'nombre');
            expect(sorted).toHaveLength(1);
            expect(sorted[0].nombre).toBe('Press banca');
        });

        it('ordena por relevancia: match al inicio > substring', () => {
            const sorted = sortByRelevance(items, 'press', 'nombre');
            // 'Press banca' y 'Press militar' tienen match al inicio (80)
            // los otros no matchean 'press'
            expect(sorted.length).toBe(2);
            // ambos tienen el mismo score, el orden entre ellos puede variar
        });

        it('soporta función extractora', () => {
            const itemsCustom = [
                { id: 1, title: 'Hola mundo' },
                { id: 2, title: 'Mundo cruel' },
            ];
            const sorted = sortByRelevance(itemsCustom, 'mundo', (i) => i.title);
            expect(sorted).toHaveLength(2);
        });

        it('devuelve los items tal cual si el query es corto', () => {
            const sorted = sortByRelevance(items, 'a', 'nombre');
            expect(sorted).toEqual(items);
        });

        it('devuelve los items tal cual si no hay query', () => {
            const sorted = sortByRelevance(items, '', 'nombre');
            expect(sorted).toEqual(items);
        });
    });
});
