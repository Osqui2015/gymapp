import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import OfflineStatusBar from './OfflineStatusBar.vue';

describe('OfflineStatusBar', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('mounts without crashing', () => {
        const wrapper = mount(OfflineStatusBar);
        expect(wrapper.exists()).toBe(true);
    });

    it('shows offline message when window fires offline event', async () => {
        const wrapper = mount(OfflineStatusBar);
        window.dispatchEvent(new Event('offline'));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Modo sin conexión');
    });

    it('transitions to online message when window fires online event', async () => {
        const wrapper = mount(OfflineStatusBar);
        window.dispatchEvent(new Event('offline'));
        await wrapper.vm.$nextTick();

        window.dispatchEvent(new Event('online'));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Conexión restablecida');

        // Auto-hides after timeout
        vi.advanceTimersByTime(4500);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });

    it('dismisses the bar when close button is clicked', async () => {
        const wrapper = mount(OfflineStatusBar);
        window.dispatchEvent(new Event('offline'));
        await wrapper.vm.$nextTick();

        const btn = wrapper.find('button[aria-label="Cerrar aviso de conexión"]');
        expect(btn.exists()).toBe(true);
        await btn.trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });
});
