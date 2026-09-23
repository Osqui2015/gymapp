import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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

        // Set 1: calentamiento 100kg x 5.
        // El calentamiento se guarda pero NO avanza el contador de series
        // de trabajo: el progreso global queda en 0/5 y la proxima efectiva
        // sigue siendo "Serie #1".
        store.recordSet({
            peso: 100,
            reps: 5,
            tipo_serie: 'calentamiento',
            esfuerzo_tipo: 'rir',
            esfuerzo_valor: 3,
        });

        expect(store.volumenTotal).toBe(500);
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.session.currentCalentamientoNumero).toBe(2);
        expect(store.canUndo).toBe(true);

        // Set 2: efectiva 120kg x 5. Ahora si avanza el contador de trabajo.
        store.recordSet({
            peso: 120,
            reps: 5,
            tipo_serie: 'efectiva',
            esfuerzo_tipo: 'rir',
            esfuerzo_valor: 1,
        });

        expect(store.volumenTotal).toBe(1100);
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        // Al registrar una efectiva, el contador de calentamiento se resetea.
        expect(store.session.currentCalentamientoNumero).toBe(1);

        // Deshacer la efectiva
        const undone = store.undoLastSet();
        expect(undone.peso).toBe(120);
        expect(store.volumenTotal).toBe(500);
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
        // La deshacer restaura el cursor de calentamiento al momento previo.
        expect(store.session.currentCalentamientoNumero).toBe(2);
    });

    it('las series de calentamiento tienen su propio contador y no afectan el progreso', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Torso',
            dia: 'Día 1',
            ejercicios: [{ nombre: 'Press de banca', series_objetivo: 3 }],
        });

        // Calentamiento 1
        store.recordSet({ peso: 20, reps: 8, tipo_serie: 'calentamiento' });
        expect(store.session.currentCalentamientoNumero).toBe(2);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.totalSeriesCompletadas).toBe(0);

        // Calentamiento 2
        store.recordSet({ peso: 30, reps: 6, tipo_serie: 'calentamiento' });
        expect(store.session.currentCalentamientoNumero).toBe(3);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.totalSeriesCompletadas).toBe(0);

        // Set efectivo 1: ahora avanza la barra de progreso.
        store.recordSet({ peso: 60, reps: 6, tipo_serie: 'efectiva' });
        expect(store.session.currentCalentamientoNumero).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(store.totalSeriesCompletadas).toBe(1);

        // Set efectivo 2.
        store.recordSet({ peso: 60, reps: 5, tipo_serie: 'efectiva' });
        expect(store.session.currentSerieNumero).toBe(3);
        expect(store.totalSeriesCompletadas).toBe(2);

        // Set efectivo 3 -> ejercicio completo, pero como es el unico no
        // hay siguiente.
        store.recordSet({ peso: 60, reps: 4, tipo_serie: 'efectiva' });
        expect(store.totalSeriesCompletadas).toBe(3);
        expect(store.currentEjercicio.completed).toBe(true);

        // Deshacer la ultima efectiva.
        store.undoLastSet();
        expect(store.totalSeriesCompletadas).toBe(2);
        expect(store.session.currentSerieNumero).toBe(3);
    });

    it('isSessionComplete refleja cuando todos los ejercicios estan terminados', () => {
        const store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Torso',
            dia: 'Día 1',
            ejercicios: [
                { nombre: 'Press de banca', series_objetivo: 2 },
                { nombre: 'Remo', series_objetivo: 2 },
            ],
        });

        expect(store.isSessionComplete).toBe(false);

        // Terminar el primer ejercicio.
        store.recordSet({ peso: 60, reps: 8, tipo_serie: 'efectiva' });
        store.recordSet({ peso: 60, reps: 8, tipo_serie: 'efectiva' });
        expect(store.currentEjercicio.nombre).toBe('Remo');
        expect(store.isSessionComplete).toBe(false);

        // Terminar el segundo ejercicio (el ultimo).
        store.recordSet({ peso: 50, reps: 10, tipo_serie: 'efectiva' });
        store.recordSet({ peso: 50, reps: 10, tipo_serie: 'efectiva' });
        expect(store.isSessionComplete).toBe(true);
        expect(store.currentEjercicio.completed).toBe(true);
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

    describe('cronometro (regresion: duracion quedaba en 0 min)', () => {
        beforeEach(() => {
            vi.useFakeTimers();
            // Fijamos un reloj base para que las diferencias de tiempo sean
            // deterministas. Luego avanzamos con vi.advanceTimersByTime.
            vi.setSystemTime(new Date('2026-09-14T20:00:00.000Z'));
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('elapsed arranca en 0 al iniciar una sesion', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'X',
                dia: 'Y',
                ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
            });
            expect(store.elapsed).toBe(0);
        });

        it('elapsed crece con el paso del tiempo (regresion: antes quedaba congelado)', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'X',
                dia: 'Y',
                ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
            });

            expect(store.elapsed).toBe(0);

            vi.advanceTimersByTime(60_000); // +60s
            expect(store.elapsed).toBe(60);

            vi.advanceTimersByTime(15 * 60_000); // +15min
            expect(store.elapsed).toBe(60 + 15 * 60);
        });

        it('elapsed se mantiene estable durante una pausa y reanuda desde donde quedo', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'X',
                dia: 'Y',
                ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
            });

            vi.advanceTimersByTime(120_000); // +2min
            expect(store.elapsed).toBe(120);

            store.pause();
            const enPausa = store.elapsed;

            vi.advanceTimersByTime(180_000); // +3min pausado
            expect(store.elapsed).toBe(enPausa);

            store.resume();
            vi.advanceTimersByTime(60_000); // +1min despues de reanudar
            // 120 inicial + 60 posterior = 180. El tiempo pausado no cuenta.
            expect(store.elapsed).toBe(180);
        });

        it('end() deja elapsed en 0 y detiene el contador', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'X',
                dia: 'Y',
                ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
            });
            vi.advanceTimersByTime(45_000);
            expect(store.elapsed).toBe(45);

            store.end();
            expect(store.elapsed).toBe(0);

            // Aunque pasen 5 minutos mas, elapsed sigue en 0.
            vi.advanceTimersByTime(5 * 60_000);
            expect(store.elapsed).toBe(0);
        });

        it('discard() deja elapsed en 0 y detiene el contador', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'X',
                dia: 'Y',
                ejercicios: [{ nombre: 'A', series_objetivo: 1 }],
            });
            vi.advanceTimersByTime(90_000);
            expect(store.elapsed).toBe(90);

            store.discard();
            expect(store.elapsed).toBe(0);
        });

        it('tras 30 min de sesion elapsed refleja los 30 min (caso de la pantalla de resumen)', () => {
            const store = useTrainingSessionStore();
            store.start({
                rutina_nombre: 'Push',
                dia: 'Día 1',
                ejercicios: [{ nombre: 'Press banca', series_objetivo: 4 }],
            });

            // 30 minutos de entrenamiento sin tocar nada.
            vi.advanceTimersByTime(30 * 60_000);

            // El snapshot que tomaria el modal de resumen debe mostrar
            // 30 min (no 0 min).
            expect(store.elapsed).toBe(30 * 60);
        });
    });
});
