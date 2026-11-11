<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** Historial de la rutina (filas de /api/historial). */
    historialRutina: {
        type: Array,
        default: () => [],
    },
    /** Ejercicios del día actual, para derivar el nombre del gráfico principal. */
    ejerciciosDelDia: {
        type: Array,
        default: () => [],
    },
});

const chartEjercicioNombre = computed(() => {
    const first = props.ejerciciosDelDia[0]?.nombre;
    return first || 'Press de banca';
});

const chartSeries = computed(() => {
    const h = props.historialRutina || [];
    const hoy = new Date();
    const desde = new Date(hoy.getTime() - 30 * 86400000);
    const N = 7;
    const puntos = [];
    for (let i = 0; i < N; i++) {
        const f = new Date(desde.getTime() + ((hoy.getTime() - desde.getTime()) * i) / (N - 1));
        puntos.push({ fecha: f, peso: 0 });
    }

    h.forEach((r) => {
        if (!r.completado || !r.peso || Number(r.peso) <= 0) return;
        const fr = new Date(r.fecha);
        if (fr < desde || fr > hoy) return;
        let mejorI = 0;
        let mejorDelta = Infinity;
        for (let i = 0; i < N; i++) {
            const delta = Math.abs(puntos[i].fecha - fr);
            if (delta < mejorDelta) {
                mejorDelta = delta;
                mejorI = i;
            }
        }
        puntos[mejorI].peso = Math.max(puntos[mejorI].peso, Number(r.peso));
    });

    const labels = puntos.map((p) => {
        const dd = String(p.fecha.getDate()).padStart(2, '0');
        const mm = String(p.fecha.getMonth() + 1).padStart(2, '0');
        return `${dd}/${mm}`;
    });

    return { points: puntos, labels };
});

const maxPesoChart = computed(() => {
    const pts = chartSeries.value.points;
    if (!pts.length) return 80;
    const max = Math.max(...pts.map((p) => p.peso || 0));
    return max > 0 ? max : 80;
});

const buildSmoothSvgCurve = (coords, baselineY = 170) => {
    if (!coords.length) return { line: '', area: '' };
    if (coords.length === 1) {
        const p = coords[0];
        const line = `M 20 ${p.y} L 480 ${p.y}`;
        const area = `${line} L 480 ${baselineY} L 20 ${baselineY} Z`;
        return { line, area };
    }
    let line = `M ${coords[0].x} ${coords[0].y}`;
    for (let i = 1; i < coords.length; i++) {
        const prev = coords[i - 1];
        const cur = coords[i];
        const dx = (cur.x - prev.x) * 0.45;
        const cp1x = Math.round(prev.x + dx);
        const cp2x = Math.round(cur.x - dx);
        line += ` C ${cp1x} ${prev.y}, ${cp2x} ${cur.y}, ${cur.x} ${cur.y}`;
    }
    const last = coords[coords.length - 1];
    const first = coords[0];
    const area = `${line} L ${last.x} ${baselineY} L ${first.x} ${baselineY} Z`;
    return { line, area };
};

const chart1Telemetry = computed(() => {
    const { points, labels } = chartSeries.value;
    const max = Math.max(...points.map((p) => p.peso), 0);
    if (max <= 0) {
        // Fallback curva de telemetría cuando aún no hay puntos en los últimos 30d
        const fallbackNodes = [
            { x: 20, y: 145, peak: false, muted: false },
            { x: 95, y: 90, peak: false, muted: false },
            { x: 165, y: 30, peak: true, muted: false },
            { x: 235, y: 150, peak: false, muted: false },
            { x: 360, y: 150, peak: false, muted: true },
            { x: 480, y: 150, peak: false, muted: true },
        ];
        const { line, area } = buildSmoothSvgCurve(fallbackNodes, 170);
        return { nodes: fallbackNodes, line, area, labels };
    }
    const min = Math.min(...points.map((p) => p.peso));
    const rango = Math.max(max - min, 1);
    let peakAssigned = false;
    const nodes = points.map((p, i) => {
        const x = Math.round(20 + (460 * i) / (points.length - 1));
        const y = Math.round(150 - ((p.peso - min) / rango) * 120);
        const isPeak = !peakAssigned && p.peso === max && max > 0;
        if (isPeak) peakAssigned = true;
        return {
            x,
            y,
            value: p.peso,
            peak: isPeak,
            muted: p.peso === 0,
        };
    });
    const { line, area } = buildSmoothSvgCurve(nodes, 170);
    return { nodes, line, area, labels };
});

const chart2Telemetry = computed(() => {
    const porDia = new Map();
    (props.historialRutina || []).forEach((row) => {
        const fecha = row.fecha || (row.created_at ? row.created_at.split('T')[0] : null);
        if (!fecha) return;
        const peso = Number(row.peso) || 0;
        if (peso <= 0) return;
        if (!porDia.has(fecha) || peso > porDia.get(fecha)) {
            porDia.set(fecha, peso);
        }
    });

    const sorted = [...porDia.entries()].sort((a, b) => a[0].localeCompare(b[0])).slice(-7);
    if (sorted.length < 2) {
        const fallbackNodes = [
            { x: 20, y: 140, isPeak: false, isAccent: false },
            { x: 90, y: 70, isPeak: false, isAccent: false },
            { x: 190, y: 145, isPeak: false, isAccent: false },
            { x: 270, y: 140, isPeak: false, isAccent: false },
            { x: 370, y: 70, isPeak: false, isAccent: true },
            { x: 430, y: 62, isPeak: false, isAccent: false },
            { x: 480, y: 30, isPeak: true, isAccent: false },
        ];
        const { line, area } = buildSmoothSvgCurve(fallbackNodes, 170);
        return {
            nodes: fallbackNodes,
            line,
            area,
            labels: ['03/09', '09/09', '12/09', '23/09', '30/09'],
        };
    }

    const pesos = sorted.map(([, p]) => p);
    const max = Math.max(...pesos);
    const min = Math.min(...pesos);
    const rango = Math.max(max - min, 1);

    const nodes = sorted.map(([, peso], i) => {
        const x = Math.round(20 + (460 * i) / (sorted.length - 1));
        const y = Math.round(145 - ((peso - min) / rango) * 115);
        return {
            x,
            y,
            value: peso,
            isPeak: i === sorted.length - 1 || (peso === max && i > 0),
            isAccent: i === Math.floor(sorted.length * 0.65),
        };
    });

    const labels = sorted.map(([fecha]) => {
        const iso = typeof fecha === 'string' && fecha.includes('T') ? fecha.split('T')[0] : fecha;
        const parts = String(iso).split('-');
        if (parts.length >= 3) {
            return `${parts[2]}/${parts[1]}`;
        }
        return iso;
    });

    const { line, area } = buildSmoothSvgCurve(nodes, 170);
    return { nodes, line, area, labels };
});
</script>

<template>
    <section
        class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg mb-space-xl"
        data-tour="telemetry-charts"
    >
        <!-- Telemetry Chart 1: Press de Banca 30d -->
        <div
            class="p-space-lg rounded-xl bg-surface-container-low border border-outline-variant/60 flex flex-col justify-between shadow-xs"
        >
            <div class="flex items-start justify-between gap-space-md mb-space-md">
                <div class="flex flex-col">
                    <div
                        class="flex items-center gap-space-xs text-secondary font-label-sm text-label-sm uppercase tracking-wider"
                    >
                        <span class="material-symbols-outlined text-[16px]"
                            >show_chart</span
                        >
                        <span>Peso máximo por día (30d)</span>
                    </div>
                    <h4
                        class="font-headline-md text-headline-md text-on-surface font-bold"
                    >
                        Progreso en {{ chartEjercicioNombre }}
                    </h4>
                    <span class="font-body-sm text-body-sm text-outline">
                        Evolución de carga máxima en series principales
                    </span>
                </div>
                <span
                    class="px-space-md py-1 rounded-full bg-secondary-container/50 text-secondary font-label-md text-label-md font-bold shrink-0"
                >
                    Max: {{ maxPesoChart }} kg
                </span>
            </div>

            <!-- Inline SVG Telemetry Curve 1 -->
            <div class="relative w-full h-56 pt-space-sm">
                <svg
                    class="w-full h-full overflow-visible"
                    fill="none"
                    preserveAspectRatio="none"
                    viewBox="0 0 500 180"
                >
                    <defs>
                        <linearGradient
                            id="chartGrad1"
                            x1="0"
                            x2="0"
                            y1="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="var(--color-primary)"
                                stop-opacity="0.25"
                            ></stop>
                            <stop
                                offset="100%"
                                stop-color="var(--color-primary)"
                                stop-opacity="0.0"
                            ></stop>
                        </linearGradient>
                    </defs>
                    <!-- Grid Lines -->
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-dasharray="4 4"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="40"
                        y2="40"
                    ></line>
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-dasharray="4 4"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="90"
                        y2="90"
                    ></line>
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-dasharray="4 4"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="140"
                        y2="140"
                    ></line>
                    <!-- Curve fill area -->
                    <path :d="chart1Telemetry.area" fill="url(#chartGrad1)"></path>
                    <!-- Smooth spline path -->
                    <path
                        :d="chart1Telemetry.line"
                        stroke="var(--color-primary)"
                        stroke-linecap="round"
                        stroke-width="3"
                    ></path>
                    <!-- Key Metric Nodes -->
                    <template
                        v-for="(node, nIdx) in chart1Telemetry.nodes"
                        :key="nIdx"
                    >
                        <template v-if="node.peak">
                            <circle
                                class="animate-pulse"
                                :cx="node.x"
                                :cy="node.y"
                                fill="var(--color-primary)"
                                fill-opacity="0.3"
                                r="7"
                            ></circle>
                            <circle
                                :cx="node.x"
                                :cy="node.y"
                                fill="var(--color-primary)"
                                r="4"
                            ></circle>
                        </template>
                        <circle
                            v-else
                            :cx="node.x"
                            :cy="node.y"
                            fill="var(--color-surface)"
                            :r="node.muted ? 3 : 4"
                            stroke="var(--color-primary)"
                            stroke-width="2"
                        ></circle>
                    </template>
                </svg>
            </div>
            <!-- Date Axis -->
            <div
                class="flex items-center justify-between text-outline font-label-sm text-label-sm pt-space-xs"
            >
                <span
                    v-for="(lbl, lIdx) in chart1Telemetry.labels"
                    :key="lIdx"
                    >{{ lbl }}</span
                >
            </div>
        </div>

        <!-- Telemetry Chart 2: Global PR History 30d -->
        <div
            class="p-space-lg rounded-xl bg-surface-container-low border border-outline-variant/60 flex flex-col justify-between shadow-xs"
        >
            <div class="flex items-start justify-between gap-space-md mb-space-md">
                <div class="flex flex-col">
                    <div
                        class="flex items-center gap-space-xs text-primary font-label-sm text-label-sm uppercase tracking-wider"
                    >
                        <span class="material-symbols-outlined text-[16px]"
                            >monitoring</span
                        >
                        <span>Peso máximo por día (últimos 30 días)</span>
                    </div>
                    <h4
                        class="font-headline-md text-headline-md text-on-surface font-bold"
                    >
                        Tu PR diario a lo largo del tiempo
                    </h4>
                    <span class="font-body-sm text-body-sm text-outline">
                        Ponderación volumétrica por semana de descarga y pico
                    </span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                    <span
                        class="font-label-sm text-label-sm text-on-surface-variant"
                        >Peso (kg)</span
                    >
                </div>
            </div>

            <!-- Inline SVG Telemetry Curve 2 -->
            <div class="relative w-full h-56 pt-space-sm">
                <svg
                    class="w-full h-full overflow-visible"
                    fill="none"
                    preserveAspectRatio="none"
                    viewBox="0 0 500 180"
                >
                    <defs>
                        <linearGradient
                            id="chartGrad2"
                            x1="0"
                            x2="0"
                            y1="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="var(--color-secondary)"
                                stop-opacity="0.25"
                            ></stop>
                            <stop
                                offset="100%"
                                stop-color="var(--color-secondary)"
                                stop-opacity="0.0"
                            ></stop>
                        </linearGradient>
                    </defs>
                    <!-- Guide markers horizontal -->
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="20"
                        y2="20"
                    ></line>
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="60"
                        y2="60"
                    ></line>
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="100"
                        y2="100"
                    ></line>
                    <line
                        stroke="var(--color-outline-variant)"
                        stroke-width="1"
                        x1="0"
                        x2="500"
                        y1="140"
                        y2="140"
                    ></line>
                    <!-- Curve fill area -->
                    <path :d="chart2Telemetry.area" fill="url(#chartGrad2)"></path>
                    <!-- Smooth spline path -->
                    <path
                        :d="chart2Telemetry.line"
                        stroke="var(--color-secondary)"
                        stroke-linecap="round"
                        stroke-width="3"
                    ></path>
                    <!-- Chart Nodes -->
                    <circle
                        v-for="(node, nIdx) in chart2Telemetry.nodes"
                        :key="nIdx"
                        :cx="node.x"
                        :cy="node.y"
                        :fill="
                            node.isPeak
                                ? 'var(--color-tertiary)'
                                : node.isAccent
                                  ? 'var(--color-secondary)'
                                  : 'var(--color-surface)'
                        "
                        :r="node.isPeak ? 6 : node.isAccent ? 5 : 4"
                        :stroke="node.isPeak ? 'var(--color-tertiary)' : 'var(--color-secondary)'"
                        stroke-width="2"
                    ></circle>
                </svg>
            </div>
            <!-- Date Axis -->
            <div
                class="flex items-center justify-between text-outline font-label-sm text-label-sm pt-space-xs"
            >
                <span
                    v-for="(lbl, lIdx) in chart2Telemetry.labels"
                    :key="lIdx"
                    >{{ lbl }}</span
                >
            </div>
        </div>
    </section>
</template>
