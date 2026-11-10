/**
 * Tema unificado de gráficos (Chart.js)
 * Paleta corporativa: Indigo primario (#6366F1) + Emerald para metas (#10B981)
 */
export const CHART_COLORS = {
    primary: '#6366F1',
    primaryLight: '#818CF8',
    primaryAlpha: (opacity = 0.2) => `rgba(99, 102, 241, ${opacity})`,
    goal: '#10B981',
    goalAlpha: (opacity = 0.2) => `rgba(16, 185, 129, ${opacity})`,
    textMuted: '#94A3B8',
    borderDark: 'rgba(255, 255, 255, 0.08)',
};
