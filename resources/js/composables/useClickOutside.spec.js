import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { useClickOutside } from './useClickOutside';

/**
 * `useClickOutside` reemplaza la directiva `@click.away` de Alpine
 * (que no existe en Vue 3).
 *
 * Los dropdowns de la navbar (Trainer / Admin / menu de usuario) dependen de
 * que el menu se cierre al hacer click fuera.
 */
describe('useClickOutside', () => {
    it('dispara el handler cuando el click es fuera del elemento', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const root = ref(null);
                useClickOutside(root, handlerSpy, true);
                return () =>
                    h('div', [
                        h('div', { ref: root, 'data-test': 'root' }, 'dentro'),
                        h('div', { 'data-test': 'outside' }, 'fuera'),
                    ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        await wrapper.find('[data-test="outside"]').trigger('pointerdown');

        expect(handlerSpy).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });

    it('NO dispara el handler cuando el click es dentro del elemento', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const root = ref(null);
                useClickOutside(root, handlerSpy, true);
                return () =>
                    h('div', [
                        h('div', { ref: root, 'data-test': 'root' }, 'dentro'),
                    ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        await wrapper.find('[data-test="root"]').trigger('pointerdown');

        expect(handlerSpy).not.toHaveBeenCalled();
        wrapper.unmount();
    });

    it('no escucha cuando `when` es false', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const root = ref(null);
                useClickOutside(root, handlerSpy, false);
                return () =>
                    h('div', [
                        h('div', { ref: root, 'data-test': 'root' }, 'dentro'),
                        h('div', { 'data-test': 'outside' }, 'fuera'),
                    ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        await wrapper.find('[data-test="outside"]').trigger('pointerdown');

        expect(handlerSpy).not.toHaveBeenCalled();
        wrapper.unmount();
    });

    it('deja de escuchar cuando `when` pasa a false (dropdown cerrado)', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const root = ref(null);
                const enabled = ref(true);
                useClickOutside(root, handlerSpy, enabled);
                return { root, enabled };
            },
            render() {
                return h('div', [
                    h('div', { ref: 'root', 'data-test': 'root' }, 'dentro'),
                    h('div', { 'data-test': 'outside' }, 'fuera'),
                ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        // Con when=true el listener esta activo.
        await wrapper.find('[data-test="outside"]').trigger('pointerdown');
        expect(handlerSpy).toHaveBeenCalledTimes(1);

        // Al cerrar el dropdown (when=false) el listener se desengancha.
        await wrapper.setData({ enabled: false });
        await wrapper.find('[data-test="outside"]').trigger('pointerdown');
        expect(handlerSpy).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('engancha el listener cuando `when` pasa a true', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const root = ref(null);
                const enabled = ref(false);
                useClickOutside(root, handlerSpy, enabled);
                return { root, enabled };
            },
            render() {
                return h('div', [
                    h('div', { ref: 'root', 'data-test': 'root' }, 'dentro'),
                    h('div', { 'data-test': 'outside' }, 'fuera'),
                ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        await wrapper.find('[data-test="outside"]').trigger('pointerdown');
        expect(handlerSpy).not.toHaveBeenCalled();

        await wrapper.setData({ enabled: true });
        await wrapper.find('[data-test="outside"]').trigger('pointerdown');
        expect(handlerSpy).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('soporta varios elementos objetivo (array de refs)', async () => {
        const handlerSpy = vi.fn();

        const Comp = defineComponent({
            setup() {
                const a = ref(null);
                const b = ref(null);
                useClickOutside([a, b], handlerSpy, true);
                return () =>
                    h('div', [
                        h('div', { ref: a, 'data-test': 'a' }, 'A'),
                        h('div', { ref: b, 'data-test': 'b' }, 'B'),
                        h('div', { 'data-test': 'outside' }, 'fuera'),
                    ]);
            },
        });

        const wrapper = mount(Comp, { attachTo: document.body });

        // Click dentro del segundo target: no debe disparar.
        await wrapper.find('[data-test="b"]').trigger('pointerdown');
        expect(handlerSpy).not.toHaveBeenCalled();

        // Click dentro del primero: tampoco.
        await wrapper.find('[data-test="a"]').trigger('pointerdown');
        expect(handlerSpy).not.toHaveBeenCalled();

        // Click fuera: dispara.
        await wrapper.find('[data-test="outside"]').trigger('pointerdown');
        expect(handlerSpy).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });
});
