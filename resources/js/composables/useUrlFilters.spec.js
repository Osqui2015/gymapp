import { describe, it, expect, beforeEach } from 'vitest';
import { useUrlFilters, useHistorialFilters } from './useUrlFilters';

describe('useUrlFilters', () => {
    beforeEach(() => {
        // limpiar la URL antes de cada test
        window.history.replaceState({}, '', '/');
    });

    it('get() devuelve null si la key no existe', () => {
        const { get } = useUrlFilters();
        expect(get('foo')).toBeNull();
    });

    it('set() y get() roundtrip funciona', () => {
        const { set, get } = useUrlFilters();
        set('alumno_id', 42);
        expect(get('alumno_id')).toBe(42);
    });

    it('set() con string funciona', () => {
        const { set, get } = useUrlFilters();
        set('name', 'oscar');
        expect(get('name')).toBe('oscar');
    });

    it('set() con boolean funciona', () => {
        const { set, get } = useUrlFilters();
        set('active', true);
        expect(get('active')).toBe(true);
    });

    it('set() borra la key si el valor es null/undefined/empty', () => {
        const { set, get } = useUrlFilters();
        set('foo', 'bar');
        expect(get('foo')).toBe('bar');
        set('foo', null);
        expect(get('foo')).toBeNull();
    });

    it('remove() borra la key', () => {
        const { set, get, remove } = useUrlFilters();
        set('foo', 'bar');
        remove('foo');
        expect(get('foo')).toBeNull();
    });

    it('clear() borra todos los params', () => {
        const { set, clear, getAll } = useUrlFilters();
        set('a', 1);
        set('b', 2);
        clear();
        expect(getAll()).toEqual({});
    });

    it('namespace aísla los filtros', () => {
        const a = useUrlFilters('a');
        const b = useUrlFilters('b');
        a.set('foo', 'fromA');
        b.set('foo', 'fromB');
        expect(a.get('foo')).toBe('fromA');
        expect(b.get('foo')).toBe('fromB');
    });

    it('preserva otros params de la URL al modificar', () => {
        window.history.replaceState({}, '', '/?other=value');
        const { set, get } = useUrlFilters();
        set('foo', 'bar');
        expect(window.location.search).toContain('other=value');
        expect(window.location.search).toContain('foo=bar');
        expect(get('foo')).toBe('bar');
    });

    it('no recarga la página al modificar (replaceState)', () => {
        const replaceStateSpy = vi.spyOn(window.history, 'replaceState');

        const { set } = useUrlFilters();
        set('foo', 'bar');

        expect(replaceStateSpy).toHaveBeenCalled();
        expect(window.location.search).toContain('foo=bar');
        replaceStateSpy.mockRestore();
    });
});

describe('useHistorialFilters', () => {
    beforeEach(() => {
        window.history.replaceState({}, '', '/');
    });

    it('inicia con valores vacíos', () => {
        const { state } = useHistorialFilters();
        expect(state.value).toEqual({
            alumno_id: null,
            from: null,
            to: null,
            rutina: null,
            ejercicio: null,
        });
    });

    it('carga valores desde la URL', () => {
        window.history.replaceState({}, '', '/?h_alumno_id=42&h_from=2026-01-01');
        const { state } = useHistorialFilters();
        expect(state.value.alumno_id).toBe(42);
        expect(state.value.from).toBe('2026-01-01');
    });

    it('reset() limpia state y URL', () => {
        window.history.replaceState({}, '', '/?h_alumno_id=42');
        const { state, reset } = useHistorialFilters();
        expect(state.value.alumno_id).toBe(42);
        reset();
        expect(state.value.alumno_id).toBeNull();
        expect(window.location.search).toBe('');
    });

    it('buildQueryParams() filtra valores nulos', () => {
        const { state, buildQueryParams } = useHistorialFilters();
        state.value = { alumno_id: 5, from: null, to: '', rutina: 'Push', ejercicio: null };
        expect(buildQueryParams()).toEqual({ alumno_id: 5, rutina: 'Push' });
    });
});
