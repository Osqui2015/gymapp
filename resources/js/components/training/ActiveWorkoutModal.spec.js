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
        expect(wrapper.text()).toContain('DÍA 1');
        // La única acción primaria es la CTA fija (una sola, en la zona del pulgar).
        expect(wrapper.text()).toContain('Registrar serie');
        // ...y el objetivo de la serie en curso vive en la tarjeta de objetivo.
        expect(wrapper.find('[data-testid="set-target-hero-card"]').text()).toContain(
            'Serie 1 de 3'
        );
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

        const btnFinalizar = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Finalizar sesión');
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

        // Kinetic Obsidian: la CTA primaria es única y fija al pie
        const btnCompletar = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Registrar serie'));
        expect(btnCompletar).toBeDefined();

        await btnCompletar.trigger('click');

        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(startSpy).toHaveBeenCalledWith(120, 'Press Banca'); // 2 min = 120s
    });

    it('la tira de series muestra el peso y las reps de cada serie (hechas y pendientes)', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const tracker = wrapper.find('[data-testid="sets-progress-tracker"]');
        expect(tracker.exists()).toBe(true);

        // 1) Registrar la primera serie con un peso concreto.
        const inputPeso = wrapper.find('input[type="number"][inputmode="decimal"]');
        expect(inputPeso.exists()).toBe(true);
        await inputPeso.setValue('62.5');

        const btnCompletar = wrapper.findAll('button').find((b) =>
            b.text().includes('Registrar serie')
        );
        expect(btnCompletar).toBeDefined();
        await btnCompletar.trigger('click');
        await wrapper.vm.$nextTick();

        // 2) La serie 1 (hecha) debe mostrar el peso registrado y las reps.
        const trackerAfter = wrapper.find('[data-testid="sets-progress-tracker"]');
        const textoTracker = trackerAfter.text();
        expect(textoTracker).toContain('62.5 kg');

        // 3) Las series pendientes muestran el peso sugerido (el de la última
        //    serie registrada) en gris, NO ceros.
        expect(textoTracker).not.toContain('— × —');
    });

    it('la barra sticky (mobile) registra la serie usando el payload de la card', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const sticky = wrapper.find('[data-testid="sticky-cta-completar"]');
        expect(sticky.exists()).toBe(true);
        // La copy de la CTA es fija ("una sola acción"); el número de serie en
        // curso vive en la tarjeta de objetivo de la card.
        expect(sticky.text()).toContain('Registrar serie');
        expect(wrapper.find('[data-testid="set-target-hero-card"]').text()).toContain(
            'Serie 1 de 3'
        );

        // Editar el peso en la card y registrar desde la barra sticky:
        // el peso editado tiene que llegar al store.
        const inputPeso = wrapper.find('input[type="number"][inputmode="decimal"]');
        await inputPeso.setValue('77.5');

        await sticky.trigger('click');

        expect(store.totalSeriesCompletadas).toBe(1);
        const sets = store.currentEjercicio.sets || [];
        expect(sets.length).toBe(1);
        expect(Number(sets[0].peso)).toBe(77.5);

        // Tras registrar, la tarjeta avanza a la serie 2.
        await wrapper.vm.$nextTick();
        expect(wrapper.find('[data-testid="set-target-hero-card"]').text()).toContain(
            'Serie 2 de 3'
        );
    });

    it('la tarjeta de objetivo cambia a CALENTAMIENTO cuando se selecciona ese tipo de serie', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const btnCalentamiento = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Calent.');
        expect(btnCalentamiento).toBeDefined();
        await btnCalentamiento.trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="set-target-hero-card"]').text()).toContain(
            'Calentamiento 1'
        );
    });

    it('las series de calentamiento son separadas y no cuentan para el progreso', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // 1) Cambiar tipo a calentamiento.
        const btnCalentamiento = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Calent.');
        expect(btnCalentamiento).toBeDefined();
        await btnCalentamiento.trigger('click');

        // El encabezado de la tarjeta identifica el calentamiento en curso.
        expect(wrapper.find('[data-testid="set-target-hero-card"]').text()).toContain(
            'Calentamiento 1'
        );

        // 2) Completar la primera serie de calentamiento (CTA única).
        const btnCompletarWarmup = wrapper.find('[data-testid="sticky-cta-completar"]');
        expect(btnCompletarWarmup.exists()).toBe(true);
        await btnCompletarWarmup.trigger('click');

        // Progreso global sigue en 0/6 (3 series por ejercicio × 2 ejercicios)
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.session.currentCalentamientoNumero).toBe(2);
        // El set queda registrado con su propio numero.
        expect(store.currentEjercicio.sets[0].tipo_serie).toBe('calentamiento');
        // Los calentamientos se persisten con OFFSET (51+) para no colisionar
        // con la serie efectiva N: el indice UNIQUE de `historials` no incluye
        // `tipo_serie`. Ver utils/seriesNumero.js.
        expect(store.currentEjercicio.sets[0].series_numero).toBe(51);

        // 3) Volver a tipo efectiva y completar la primera serie de trabajo.
        const btnEfectiva = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Efectiva');
        await btnEfectiva.trigger('click');

        const btnCompletarEfectiva = wrapper.find('[data-testid="sticky-cta-completar"]');
        await btnCompletarEfectiva.trigger('click');

        // Ahora si avanza el progreso y el contador de calentamiento se reseteo.
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(store.session.currentCalentamientoNumero).toBe(1);

        // 4) El warmup y la serie efectiva NO pueden compartir `series_numero`:
        //    la clave UNIQUE de `historials` no incluye `tipo_serie`, asi que
        //    si coincidieran el warmup se pisaria con la efectiva.
        expect(store.currentEjercicio.sets[0].series_numero).not.toBe(
            store.currentEjercicio.sets[1].series_numero
        );
        expect(store.currentEjercicio.sets[1].series_numero).toBe(1);
    });

    it('un bloque sin series (movilidad) se cierra con el warm-up y avanza al siguiente ejercicio', async () => {
        // Sesion con un bloque informativo (series_objetivo = 0) al inicio,
        // como el bloque de movilidad del dia 1 de la V3.
        store.discard();
        store.start({
            rutina_nombre: 'Torso',
            dia: 'Día 1',
            ejercicios: [
                {
                    nombre: 'Movilidad articular y activación',
                    series_objetivo: 0,
                    reps_min: '1',
                    reps_max: '1',
                    descanso_min: 0,
                    notas: 'Bloque de movilidad de 5 a 10 minutos, no es una serie de trabajo.',
                },
                {
                    nombre: 'Press Banca',
                    series_objetivo: 3,
                    reps_min: '8',
                    reps_max: '10',
                    descanso_min: 2,
                },
            ],
        });

        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // El bloque no muestra "Serie #X de Y" sino que se identifica como bloque.
        expect(wrapper.text()).toContain('Bloque de calentamiento');

        // Registrar el calentamiento cierra el bloque y pasa al siguiente
        // ejercicio (no queda trabado en un ejercicio que nunca "termina").
        const btnCalentamiento = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Calent.');
        expect(btnCalentamiento).toBeDefined();
        await btnCalentamiento.trigger('click');

        const btnCompletarWarmup = wrapper.find('[data-testid="sticky-cta-completar"]');
        expect(btnCompletarWarmup.exists()).toBe(true);
        await btnCompletarWarmup.trigger('click');

        expect(store.session.currentEjercicioIndex).toBe(1);
        expect(store.currentEjercicio.nombre).toBe('Press Banca');
        // El bloque no aporto series efectivas al total de la sesion.
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.totalSeriesObjetivo).toBe(3);
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

        // Mientras no este todo completo, debe verse la CTA Registrar serie y
        // NO el boton de finalizar.
        expect(wrapper.text()).toContain('Registrar serie');
        expect(wrapper.find('[data-testid="btn-finalizar-sesion"]').exists()).toBe(false);

        // Completar las dos series del unico ejercicio.
        const completar = () =>
            wrapper.findAll('button').find((b) => b.text().includes('Registrar serie'));
        await completar().trigger('click');
        await completar().trigger('click');

        // Ahora la sesion esta completa y debe aparecer el boton verde.
        expect(store.isSessionComplete).toBe(true);
        expect(wrapper.text()).toContain('FINALIZAR SESIÓN');
        expect(wrapper.find('[data-testid="sticky-cta-completar"]').exists()).toBe(false);
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
            // Banner debe indicar el orden correcto cuando estamos en el PRIMERO
            expect(wrapper.text()).toContain(
                'Hacé'
            );
            expect(wrapper.text()).toContain(
                'y seguí directo con'
            );
            // Ambos nombres presentes en pantalla (banner + headers de cards)
            expect(wrapper.text()).toContain('Press de banca');
            expect(wrapper.text()).toContain('Aperturas en polea');
            // Headers de nombre en AMBAS cards (clave para no perder de vista
            // qué ejercicio se está registrando)
            expect(
                wrapper.find('[data-testid="set-card-name-0"]').exists()
            ).toBe(true);
            expect(
                wrapper.find('[data-testid="set-card-name-1"]').exists()
            ).toBe(true);
            // En superseries existe UN SOLO botón unificado para completar la ronda
            expect(
                wrapper.find('[data-testid="btn-completar-superset"]').exists()
            ).toBe(true);
            // ...y la CTA fija normal no aparece duplicada.
            expect(
                wrapper.find('[data-testid="sticky-cta-completar"]').exists()
            ).toBe(false);
        });

        it('cuando estamos en el SEGUNDO del par, el banner refleja el orden real (ya hiciste el primero)', async () => {
            startSupersetSession();
            // Nos paramos en el SEGUNDO del par (Aperturas, índice 1)
            store.session.currentEjercicioIndex = 1;
            await new Promise((r) => setTimeout(r, 0));
            await Promise.resolve();

            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // El banner debe decir algo tipo "Hacé Aperturas — ya hiciste Press
            // de banca antes, seguí sin descansar".
            expect(wrapper.text()).toContain('ya hiciste');
            expect(wrapper.text()).toContain('antes');
        });

        it('el botón unificado de superset completa ambos ejercicios de la ronda', async () => {
            startSupersetSession();
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // Marcar serie con el botón único de superserie
            const btnSuperset = wrapper.find('[data-testid="btn-completar-superset"]');
            expect(btnSuperset.exists()).toBe(true);
            await btnSuperset.trigger('click');

            // Ambos ejercicios del par tienen su primer set registrado
            expect(store.session.ejercicios[0].sets.length).toBe(1);
            expect(store.session.ejercicios[0].sets[0].series_numero).toBe(1);
            expect(store.session.ejercicios[1].sets.length).toBe(1);
            expect(store.session.ejercicios[1].sets[0].series_numero).toBe(1);
        });

        it('muestra min y max para cada ejercicio del superset en el Hero card', async () => {
            const axiosModule = await import('axios');
            axiosModule.default.get = vi.fn().mockImplementation((url, config) => {
                const ej = config?.params?.ejercicio;
                if (ej === 'Press de banca') {
                    return Promise.resolve({
                        data: {
                            encontrado: true,
                            peso_min: 50,
                            reps_en_peso_min: 10,
                            peso_max: 70,
                            reps_en_peso_max: 8,
                            ultimo_esfuerzo: { tipo: 'rir', valor: 2 },
                        },
                    });
                }
                if (ej === 'Aperturas en polea') {
                    return Promise.resolve({
                        data: {
                            encontrado: true,
                            peso_min: 15,
                            reps_en_peso_min: 12,
                            peso_max: 25,
                            reps_en_peso_max: 10,
                            ultimo_esfuerzo: { tipo: 'rir', valor: 1 },
                        },
                    });
                }
                return Promise.resolve({ data: {} });
            });
            window.axios = axiosModule.default;

            startSupersetSession();
            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });
            await new Promise((r) => setTimeout(r, 30));
            await wrapper.vm.$nextTick();

            const card1 = wrapper.find('[data-testid="superset-history-card-1"]');
            expect(card1.exists()).toBe(true);
            expect(card1.text()).toContain('Press de banca');
            expect(card1.text()).toContain('50 kg');
            expect(card1.text()).toContain('70 kg');

            const card2 = wrapper.find('[data-testid="superset-history-card-2"]');
            expect(card2.exists()).toBe(true);
            expect(card2.text()).toContain('Aperturas en polea');
            expect(card2.text()).toContain('15 kg');
            expect(card2.text()).toContain('25 kg');
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
            // La CTA fija única del modo normal sí está.
            expect(
                wrapper.find('[data-testid="sticky-cta-completar"]').exists()
            ).toBe(true);
            // Y como hay una sola card (no superset), NO debe mostrar el
            // header de nombre adentro (ya está arriba, junto a la imagen).
            expect(
                wrapper.find('[data-testid="set-card-name-2"]').exists()
            ).toBe(false);
            // Sin superset → no hay flechas para alternar media
            expect(wrapper.find('[data-testid="media-prev"]').exists()).toBe(false);
            expect(wrapper.find('[data-testid="media-next"]').exists()).toBe(false);
        });
    });

    describe('Prescripción y notas de esfuerzo (RIR / series)', () => {
        it('muestra el desglose de prescripción en los ajustes y en SetConfigCard', async () => {
            store.start({
                rutina_nombre: 'Personalizada 3 Días',
                dia: 'Día 1 (Torso)',
                ejercicios: [
                    {
                        nombre: 'Press de banca',
                        series_objetivo: 4,
                        reps_min: '4',
                        reps_max: '6',
                        descanso_min: 2,
                        notas: '2x6 RIR 1 + 2x4 RIR 0',
                    },
                ],
            });

            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });

            // 1. El desglose completo vive dentro de "Ajustes de la serie"
            // (colapsado: no empuja la acción principal fuera de pantalla).
            const prescripcionBox = wrapper.find('[data-testid="ejercicio-prescripcion-box"]');
            expect(prescripcionBox.exists()).toBe(true);
            expect(prescripcionBox.text()).toContain('2 series × 6 reps');
            expect(prescripcionBox.text()).toContain('RIR 1');
            expect(prescripcionBox.text()).toContain('2 series × 4 reps');
            expect(prescripcionBox.text()).toContain('RIR 0');

            // 2. En SetConfigCard debe mostrar el banner de plan
            const planBanner = wrapper.find('[data-testid="set-plan-banner"]');
            expect(planBanner.exists()).toBe(true);
            // El banner usa el resumen ESTRUCTURADO del plan (no el texto crudo
            // de `notas`): "2×6 RIR 1 → 2×4 RIR 0".
            expect(planBanner.text()).toContain('2×6 RIR 1 → 2×4 RIR 0');

            // 2b. El objetivo de la serie YA NO se duplica en un pill dentro
            // del banner: se muestra en grande en el "target hero card"
            // (data-testid="set-target-hero-card"), que es el bloque con los
            // números grandes de reps + RIR/RPE/FALLO.
            const targetHero = wrapper.find('[data-testid="set-target-hero-card"]');
            expect(targetHero.exists()).toBe(true);
            expect(targetHero.text()).toContain('6 reps');
            expect(targetHero.text()).toContain('RIR 1');
            // Y el pill duplicado NO debe existir.
            expect(planBanner.text()).not.toContain('Serie #1: 6 reps');

            // 3. El banner de esfuerzo target debe indicar el objetivo para la Serie #1
            const esfuerzoBanner = wrapper.find('[data-testid="esfuerzo-target-banner"]');
            expect(esfuerzoBanner.exists()).toBe(true);
            expect(esfuerzoBanner.text()).toContain('Objetivo Serie #1: 6 reps con RIR 1');
        });

        it('muestra peso mínimo y máximo en el card de última vez', async () => {
            const axiosModule = await import('axios');
            const fakeUltimoData = {
                encontrado: true,
                fecha: '2026-10-01',
                peso_min: 40,
                reps_en_peso_min: 8,
                peso_max: 60,
                reps_en_peso_max: 6,
                ultimo_esfuerzo: { tipo: 'rir', valor: 1 },
            };
            axiosModule.default.get = vi.fn().mockImplementation((url) => {
                if (String(url).includes('/api/historial/ultimo')) {
                    return Promise.resolve({ data: fakeUltimoData });
                }
                return Promise.resolve({ data: {} });
            });
            window.axios = axiosModule.default;

            store.start({
                rutina_nombre: 'Test',
                dia: 'Día 1',
                ejercicios: [{ nombre: 'Press de banca', series_objetivo: 3 }],
            });

            const wrapper = mount(ActiveWorkoutModal, { props: { open: true } });
            await new Promise((r) => setTimeout(r, 20));
            await wrapper.vm.$nextTick();

            const card = wrapper.find('[data-testid="last-exercise-card"]');
            expect(card.exists()).toBe(true);
            const minBox = wrapper.find('[data-testid="last-exercise-min"]');
            expect(minBox.text()).toContain('40 kg');
            expect(minBox.text()).toContain('× 8');
            const maxBox = wrapper.find('[data-testid="last-exercise-max"]');
            expect(maxBox.text()).toContain('60 kg');
            expect(maxBox.text()).toContain('× 6');
            // El esfuerzo de la última sesión acompaña al historial.
            expect(card.text()).toContain('rir 1');
        });
    });
});
