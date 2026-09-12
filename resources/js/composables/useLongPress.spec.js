import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, nextTick, ref } from 'vue';
import { useLongPress } from './useLongPress.js';

// Helper: monta un componente mínimo que use useLongPress para poder
// testear el composable dentro del ciclo de vida de Vue.
function mountWithLongPress(onLongPress, opts = {}) {
    let api;
    const Comp = defineComponent({
        setup() {
            api = useLongPress({ onLongPress, ...opts });
            return () => h('button', { ...api.bind, class: 'target' }, 'press me');
        },
    });
    const wrapper = mount(Comp, { attachTo: document.body });
    return { wrapper, getApi: () => api };
}

describe('useLongPress', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    it('dispara onLongPress tras el delay configurado', async () => {
        const onLongPress = vi.fn();
        const { wrapper } = mountWithLongPress(onLongPress, { delay: 300 });
        const btn = wrapper.find('button');

        await btn.trigger('mousedown');
        expect(onLongPress).not.toHaveBeenCalled();
        vi.advanceTimersByTime(300);
        await nextTick();
        expect(onLongPress).toHaveBeenCalledTimes(1);
    });

    it('no dispara si el usuario suelta antes del delay', async () => {
        const onLongPress = vi.fn();
        const { wrapper } = mountWithLongPress(onLongPress, { delay: 500 });
        const btn = wrapper.find('button');

        await btn.trigger('mousedown');
        vi.advanceTimersByTime(200);
        await btn.trigger('mouseup');
        vi.advanceTimersByTime(500);
        await nextTick();
        expect(onLongPress).not.toHaveBeenCalled();
    });

    it('no dispara si el usuario mueve el dedo más allá de la tolerancia', async () => {
        const onLongPress = vi.fn();
        const { wrapper } = mountWithLongPress(onLongPress, { delay: 500, tolerance: 10 });
        const btn = wrapper.find('button');

        await btn.trigger('mousedown');
        // Simular movimiento de 50px (más allá de la tolerancia de 10)
        await btn.trigger('mousemove', { clientX: 50, clientY: 50 });
        vi.advanceTimersByTime(600);
        await nextTick();
        expect(onLongPress).not.toHaveBeenCalled();
    });

    it('isPressed refleja el estado durante el press', async () => {
        const onLongPress = vi.fn();
        const { wrapper, getApi } = mountWithLongPress(onLongPress, { delay: 500 });
        const btn = wrapper.find('button');

        expect(getApi().isPressed.value).toBe(false);
        await btn.trigger('mousedown');
        expect(getApi().isPressed.value).toBe(true);
        vi.advanceTimersByTime(500);
        await nextTick();
        expect(getApi().isPressed.value).toBe(false);
    });

    it('bloquea el click siguiente si el long-press ya disparó', async () => {
        const onLongPress = vi.fn();
        const { wrapper } = mountWithLongPress(onLongPress, { delay: 100 });
        const btn = wrapper.find('button');

        await btn.trigger('mousedown');
        vi.advanceTimersByTime(150);
        await nextTick();
        expect(onLongPress).toHaveBeenCalledTimes(1);

        // El click que viene después no debe disparar nada (pero no podemos
        // verificar preventDefault directamente; al menos no debe tirar error)
        await btn.trigger('click');
        // onLongPress sigue siendo 1
        expect(onLongPress).toHaveBeenCalledTimes(1);
    });

    it('lanza error si no se pasa onLongPress', () => {
        expect(() => useLongPress({})).toThrow(/onLongPress/);
    });

    it('soporta eventos touch (touchstart/touchend)', async () => {
        const onLongPress = vi.fn();
        const { wrapper } = mountWithLongPress(onLongPress, { delay: 200 });
        const btn = wrapper.find('button');

        await btn.trigger('touchstart', { touches: [{ clientX: 0, clientY: 0 }] });
        vi.advanceTimersByTime(250);
        await nextTick();
        expect(onLongPress).toHaveBeenCalledTimes(1);
    });
});
