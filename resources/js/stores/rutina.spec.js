import { describe, it, expect, beforeEach } from 'vitest';
import { useRutinaStore } from './rutina';

describe('useRutinaStore', () => {
    let rutina;

    beforeEach(() => {
        // Limpia localStorage entre tests (también lo hace test-setup.js,
        // pero dejarlo explícito acá para legibilidad)
        localStorage.clear();
        rutina = useRutinaStore();
    });

    it('inicia con valores por defecto', () => {
        expect(rutina.seleccionada).toBeNull();
        expect(rutina.diaActual).toBe('Día 1');
        expect(rutina.creada).toBeNull();
    });

    describe('seleccionar()', () => {
        it('setea la rutina seleccionada y resetea el día a "Día 1"', () => {
            rutina.seleccionar('Intermedio', 'Lunes, Miércoles');
            expect(rutina.seleccionada).toEqual({ nivel: 'Intermedio', dias: 'Lunes, Miércoles' });
            expect(rutina.diaActual).toBe('Día 1');
        });

        it('persiste en localStorage', () => {
            rutina.seleccionar('Avanzado', 'Todos los días');
            const stored = JSON.parse(localStorage.getItem('rutina_seleccionada'));
            expect(stored).toEqual({ nivel: 'Avanzado', dias: 'Todos los días' });
            expect(localStorage.getItem('rutina_dia')).toBe('Día 1');
        });
    });

    describe('limpiar()', () => {
        it('limpia la rutina seleccionada y resetea el día', () => {
            rutina.seleccionar('Intermedio', 'L M V');
            rutina.diaActual = 'Día 3';
            rutina.limpiar();
            expect(rutina.seleccionada).toBeNull();
            expect(rutina.diaActual).toBe('Día 1');
            expect(rutina.creada).toBeNull();
        });

        it('limpia localStorage', () => {
            rutina.seleccionar('X', 'Y');
            expect(localStorage.getItem('rutina_seleccionada')).not.toBeNull();
            rutina.limpiar();
            expect(localStorage.getItem('rutina_seleccionada')).toBeNull();
            expect(localStorage.getItem('rutina_dia')).toBeNull();
        });
    });

    describe('setDiaActual()', () => {
        it('actualiza el día y persiste en localStorage', () => {
            rutina.seleccionar('Intermedio', 'L M V');
            rutina.setDiaActual('Día 3');
            expect(rutina.diaActual).toBe('Día 3');
            expect(localStorage.getItem('rutina_dia')).toBe('Día 3');
        });
    });

    describe('crearRutina() / eliminarRutina()', () => {
        it('crearRutina guarda una rutina con id, nombre y ejercicios vacíos', () => {
            rutina.crearRutina('Mi rutina custom');
            expect(rutina.creada).toBeTruthy();
            expect(rutina.creada.nombre).toBe('Mi rutina custom');
            expect(rutina.creada.ejercicios).toEqual([]);
            expect(typeof rutina.creada.id).toBe('number');
        });

        it('eliminarRutina limpia la rutina creada', () => {
            rutina.crearRutina('X');
            rutina.eliminarRutina();
            expect(rutina.creada).toBeNull();
        });
    });

    describe('hidratar()', () => {
        it('lee del localStorage y popula el state', () => {
            // Simular que ya hay datos guardados
            localStorage.setItem('rutina_seleccionada', JSON.stringify({ nivel: 'Persistido', dias: 'L X' }));
            localStorage.setItem('rutina_dia', 'Día 5');

            rutina.hidratar();
            expect(rutina.seleccionada).toEqual({ nivel: 'Persistido', dias: 'L X' });
            expect(rutina.diaActual).toBe('Día 5');
        });

        it('no rompe si localStorage está vacío', () => {
            rutina.hidratar();
            expect(rutina.seleccionada).toBeNull();
            expect(rutina.diaActual).toBe('Día 1'); // fallback al default
        });

        it('no rompe si el JSON está malformado', () => {
            localStorage.setItem('rutina_seleccionada', '{esto no es JSON válido');
            // No debe tirar, debe caer a los defaults silenciosamente
            expect(() => rutina.hidratar()).not.toThrow();
            expect(rutina.seleccionada).toBeNull();
        });
    });

    describe('persiste correctamente a través de hidratar()', () => {
        it('round-trip: seleccionar → hidratar en store nuevo', () => {
            rutina.seleccionar('Round-trip', 'M J');
            rutina.setDiaActual('Día 2');

            // Simular "recarga": nuevo store, mismos datos en localStorage
            const rutina2 = useRutinaStore();
            rutina2.hidratar();

            expect(rutina2.seleccionada).toEqual({ nivel: 'Round-trip', dias: 'M J' });
            expect(rutina2.diaActual).toBe('Día 2');
        });
    });
});
