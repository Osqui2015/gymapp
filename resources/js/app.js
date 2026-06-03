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

const mountEl = document.getElementById('app');
if (mountEl) {
    app.mount(mountEl);
}