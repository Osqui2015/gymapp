import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import ActiveWorkoutModal from './ActiveWorkoutModal.vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import { useRestTimerStore } from '@/stores/restTimer';

// Mock useOfflineSeries
vi.mock('@/composables/useOfflineSeries', () => ({
    useOfflineSeries: () => ({
        recordSet: vi.fn().mockResolvedValue({ status: 'sent' }),
    }),
}));

// Mock useWakeLock
vi.mock('@/composables/useWakeLock', () => ({
    useWakeLock: () => ({
        supported: true,
        active: false,
        requestWakeLock: vi.fn(),
        releaseWakeLock: vi.fn(),
    }),
}));

// Mock axios para que la seccion "Vista del ejercicio" renderice con media fake.
vi.mock('axios', () => {
    return {
        default: {
            get: vi.fn((url) => {
                if (String(url).includes('/api/ejercicios/media')) {
                    return Promise.resolve({
                        data: {
                            gif_url: 'https://example.com/demo.gif',
                            image_url: 'https://example.com/demo.png',
                        },
                    });
                }
                if (String(url).includes('/api/historial/ultimo')) {
                    return Promise.resolve({ data: { encontrado: false } });
                }
                return Promise.resolve({ data: {} });
            }),
        },
    };
});

describe('ActiveWorkoutModal', () => {
    let store;

    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
        store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Torso Pierna',
            dia: 'Día 1',
            ejercicios: [
                {
                    nombre: 'Press Banca',
                    series_objetivo: 3,
                    reps_min: '8',
                    reps_max: '10',
                    descanso_min: 2,
                },
                {
                    nombre: 'Remo con Barra',
                    series_objetivo: 3,
                    reps_min: '8',
                    reps_max: '10',
                    descanso_min: 1.5,
                },
            ],
        });
    });

    it('renderiza cuando open es true y muestra el ejercicio actual', () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        expect(wrapper.text()).toContain('Press Banca');
        expect(wrapper.text()).toContain('Torso Pierna');
        expect(wrapper.text()).toContain('Día 1');
        expect(wrapper.text()).toContain('COMPLETAR SERIE #1');
        // Pill con el total de series del ejercicio (Press Banca tiene 3).
        expect(wrapper.text()).toContain('3 series');
    });

    it('el ojito oculta y muestra la vista del ejercicio', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // Esperar a que el watch inicial con `immediate: true` cargue la
        // media del ejercicio (la seccion que contiene el toggle solo
        // aparece cuando hay gif o imagen).
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const toggle = wrapper.find('[data-testid="toggle-exercise-view"]');
        expect(toggle.exists()).toBe(true);

        // Por defecto, la vista esta visible.
        expect(wrapper.find('[data-testid="exercise-media"]').exists()).toBe(true);
        expect(toggle.attributes('aria-pressed')).toBe('false');

        // Click -> oculta.
        await toggle.trigger('click');
        expect(toggle.attributes('aria-pressed')).toBe('true');
        expect(wrapper.find('[data-testid="exercise-media"]').exists()).toBe(true);
        // ...pero la imagen dentro del bloque desaparece.
        expect(
            wrapper.find('[data-testid="exercise-media"] img').exists()
        ).toBe(false);

        // Click otra vez -> vuelve a mostrarse.
        await toggle.trigger('click');
        expect(toggle.attributes('aria-pressed')).toBe('false');
        expect(
            wrapper.find('[data-testid="exercise-media"] img').exists()
        ).toBe(true);
    });

    it('no renderiza contenido cuando open es false', () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: false },
        });

        expect(wrapper.find('header').exists()).toBe(false);
    });

    it('emite minimize al tocar el botón de minimizar', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const btnMinimize = wrapper.find('button[aria-label="Minimizar sesión"]');
        await btnMinimize.trigger('click');

        expect(wrapper.emitted('minimize')).toBeTruthy();
    });

    it('emite finish al presionar Finalizar', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const btnFinalizar = wrapper.findAll('button').find((b) => b.text() === 'Finalizar');
        expect(btnFinalizar).toBeDefined();
        await btnFinalizar.trigger('click');

        expect(wrapper.emitted('finish')).toBeTruthy();
    });

    it('permite completar serie y avanzar al siguiente set', async () => {
        const restStore = useRestTimerStore();
        const startSpy = vi.spyOn(restStore, 'start');

        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // Kinetic Obsidian: el botón COMPLETAR SERIE ahora usa shadow-violet-glow
        const btnCompletar = wrapper.findAll('button').find((b) =>
            b.text().includes('COMPLETAR SERIE')
        );
        expect(btnCompletar).toBeDefined();

        await btnCompletar.trigger('click');

        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(startSpy).toHaveBeenCalledWith(120, 'Press Banca'); // 2 min = 120s
    });

    it('las series de calentamiento son separadas y no cuentan para el progreso', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // 1) Cambiar tipo a calentamiento.
        const btnCalentamiento = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Calentamiento');
        expect(btnCalentamiento).toBeDefined();
        await btnCalentamiento.trigger('click');

        // Header de la card y boton ahora deben mostrar Calentamiento #1.
        // (Con el refactor a SetConfigCard, el texto "Configurar Calentamiento"
        // lo emite la card directamente como "Calentamiento #N".)
        expect(wrapper.text()).toContain('Calentamiento #1');
        expect(wrapper.text()).toContain('COMPLETAR CALENTAMIENTO #1');

        // 2) Completar la primera serie de calentamiento.
        const btnCompletarWarmup = wrapper
            .findAll('button')
            .find((b) => b.text().includes('COMPLETAR CALENTAMIENTO'));
        await btnCompletarWarmup.trigger('click');

        // Progreso global sigue en 0/6 (3 series por ejercicio × 2 ejercicios)
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.session.currentCalentamientoNumero).toBe(2);
        // El set queda registrado con su propio numero.
        expect(store.currentEjercicio.sets[0].tipo_serie).toBe('calentamiento');
        expect(store.currentEjercicio.sets[0].series_numero).toBe(1);

        // 3) Volver a tipo efectiva y completar la primera serie de trabajo.
        const btnEfectiva = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Efectiva');
        await btnEfectiva.trigger('click');

        const btnCompletarEfectiva = wrapper
            .findAll('button')
            .find((b) => b.text().includes('COMPLETAR SERIE'));
        await btnCompletarEfectiva.trigger('click');

        // Ahora si avanza el progreso y el contador de calentamiento se reseteo.
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(store.session.currentCalentamientoNumero).toBe(1);
    });

    it('muestra el boton FINALIZAR SESION cuando todos los ejercicios estan completos', async () => {
        // Forzamos una sesion minima para poder terminarla rapido.
        store.discard();
        store.start({
            rutina_nombre: 'Mini',
            dia: 'Día 1',
            ejercicios: [{ nombre: 'Press Banca', series_objetivo: 2 }],
        });

        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // Mientras no este todo completo, debe verse COMPLETAR SERIE y NO
        // el boton de finalizar.
        expect(wrapper.text()).toContain('COMPLETAR SERIE #1');
        expect(wrapper.find('[data-testid="btn-finalizar-sesion"]').exists()).toBe(false);

        // Completar las dos series del unico ejercicio.
        const completar = () =>
            wrapper.findAll('button').find((b) => b.text().includes('COMPLETAR SERIE'));
        await completar().trigger('click');
        await completar().trigger('click');

        // Ahora la sesion esta completa y debe aparecer el boton verde.
        expect(store.isSessionComplete).toBe(true);
        expect(wrapper.text()).toContain('FINALIZAR SESIÓN');
        expect(wrapper.text()).not.toContain('COMPLETAR SERIE #');
        const btnFinalizar = wrapper.find('[data-testid="btn-finalizar-sesion"]');
        expect(btnFinalizar.exists()).toBe(true);

        // Al tocar FINALIZAR se emite el evento 'finish' (el padre abre el
        // resumen / cierra la sesion).
        await btnFinalizar.trigger('click');
        expect(wrapper.emitted('finish')).toBeTruthy();
    });

    describe('Superseries', () => {
        const startSupersetSession = () => {
            store.discard();
            store.start({
                rutina_nombre: 'Torso A',
                dia: 'Día 1',
                ejercicios: [
                    {
                        nombre: 'Press de banca',
                        series_objetivo: 3,
                        reps_min: '8',
                        reps_max: '10',
                        descanso_min: 1.5,
                        superserie_grupo: 1,
                    },
                    {
                        nombre: 'Aperturas en polea',
                        series_objetivo: 3,
                        reps_min: '10',
                        reps_max: '12',
                        descanso_min: 1.5,
                        superserie_grupo: 1,
                    },
                    {
                        nombre: 'Remo con barra',
                        series_objetivo: 3,
                        reps_min: '8',
                        reps_max: '10',
                        descanso_min: 1.5,
                    },
                ],
            });
        };

        it('muestra el banner y dos cards cuando el ejercicio activo tiene compañero de superset', async () => {
            startSupersetSession();
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // Banner de superset arriba
            expect(wrapper.find('[data-testid="superset-banner"]').exists()).toBe(true);
            // Grid con dos cards
            expect(wrapper.find('[data-testid="superset-grid"]').exists()).toBe(true);
            // Bloque de superserie en la columna izquierda con los DOS nombres
            // en orden primero (current) → segundo (partner)
            expect(
                wrapper.find('[data-testid="superset-left-block"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="superset-left-first-0"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="superset-left-second-1"]').exists()
            ).toBe(true);
            // Banner debe indicar el orden correcto cuando estamos en el PRIMERO
            expect(wrapper.text()).toContain(
                'Hacé'
            );
            expect(wrapper.text()).toContain(
                'y seguí directo con'
            );
            // Ambos nombres presentes en pantalla (banner + bloque izq + headers de cards)
            expect(wrapper.text()).toContain('Press de banca');
            expect(wrapper.text()).toContain('Aperturas en polea');
            // Headers de nombre en AMBAS cards (clave para no perder de vista
            // qué ejercicio se está registrando sin mirar la columna izq)
            expect(
                wrapper.find('[data-testid="set-card-name-0"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="set-card-name-1"]').exists()
            ).toBe(true);
            // Ambos botones de completar serie existen
            expect(
                wrapper.find('[data-testid="btn-completar-0"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="btn-completar-1"]').exists()
            ).toBe(true);
        });

        it('cuando estamos en el SEGUNDO del par, el banner refleja el orden real (ya hiciste el primero)', async () => {
            startSupersetSession();
            // Nos paramos en el SEGUNDO del par (Aperturas, índice 1)
            store.session.currentEjercicioIndex = 1;
            await new Promise((r) => setTimeout(r, 0));
            await Promise.resolve();

            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // El bloque izq ahora debe mostrar el primero como "Ya hiciste"
            // (con tachado) y el segundo como "Ahora" (resaltado).
            expect(wrapper.text()).toContain('Ya hiciste');
            expect(wrapper.text()).toContain('Ahora');

            // El banner debe decir algo tipo "Hacé Aperturas — ya hiciste Press
            // de banca antes, seguí sin descansar".
            expect(wrapper.text()).toContain('ya hiciste');
            expect(wrapper.text()).toContain('antes');
        });

        it('registrar serie en el partner de superset NO avanza currentEjercicioIndex', async () => {
            startSupersetSession();
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // Marcar serie en el partner (índice 1)
            const btnPartner = wrapper.find('[data-testid="btn-completar-1"]');
            expect(btnPartner.exists()).toBe(true);
            await btnPartner.trigger('click');

            // currentEjercicioIndex sigue siendo 0 (Press de banca)
            expect(store.session.currentEjercicioIndex).toBe(0);
            // Pero el partner (Aperturas) ya tiene su primer set registrado
            expect(store.session.ejercicios[1].sets.length).toBe(1);
            expect(store.session.ejercicios[1].sets[0].series_numero).toBe(1);
        });

        it('el header "EJERCICIO X DE Y" cuenta superseries como 1 unidad', async () => {
            startSupersetSession();
            // Sesión de 3 ejercicios donde 2 son superserie → 2 unidades lógicas
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            const pill = wrapper.find('[data-testid="ejercicio-progress-pill"]');
            expect(pill.exists()).toBe(true);
            // Como las superseries colapsan a 1 unidad, el header muestra
            // "EJERCICIO 1 DE 2" (sin sufijo "· 3 ej" porque sería redundante
            // una vez que la unidad ya está colapsada).
            expect(pill.text()).toContain('EJERCICIO 1 DE 2');
            expect(pill.text()).not.toContain('ej');
        });

        it('las flechas de "Vista del ejercicio" permiten alternar current ↔ partner', async () => {
            startSupersetSession();
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // Esperar a que cargue la media (mock async) antes de chequear.
            await new Promise((r) => setTimeout(r, 0));
            await wrapper.vm.$nextTick();

            // Inicialmente solo se muestra la flecha "siguiente" (porque estamos viendo el current)
            expect(wrapper.find('[data-testid="media-next"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="media-prev"]').exists()).toBe(false);

            // Click en la flecha → ahora vemos el partner
            await wrapper.find('[data-testid="media-next"]').trigger('click');

            // Aparece la flecha "anterior" y se oculta la "siguiente"
            expect(wrapper.find('[data-testid="media-prev"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="media-next"]').exists()).toBe(false);

            // El header "Vista: ..." ahora muestra el nombre del compañero
            const mediaSection = wrapper.find('[data-testid="exercise-media"]');
            expect(mediaSection.text()).toContain('Aperturas en polea');

            // Click en la flecha "anterior" → volvemos al current
            await wrapper.find('[data-testid="media-prev"]').trigger('click');
            expect(wrapper.find('[data-testid="media-next"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="media-prev"]').exists()).toBe(false);
            expect(
                wrapper.find('[data-testid="exercise-media"]').text()
            ).toContain('Press de banca');
        });

        it('NO muestra banner ni grid cuando el ejercicio activo NO es parte de superset', async () => {
            startSupersetSession();
            // Avanzamos al tercer ejercicio (Remo) que NO es superset
            store.session.currentEjercicioIndex = 2;
            await new Promise((r) => setTimeout(r, 0));
            await Promise.resolve();

            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            expect(wrapper.find('[data-testid="superset-banner"]').exists()).toBe(false);
            expect(wrapper.find('[data-testid="superset-grid"]').exists()).toBe(false);
            expect(
                wrapper.find('[data-testid="superset-left-block"]').exists()
            ).toBe(false);
            // Solo hay un card de completar serie
            expect(
                wrapper.find('[data-testid="btn-completar-2"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="btn-completar-1"]').exists()
            ).toBe(false);
            // Y como hay una sola card (no superset), NO debe mostrar el
            // header de nombre adentro (ya está en la columna izquierda).
            expect(
                wrapper.find('[data-testid="set-card-name-2"]').exists()
            ).toBe(false);
            // Sin superset → no hay flechas para alternar media
            expect(wrapper.find('[data-testid="media-prev"]').exists()).toBe(false);
            expect(wrapper.find('[data-testid="media-next"]').exists()).toBe(false);
        });
    });
});
