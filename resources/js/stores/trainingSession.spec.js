import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useTrainingSessionStore } from './trainingSession';

describe('useTrainingSessionStore', () => {
    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
    });

    it('arranca sin sesion activa', () => {
        const store = useTrainingSessionStore();
        expect(store.isActive).toBe(false);
        expect(store.currentEjercicio).toBeNull();
    });

    it('start() crea una sesion activa con ejercicios', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Full Body A',
            dia: 'Lunes',
            ejercicios: [
                { nombre: 'Sentadilla', series_objetivo: 3 },
                { nombre: 'Press Banca', series_objetivo: 4 },
            ],
        });

        expect(store.isActive).toBe(true);
        expect(store.session.rutina_nombre).toBe('Full Body A');
        expect(store.session.ejercicios).toHaveLength(2);
        expect(store.currentEjercicio.nombre).toBe('Sentadilla');
    });

    it('persiste en localStorage inmediatamente', async () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Push',
            dia: 'Martes',
            ejercicios: [{ nombre: 'Press', series_objetivo: 3 }],
        });

        // Esperar a que el watcher (deep + sync) escriba.
        await new Promise((r) => setTimeout(r, 0));

        const raw = localStorage.getItem('gymapp:training-session:v1');
        expect(raw).toBeTruthy();
        const parsed = JSON.parse(raw);
        expect(parsed.rutina_nombre).toBe('Push');
    });

    it('completeCurrentSerie avanza el contador y pasa al siguiente ejercicio', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Full Body A',
            dia: 'Lunes',
            ejercicios: [
                { nombre: 'Sentadilla', series_objetivo: 3 },
                { nombre: 'Press', series_objetivo: 2 },
            ],
        });

        // Sentadilla serie 1, 2, 3 → deberia pasar a Press
        store.completeCurrentSerie(); // 1/3
        expect(store.currentEjercicio.nombre).toBe('Sentadilla');
        expect(store.currentEjercicio.series_completadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);

        store.completeCurrentSerie(); // 2/3
        store.completeCurrentSerie(); // 3/3 → completado, salta a Press
        expect(store.currentEjercicio.nombre).toBe('Press');
        expect(store.currentEjercicio.series_completadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
    });

    it('end() cierra la sesion y limpia localStorage', async () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'X',
            dia: 'Y',
            ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
        });
        await new Promise((r) => setTimeout(r, 0));
        expect(localStorage.getItem('gymapp:training-session:v1')).toBeTruthy();

        store.end();
        await new Promise((r) => setTimeout(r, 0));

        expect(store.isActive).toBe(false);
        expect(localStorage.getItem('gymapp:training-session:v1')).toBeNull();
    });

    it('restaura sesion desde localStorage al re-instanciar', () => {
        // Primer store: crea sesion
        const store1 = useTrainingSessionStore();
        store1.start({
            rutina_nombre: 'Persiste',
            dia: 'Miercoles',
            ejercicios: [{ nombre: 'Remo', series_objetivo: 3 }],
        });
        // Asegurarse de que el watcher escribio
        return new Promise((resolve) => {
            setTimeout(() => {
                // Segundo store en la misma Pinia (o nueva) — debe leer localStorage
                setActivePinia(createPinia());
                const store2 = useTrainingSessionStore();
                expect(store2.isActive).toBe(true);
                expect(store2.session.rutina_nombre).toBe('Persiste');
                resolve();
            }, 0);
        });
    });

    it('no restaura sesiones con endedAt (cerradas)', () => {
        localStorage.setItem(
            'gymapp:training-session:v1',
            JSON.stringify({
                id: 'old',
                startedAt: '2026-01-01T00:00:00.000Z',
                endedAt: '2026-01-01T01:00:00.000Z',
                rutina_nombre: 'Vieja',
                dia: 'Ayer',
                ejercicios: [],
            })
        );

        const store = useTrainingSessionStore();
        expect(store.isActive).toBe(false);
    });

    it('discard() borra todo sin dejar rastro', async () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'X',
            dia: 'Y',
            ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
        });
        await new Promise((r) => setTimeout(r, 0));

        store.discard();
        await new Promise((r) => setTimeout(r, 0));

        expect(store.isActive).toBe(false);
        expect(localStorage.getItem('gymapp:training-session:v1')).toBeNull();
    });

    it('recordSet guarda atributos avanzados, calcula volumen y permite deshacer con undoLastSet', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Fuerza',
            dia: 'Día 1',
            ejercicios: [
                { nombre: 'Sentadilla', series_objetivo: 3 },
                { nombre: 'Press Banca', series_objetivo: 2 },
            ],
        });

        expect(store.canUndo).toBe(false);
        expect(store.volumenTotal).toBe(0);

        // Registrar set 1: 100kg x 5 (volumen = 500)
        store.recordSet({
            peso: 100,
            reps: 5,
            tipo_serie: 'calentamiento',
            esfuerzo_tipo: 'rir',
            esfuerzo_valor: 3,
        });

        expect(store.volumenTotal).toBe(500);
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.canUndo).toBe(true);
        expect(store.session.currentSerieNumero).toBe(2);

        // Registrar set 2: 120kg x 5 (volumen = 600, total = 1100)
        store.recordSet({
            peso: 120,
            reps: 5,
            tipo_serie: 'efectiva',
            esfuerzo_tipo: 'rir',
            esfuerzo_valor: 1,
        });

        expect(store.volumenTotal).toBe(1100);
        expect(store.totalSeriesCompletadas).toBe(2);

        // Deshacer el set 2
        const undone = store.undoLastSet();
        expect(undone.peso).toBe(120);
        expect(store.volumenTotal).toBe(500);
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
    });

    it('pause y resume controlan el estado isPaused', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Fuerza',
            dia: 'Día 1',
            ejercicios: [{ nombre: 'Press', series_objetivo: 3 }],
        });

        expect(store.isPaused).toBe(false);
        store.pause();
        expect(store.isPaused).toBe(true);
        store.resume();
        expect(store.isPaused).toBe(false);
    });
});
