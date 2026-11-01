import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import MobileQuickSeriesInput from './MobileQuickSeriesInput.vue';
import { useToastStore } from '../../stores/toast';

/**
 * MobileQuickSeriesInput — bottom sheet de carga rápida de series (mobile).
 *
 * Es el flujo más usado del app: el usuario abre la rutina, marca sus series
 * y guarda. La lógica que se testea acá:
 *
 *  - clonar(): genera una serie por cada serie planificada del ejercicio
 *  - ciclarTipoSerie(): rota el tipo (efectiva → calentamiento → dropset →
 *    al_fallo → efectiva)
 *  - marcarCompletada(): exige peso Y reps antes de marcar, y avisa si faltan
 *  - guardar(): emite solo las series completadas, con la nota adjunta
 */

const EJERCICIOS = [
    {
        id: 1,
        ejercicio_nombre: 'Press banca',
        series: 3,
        reps_min: '8',
        reps_max: '12',
        descanso_min: 90,
        peso_sugerido: 60,
    },
];

function mountSheet(overrides = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);

    return mount(MobileQuickSeriesInput, {
        global: {
            plugins: [pinia],
            // El componente usa <Teleport to="body">. Con este stub el
            // contenido se renderiza inline y el wrapper lo contiene.
            stubs: { teleport: true },
        },
        props: {
            open: true,
            dia: 'Día 1',
            rutinaNombre: 'Push A',
            ejercicios: EJERCICIOS,
            saving: false,
            ...overrides,
        },
        attachTo: document.body,
    });
}

describe('MobileQuickSeriesInput', () => {
    beforeEach(() => {
        document.body.style.overflow = '';
    });

    it('no renderiza nada cuando esta cerrado', () => {
        const wrapper = mountSheet({ open: false });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
        wrapper.unmount();
    });

    it('renderiza el dialogo con el dia y la rutina', () => {
        const wrapper = mountSheet();
        const dialog = wrapper.find('[role="dialog"]');
        expect(dialog.exists()).toBe(true);
        expect(dialog.attributes('aria-label')).toContain('Día 1');
        expect(wrapper.text()).toContain('Push A');
        wrapper.unmount();
    });

    it('genera una serie por cada serie planificada', () => {
        const wrapper = mountSheet();
        // 3 series planificadas -> 3 filas numeradas.
        expect(wrapper.text()).toContain('#1');
        expect(wrapper.text()).toContain('#2');
        expect(wrapper.text()).toContain('#3');
        wrapper.unmount();
    });

    it('usa 3 series por defecto si el ejercicio no define cuantas', () => {
        const wrapper = mountSheet({
            ejercicios: [{ id: 2, ejercicio_nombre: 'Sentadilla', reps_min: '5', reps_max: '5' }],
        });
        expect(wrapper.text()).toContain('#1');
        expect(wrapper.text()).toContain('#2');
        expect(wrapper.text()).toContain('#3');
        wrapper.unmount();
    });

    it('pre-carga el peso sugerido del ejercicio', () => {
        const wrapper = mountSheet();
        const inputs = wrapper.findAll('input[type="number"]');
        // El primer input es el peso de la serie 1.
        expect(inputs[0].element.value).toBe('60');
        wrapper.unmount();
    });

    it('el tipo de serie inicial es "efectiva" (Norm.)', () => {
        const wrapper = mountSheet();
        expect(wrapper.text()).toContain('Norm.');
        wrapper.unmount();
    });

    it('cicla el tipo de serie al tocar el boton de tipo', async () => {
        const wrapper = mountSheet();

        const botones = wrapper.findAll('button[title^="Tipo:"]');
        expect(botones.length).toBe(3);

        // efectiva -> calentamiento
        await botones[0].trigger('click');
        expect(botones[0].text()).toBe('Cal.');

        // calentamiento -> dropset
        await botones[0].trigger('click');
        expect(botones[0].text()).toBe('Drop');

        // dropset -> al_fallo
        await botones[0].trigger('click');
        expect(botones[0].text()).toBe('Fallo');

        // al_fallo -> efectiva (vuelve al inicio)
        await botones[0].trigger('click');
        expect(botones[0].text()).toBe('Norm.');
        wrapper.unmount();
    });

    it('no marca una serie como completada si falta el peso', async () => {
        // Ejercicio SIN peso_sugerido: el input de peso arranca vacío.
        const wrapper = mountSheet({
            ejercicios: [
                {
                    id: 9,
                    ejercicio_nombre: 'Sentadilla',
                    series: 2,
                    reps_min: '5',
                    reps_max: '5',
                },
            ],
        });

        const inputs = wrapper.findAll('input[type="number"]');
        // Solo reps: el peso sigue vacío.
        await inputs[1].setValue('10');

        const check = wrapper.find('button[aria-label="Marcar serie completada"]');
        await check.trigger('click');

        // Sigue sin completarse: el botón conserva su aria-label original.
        expect(wrapper.find('button[aria-label="Marcar serie completada"]').exists()).toBe(true);
        expect(wrapper.findAll('button[aria-pressed="true"]').length).toBe(0);
        wrapper.unmount();
    });

    it('avisa con un toast si faltan peso o reps al marcar', async () => {
        const wrapper = mountSheet();
        const store = useToastStore();
        store.dismissAll();

        const check = wrapper.find('button[aria-label="Marcar serie completada"]');
        await check.trigger('click');

        expect(store.toasts.length).toBe(1);
        expect(store.toasts[0].type).toBe('warning');
        expect(store.toasts[0].message).toContain('Cargá peso y reps');
        wrapper.unmount();
    });

    it('el boton Guardar esta deshabilitado mientras no haya series completadas', async () => {
        const wrapper = mountSheet();

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        expect(guardar.attributes('disabled')).toBeDefined();

        // Con una serie completada, se habilita.
        await wrapper.findAll('input[type="number"]')[1].setValue('10');
        await wrapper.find('button[aria-label="Marcar serie completada"]').trigger('click');

        const guardar2 = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        expect(guardar2.attributes('disabled')).toBeUndefined();
        wrapper.unmount();
    });

    it('el boton Guardar se habilita solo con series marcadas, no solo con los datos', async () => {
        const wrapper = mountSheet();

        // Poner peso y reps NO alcanza: hay que marcar la serie.
        await wrapper.findAll('input[type="number"]')[1].setValue('10');

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        expect(guardar.attributes('disabled')).toBeDefined();
        wrapper.unmount();
    });

    it('marca la serie como completada si tiene peso y reps', async () => {
        const wrapper = mountSheet();

        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[0].setValue('60');
        await inputs[1].setValue('10');

        const check = wrapper.find('button[aria-label="Marcar serie completada"]');
        await check.trigger('click');

        // La serie queda marcada (aria-pressed="true").
        const marcadas = wrapper.findAll('button[aria-pressed="true"]');
        expect(marcadas.length).toBe(1);
        expect(marcadas[0].attributes('aria-label')).toBe('Serie completada');
        wrapper.unmount();
    });

    it('desmarca la serie si se vuelve a tocar el check', async () => {
        const wrapper = mountSheet();

        await wrapper.findAll('input[type="number"]')[1].setValue('10');
        await wrapper.find('button[aria-label="Marcar serie completada"]').trigger('click');
        expect(wrapper.findAll('button[aria-pressed="true"]').length).toBe(1);

        await wrapper.find('button[aria-label="Serie completada"]').trigger('click');
        expect(wrapper.findAll('button[aria-pressed="true"]').length).toBe(0);
        wrapper.unmount();
    });

    it('guarda solo las series completadas', async () => {
        const wrapper = mountSheet();

        const inputs = wrapper.findAll('input[type="number"]');
        // Serie 1: peso 60 (sugerido), reps 10 -> completar.
        await inputs[1].setValue('10');
        await wrapper.find('button[aria-label="Marcar serie completada"]').trigger('click');

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        await guardar.trigger('click');

        const emissions = wrapper.emitted('save');
        expect(emissions).toBeTruthy();
        expect(emissions[0][0].records.length).toBe(1);

        const rec = emissions[0][0].records[0];
        expect(rec.ejercicio_nombre).toBe('Press banca');
        expect(rec.series_numero).toBe(1);
        expect(rec.peso).toBe(60);
        expect(rec.reps_realizadas).toBe(10);
        expect(rec.reps_min).toBe('8');
        expect(rec.reps_max).toBe('12');
        expect(rec.tipo_serie).toBe('efectiva');
        expect(rec.completado).toBe(true);
        wrapper.unmount();
    });

    it('el payload del guardado incluye el dia y la rutina', async () => {
        const wrapper = mountSheet();

        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[1].setValue('10');
        await wrapper.find('button[aria-label="Marcar serie completada"]').trigger('click');

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        await guardar.trigger('click');

        const payload = wrapper.emitted('save')[0][0];
        expect(payload.dia).toBe('Día 1');
        expect(payload.rutina_nombre).toBe('Push A');
        wrapper.unmount();
    });

    it('no guarda si no hay ninguna serie completada', async () => {
        const wrapper = mountSheet();

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        await guardar.trigger('click');

        expect(wrapper.emitted('save')).toBeFalsy();
        wrapper.unmount();
    });

    it('adjunta la nota del ejercicio a cada set guardado', async () => {
        const wrapper = mountSheet();

        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[1].setValue('10');
        await wrapper.find('button[aria-label="Marcar serie completada"]').trigger('click');

        // La nota del usuario (input de texto del ejercicio).
        const nota = wrapper.find('input[placeholder^="Opcional"]');
        await nota.setValue('Me sentí fuerte');

        const guardar = wrapper.findAll('button').find((b) => b.text().includes('Guardar'));
        await guardar.trigger('click');

        const rec = wrapper.emitted('save')[0][0].records[0];
        expect(rec.nota_user).toBe('Me sentí fuerte');
        wrapper.unmount();
    });

    it('emite close al tocar el fondo o el boton de cerrar', async () => {
        const wrapper = mountSheet();

        const cerrar = wrapper.find('button[aria-label="Cerrar"]');
        await cerrar.trigger('click');

        expect(wrapper.emitted('close')).toBeTruthy();
        wrapper.unmount();
    });

    it('bloquea el scroll del body mientras esta abierto', async () => {
        const wrapper = mountSheet();
        await wrapper.vm.$nextTick();

        expect(document.body.style.overflow).toBe('hidden');
        wrapper.unmount();
    });

    it('restaura el scroll del body al cerrar', async () => {
        const wrapper = mountSheet({ open: true });
        await wrapper.vm.$nextTick();
        expect(document.body.style.overflow).toBe('hidden');

        await wrapper.setProps({ open: false });
        await wrapper.vm.$nextTick();

        expect(document.body.style.overflow).toBe('');
        wrapper.unmount();
    });

    it('no muta los props: trabaja sobre una copia local', async () => {
        const ejercicios = JSON.parse(JSON.stringify(EJERCICIOS));
        const wrapper = mountSheet({ ejercicios });

        const botones = wrapper.findAll('button[title^="Tipo:"]');
        await botones[0].trigger('click');

        // El prop original no debe tener _series ni tipo_serie.
        expect(ejercicios[0]._series).toBeUndefined();
        expect(ejercicios[0].tipo_serie).toBeUndefined();
        wrapper.unmount();
    });

    it('al reabrir vuelve a clonar desde cero (descarta lo no guardado)', async () => {
        const wrapper = mountSheet();

        const botones = wrapper.findAll('button[title^="Tipo:"]');
        await botones[0].trigger('click');
        expect(botones[0].text()).toBe('Cal.');

        await wrapper.setProps({ open: false });
        await wrapper.setProps({ open: true });
        await wrapper.vm.$nextTick();

        const nuevosBotones = wrapper.findAll('button[title^="Tipo:"]');
        expect(nuevosBotones[0].text()).toBe('Norm.');
        wrapper.unmount();
    });
});
