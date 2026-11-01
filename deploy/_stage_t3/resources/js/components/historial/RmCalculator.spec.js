import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, ref, nextTick } from 'vue';
import RmCalculator from './RmCalculator.vue';

/**
 * El componente tenía una copia local del prop + dos watches deep encadenados
 * (prop → local → emit → prop → …): al tipear entraba en bucle recursivo
 * ("Maximum recursive updates exceeded"). Además la fórmula tenía dos
 * fuentes (rmFormula vs la local), así que cambiarla no recalculaba el 1RM.
 *
 * Los tests usan un harness padre real, porque el componente es controlado
 * (v-model): si el padre no baja el prop, el hijo no puede avanzar.
 */
const Harness = defineComponent({
    components: { RmCalculator },
    setup() {
        const calculator = ref({ weight: 80, reps: 5 });
        const rmFormula = ref('epley');
        const emissions = ref([]);
        return { calculator, rmFormula, emissions };
    },
    template: `
        <RmCalculator
            v-model:calculator="calculator"
            v-model:rm-formula="rmFormula"
            :historical1RMs="[]"
            @update:calculator="emissions.push($event)"
        />
    `,
});

describe('RmCalculator', () => {
    it('calcula el 1RM con la fórmula Epley', () => {
        const wrapper = mount(Harness);
        // 80 × (1 + 5/30) = 93.33
        expect(wrapper.text()).toContain('93.3');
    });

    it('recalcula al cambiar la fórmula (antes no recalculaba nada)', async () => {
        const wrapper = mount(Harness);
        expect(wrapper.text()).toContain('93.3');

        await wrapper.find('select').setValue('lander');
        await nextTick();

        // Lander: (100 × 80) / (101.3 - 2.6712 × 5) = 91.0
        expect(wrapper.text()).toContain('91.0');
    });

    it('no entra en bucle recursivo al escribir el peso', async () => {
        const wrapper = mount(Harness);

        // Si el ciclo prop → local → emit → prop estuviera vivo, esto dispara
        // "Maximum recursive updates exceeded".
        const input = wrapper.find('input[type="number"]');
        await input.setValue(100);
        await nextTick();
        await input.setValue(120);
        await nextTick();

        // 120 × (1 + 5/30) = 140
        expect(wrapper.text()).toContain('140');
    });

    it('emite update:calculator al cambiar el peso', async () => {
        const wrapper = mount(Harness);

        await wrapper.find('input[type="number"]').setValue(90);
        await nextTick();

        const emissions = wrapper.vm.emissions;
        expect(emissions.length).toBeGreaterThan(0);
        expect(emissions[emissions.length - 1].weight).toBe(90);
    });

    it('el padre recibe el valor tipeado (v-model real)', async () => {
        const wrapper = mount(Harness);

        await wrapper.find('input[type="number"]').setValue(95);
        await nextTick();

        expect(wrapper.vm.calculator.weight).toBe(95);
    });

    it('emite update:rmFormula al cambiar el select de fórmula', async () => {
        const wrapper = mount(Harness);

        await wrapper.find('select').setValue('lander');
        await nextTick();

        expect(wrapper.vm.rmFormula).toBe('lander');
    });
});
