import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import DashboardContent from './components/DashboardContent.vue';
import RutinasAccordion from './components/RutinasAccordion.vue';
import EjerciciosList from './components/EjerciciosList.vue';
import CrearRutina from './components/CrearRutina.vue';
import ConfiguracionPanel from './components/ConfiguracionPanel.vue';
import TrainerAlumnos from './components/TrainerAlumnos.vue';
import HistorialContent from './components/HistorialContent.vue';
import ProgresoContent from './components/ProgresoContent.vue';
import DiarioNutricion from './components/DiarioNutricion.vue';
import TrainerDashboard from './components/TrainerDashboard.vue';
import TrainerEjercicios from './components/TrainerEjercicios.vue';
import TrainerDuplicar from './components/TrainerDuplicar.vue';
import AdminStats from './components/AdminStats.vue';
import AdminMembresias from './components/AdminMembresias.vue';
import AdminAuditLogs from './components/AdminAuditLogs.vue';
import AdminImportExport from './components/AdminImportExport.vue';

const pinia = createPinia();
const app = createApp({});

app.use(pinia);
app.component('dashboard-content', DashboardContent);
app.component('rutinas-accordion', RutinasAccordion);
app.component('ejercicios-list', EjerciciosList);
app.component('crear-rutina', CrearRutina);
app.component('configuracion-panel', ConfiguracionPanel);
app.component('trainer-alumnos', TrainerAlumnos);
app.component('historial-content', HistorialContent);
app.component('progreso-content', ProgresoContent);
app.component('diario-nutricion', DiarioNutricion);
app.component('trainer-dashboard', TrainerDashboard);
app.component('trainer-ejercicios', TrainerEjercicios);
app.component('trainer-duplicar', TrainerDuplicar);
app.component('admin-stats', AdminStats);
app.component('admin-membresias', AdminMembresias);
app.component('admin-audit-logs', AdminAuditLogs);
app.component('admin-import-export', AdminImportExport);

const mountEl = document.getElementById('app');
if (mountEl) {
    app.mount(mountEl);
}