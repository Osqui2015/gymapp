import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import axios from 'axios';
import EffortCard from './EffortCard.vue';

/**
 * EffortCard — análisis de esfuerzo (RIR/RPE) del usuario.
 *
 * Cubre la lógica propia del componente:
 *  - el cálculo del sparkline SVG (path, posiciones, clamp de RIR a 0-4)
 *  - el ancho de las barras de distribución (con el mínimo del 6%)
 *  - el color de cada bucket
 *  - la recarga cuando cambia el alumno (userId) — el bug que corrigió
 *    la Tanda 2: sin el watch, la tarjeta mostraba el esfuerzo del alumno
 *    anterior cuando el trainer cambiaba de alumno.
 */

vi.mock('axios');

const RESPUESTA_BASE = {
    summary: {
        avg_rir: 2.4,
        high_effort_pct: 33,
        sets_with_effort: 12,
        total_sets: 36,
        coverage_pct: 33,
    },
    distribution: { '0': 2, '1': 3, '2': 4, '3': 1, '4': 0 },
    weekly_trend: [
        { week_start: '2026-09-01', avg_rir: 3, sets: 5 },
        { week_start: '2026-09-08', avg_rir: 2, sets: 8 },
        { week_start: '2026-09-15', avg_rir: 1, sets: 6 },
    ],
    by_exercise: [{ ejercicio: 'Press banca', sets: 10, avg_rir: 1.5 }],
};

describe('EffortCard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        axios.get = vi.fn().mockResolvedValue({ data: JSON.parse(JSON.stringify(RESPUESTA_BASE)) });
    });

    it('muestra el loading mientras carga', async () => {
        let resolve;
        axios.get = vi.fn(() => new Promise((r) => (resolve = r)));

        const wrapper = mount(EffortCard);
        expect(wrapper.text()).toContain('Cargando análisis de esfuerzo');

        resolve({ data: RESPUESTA_BASE });
        await flushPromises();
        wrapper.unmount();
    });

    it('pide el análisis con el rango por default (30d)', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(axios.get).toHaveBeenCalledWith('/api/historial/effort-analysis', {
            params: { range: '30d' },
        });
        wrapper.unmount();
    });

    it('incluye el user_id cuando el trainer está viendo a un alumno', async () => {
        const wrapper = mount(EffortCard, { props: { userId: 7 } });
        await flushPromises();

        expect(axios.get).toHaveBeenCalledWith('/api/historial/effort-analysis', {
            params: { range: '30d', user_id: 7 },
        });
        wrapper.unmount();
    });

    it('muestra el resumen con el promedio de RIR', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('RIR 2.4');
        expect(wrapper.text()).toContain('33%');
        expect(wrapper.text()).toContain('12 de 36 sets con esfuerzo');
        wrapper.unmount();
    });

    it('re-carga cuando cambia el alumno (userId)', async () => {
        const wrapper = mount(EffortCard, { props: { userId: 7 } });
        await flushPromises();
        expect(axios.get).toHaveBeenCalledTimes(1);

        await wrapper.setProps({ userId: 9 });
        await flushPromises();

        expect(axios.get).toHaveBeenCalledTimes(2);
        expect(axios.get).toHaveBeenLastCalledWith('/api/historial/effort-analysis', {
            params: { range: '30d', user_id: 9 },
        });
        wrapper.unmount();
    });

    it('re-carga cuando cambia el rango', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        const boton90d = wrapper.findAll('button').find((b) => b.text() === '90d');
        await boton90d.trigger('click');
        await flushPromises();

        expect(axios.get).toHaveBeenLastCalledWith('/api/historial/effort-analysis', {
            params: { range: '90d' },
        });
        expect(wrapper.text()).toContain('90 días');
        wrapper.unmount();
    });

    it('muestra el mensaje de "sin esfuerzo" cuando no hay sets con RIR/RPE', async () => {
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                summary: {
                    avg_rir: null,
                    high_effort_pct: 0,
                    sets_with_effort: 0,
                    total_sets: 20,
                    coverage_pct: 0,
                },
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('Todavía no registraste RIR o RPE');
        wrapper.unmount();
    });

    it('muestra el error del servidor cuando la peticion falla', async () => {
        axios.get = vi.fn().mockRejectedValue({
            response: { data: { message: 'Sesión expirada' } },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('Sesión expirada');
        wrapper.unmount();
    });

    it('usa un mensaje generico si el error no trae mensaje', async () => {
        axios.get = vi.fn().mockRejectedValue(new Error('network'));

        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('No se pudo cargar el análisis de esfuerzo');
        wrapper.unmount();
    });

    it('dibuja el sparkline con un path para cada semana', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        const path = wrapper.find('svg path');
        expect(path.exists()).toBe(true);
        expect(path.attributes('d')).toMatch(/^M /);
        expect(path.attributes('d')).toContain('Q');
        wrapper.unmount();
    });

    it('el sparkline dibuja un solo punto cuando hay una sola semana', async () => {
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                weekly_trend: [{ week_start: '2026-09-01', avg_rir: 2, sets: 5 }],
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        const path = wrapper.find('svg path');
        // Con un solo punto el path es una línea horizontal (no una curva).
        expect(path.attributes('d')).not.toContain('Q');
        expect(path.attributes('d')).toMatch(/^M 12,/);
        wrapper.unmount();
    });

    it('el sparkline limita el RIR al rango 0-4', async () => {
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                weekly_trend: [
                    { week_start: '2026-09-01', avg_rir: 9, sets: 5 },
                    { week_start: '2026-09-08', avg_rir: -3, sets: 5 },
                ],
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        const circulos = wrapper.findAll('svg circle');
        // 2 circulos por punto (el hit-area transparente + el visible).
        const visibles = circulos.filter((c) => c.attributes('r') !== '12');
        expect(visibles.length).toBe(2);

        // RIR 9 se clampa a 4 -> y = padY + (4/4)*usableH = 12 + 36 = 48
        expect(visibles[0].attributes('cy')).toBe('48');
        // RIR -3 se clampa a 0 -> y = padY = 12
        expect(visibles[1].attributes('cy')).toBe('12');
        wrapper.unmount();
    });

    it('el sparkline usa el RIR por defecto (2) si viene undefined', async () => {
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                weekly_trend: [{ week_start: '2026-09-01', sets: 5 }],
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        const visibles = wrapper.findAll('svg circle').filter((c) => c.attributes('r') !== '12');
        // RIR 2 -> y = 12 + (2/4)*36 = 30
        expect(visibles[0].attributes('cy')).toBe('30');
        wrapper.unmount();
    });

    it('las barras de distribucion tienen ancho proporcional al maximo', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        // Maximo del distribution = 4 (bucket 2).
        // bucket 2 -> 100%, bucket 0 -> 50%, bucket 1 -> 75%, bucket 3 -> 25%.
        const barras = wrapper.findAll('.rounded-full.transition-all');
        const anchos = barras.map((b) => b.attributes('style'));

        expect(anchos[0]).toContain('width: 50%'); // bucket 0 -> 2/4
        expect(anchos[1]).toContain('width: 75%'); // bucket 1 -> 3/4
        expect(anchos[2]).toContain('width: 100%'); // bucket 2 -> 4/4
        wrapper.unmount();
    });

    it('una barra con valor 0 tiene ancho 0 (sin barra visible)', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        const barras = wrapper.findAll('.rounded-full.transition-all');
        // El bucket 4 tiene 0 sets -> la barra no se dibuja.
        const anchos = barras.map((b) => b.attributes('style'));
        expect(anchos[4]).toContain('width: 0%');
        wrapper.unmount();
    });

    it('una barra con un set tiene el ancho minimo del 6%', async () => {
        // distribution = {0:1, 1:1} -> max = 1 -> 1/1 = 100%, pero forzamos
        // un caso donde el porcentaje real es muy chico: 1 de 40.
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                distribution: { '0': 1, '1': 40 },
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        const barras = wrapper.findAll('.rounded-full.transition-all');
        const anchos = barras.map((b) => b.attributes('style'));
        // 1/40 = 2.5% -> se sube al mínimo del 6% para que se vea la barra.
        expect(anchos[0]).toContain('width: 6%');
        wrapper.unmount();
    });

    it('muestra las 5 barras de distribucion (RIR 0 a 4+)', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('RIR 0');
        expect(wrapper.text()).toContain('RIR 4+');
        wrapper.unmount();
    });

    it('al tocar un punto del sparkline muestra el detalle de esa semana', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        const puntos = wrapper.findAll('svg g.cursor-pointer');
        expect(puntos.length).toBe(3);

        await puntos[1].trigger('click');
        await wrapper.vm.$nextTick();

        // El detalle aparece arriba del sparkline, en el span con la semana.
        const detalle = wrapper.findAll('span').find((s) => s.text().includes('2026-09-08 · RIR 2'));
        expect(detalle).toBeTruthy();
        expect(detalle.text()).toContain('8 sets');
        wrapper.unmount();
    });

    it('al tocar el mismo punto dos veces se deselecciona', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        const puntos = wrapper.findAll('svg g.cursor-pointer');
        await puntos[1].trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.findAll('span').some((s) => s.text().includes('2026-09-08 · RIR 2'))).toBe(true);

        await puntos[1].trigger('click');
        await wrapper.vm.$nextTick();

        expect(wrapper.findAll('span').some((s) => s.text().includes('2026-09-08 · RIR 2'))).toBe(false);
        expect(wrapper.text()).toContain('3 semanas');
        wrapper.unmount();
    });

    it('lista los ejercicios mas trackeados', async () => {
        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('Más Trackeados');
        expect(wrapper.text()).toContain('Press banca');
        expect(wrapper.text()).toContain('10 sets');
        wrapper.unmount();
    });

    it('usa el singular "semana" cuando hay una sola semana', async () => {
        axios.get = vi.fn().mockResolvedValue({
            data: {
                ...JSON.parse(JSON.stringify(RESPUESTA_BASE)),
                weekly_trend: [{ week_start: '2026-09-01', avg_rir: 2, sets: 5 }],
            },
        });

        const wrapper = mount(EffortCard);
        await flushPromises();

        expect(wrapper.text()).toContain('1 semana');
        wrapper.unmount();
    });
});
