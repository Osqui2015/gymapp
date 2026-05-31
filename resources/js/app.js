import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import DashboardContent from './components/DashboardContent.vue';
import RutinasAccordion from './components/RutinasAccordion.vue';
import EjerciciosList from './components/EjerciciosList.vue';
import CrearRutina from './components/CrearRutina.vue';

const pinia = createPinia();
const app = createApp({});

app.use(pinia);
app.component('dashboard-content', DashboardContent);
app.component('rutinas-accordion', RutinasAccordion);
app.component('ejercicios-list', EjerciciosList);
app.component('crear-rutina', CrearRutina);

const mountEl = document.getElementById('app');
if (mountEl) {
    app.mount(mountEl);
}