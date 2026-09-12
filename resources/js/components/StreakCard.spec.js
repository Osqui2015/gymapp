import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import StreakCard from './StreakCard.vue';

describe('StreakCard', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.restoreAllMocks();
    });

    const sampleData = {
        current_streak: 5,
        longest_streak: 12,
        this_week: 3,
        this_month: 14,
        total_workouts: 45,
        total_sets: 210,
    };

    it('renderiza la racha expandida por defecto', () => {
        const wrapper = mount(StreakCard, {
            props: { data: sampleData },
        });

        expect(wrapper.find('[data-testid="streak-card-expanded"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="streak-card-minimized"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('5');
        expect(wrapper.text()).toContain('días');
        expect(wrapper.text()).toContain('Mejor racha');
        expect(wrapper.text()).toContain('12');
    });

    it('permite ocultar la racha al hacer click en el botón de ocultar', async () => {
        const wrapper = mount(StreakCard, {
            props: { data: sampleData },
        });

        const hideBtn = wrapper.find('[data-testid="streak-hide-button"]');
        expect(hideBtn.exists()).toBe(true);
        await hideBtn.trigger('click');

        expect(wrapper.find('[data-testid="streak-card-minimized"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="streak-card-expanded"]').exists()).toBe(false);
        expect(localStorage.getItem('gymapp_hide_gamification')).toBe('true');
    });

    it('se monta minimizado si localStorage tiene guardado el estado oculto', () => {
        localStorage.setItem('gymapp_hide_gamification', 'true');

        const wrapper = mount(StreakCard, {
            props: { data: sampleData },
        });

        expect(wrapper.find('[data-testid="streak-card-minimized"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="streak-card-expanded"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Gamificación y racha ocultas');
    });

    it('permite volver a mostrar la racha al hacer click en Mostrar', async () => {
        localStorage.setItem('gymapp_hide_gamification', 'true');

        const wrapper = mount(StreakCard, {
            props: { data: sampleData },
        });

        const showBtn = wrapper.find('[data-testid="streak-show-button"]');
        expect(showBtn.exists()).toBe(true);
        await showBtn.trigger('click');

        expect(wrapper.find('[data-testid="streak-card-expanded"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="streak-card-minimized"]').exists()).toBe(false);
        expect(localStorage.getItem('gymapp_hide_gamification')).toBe('false');
    });
});
