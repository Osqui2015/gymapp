<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <Breadcrumbs :items="[
                { label: 'Inicio', href: '/dashboard' },
                { label: 'Configuración' },
            ]" />
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Configuración</h1>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-8">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'pb-4 px-6 text-sm font-semibold border-b-2 transition-all duration-200',
                        activeTab === tab.id
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <UserManagement
                v-show="activeTab === 'usuarios'"
                :users="users"
                :trainers="trainers"
                :currentUserId="currentUserId"
                :creando="creando"
                :guardando="guardando"
                :errorCrear="errorCrear"
                :successCrear="successCrear"
                :searchFilter="searchFilter"
                :currentPage="currentPage"
                :totalPages="totalPages"
                :mostrarModal="mostrarModal"
                :formularioEdit="formularioEdit"
                @crear="crearUsuario"
                @abrir-editar="abrirModalEditar"
                @cerrar-modal="cerrarModal"
                @guardar-edicion="guardarEdicion"
                @toggle-suspend="toggleSuspend"
                @eliminar="eliminarUsuario"
                @update:searchFilter="onSearchFilterChange"
                @update:currentPage="currentPage = $event"
            />

            <TrainerAssignment
                v-show="activeTab === 'trainer_assignment'"
                :trainerList="trainerList"
                :alumnoList="alumnoList"
                :selectedTrainer="selectedTrainer"
                :checkedAlumnos="checkedAlumnos"
                :guardando="guardandoAsignacion"
                :selectedAlumnosCount="selectedTrainerAlumnosCount"
                @select-trainer="selectTrainerForAssignment"
                @guardar-asignacion="guardarAsignacionMasiva"
                @seleccionar-todos="seleccionarTodosAlumnos"
                @desmarcar-todos="desmarcarTodosAlumnos"
                @update:checkedAlumnos="checkedAlumnos = $event"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useDebounce } from '../composables/useDebounce';

import UserManagement from './config/UserManagement.vue';
import TrainerAssignment from './config/TrainerAssignment.vue';
import Breadcrumbs from './Breadcrumbs.vue';

const toast = useToast();
const showToast = (message, type = 'success') => toast.add(message, type);

const tabs = [
    { id: 'usuarios', label: 'Gestión de Usuarios' },
    { id: 'trainer_assignment', label: 'Asignar Alumnos a Trainers' },
];

const activeTab = ref('usuarios');

// === User management state ===
const users = ref([]);
const trainers = ref([]);
const currentUserId = ref(null);
const creando = ref(false);
const guardando = ref(false);
const errorCrear = ref('');
const successCrear = ref('');
const mostrarModal = ref(false);
const searchFilter = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

const nuevoUsuario = ref({
    nick: '',
    name: '',
    email: '',
    telefono: '',
    password: '',
    role: '',
    trainer_id: null,
});

const formularioEdit = ref({
    id: null,
    nick: '',
    name: '',
    email: '',
    telefono: '',
    password: '',
    role: '',
    trainer_id: null,
});

// === Trainer assignment state ===
const trainerList = ref([]);
const alumnoList = ref([]);
const selectedTrainer = ref(null);
const checkedAlumnos = ref([]);
const guardandoAsignacion = ref(false);

const itemsPerPage = 10;

// === Data fetching ===
const cargarDatos = async () => {
    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                page: currentPage.value,
                search: searchFilter.value,
                per_page: itemsPerPage,
            },
        });

        if (response.data.users !== undefined) {
            users.value = response.data.users;
            totalPages.value = response.data.last_page;
            trainers.value = response.data.trainers || [];
            currentUserId.value = response.data.current_user_id;
        } else {
            users.value = response.data;
            totalPages.value = 1;
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
};

const cargarDatosAsignacion = async () => {
    try {
        const response = await axios.get('/api/admin/trainers-alumnos');
        trainerList.value = response.data.trainers || [];
        alumnoList.value = response.data.alumnos || [];

        if (selectedTrainer.value) {
            const updated = trainerList.value.find((t) => t.id === selectedTrainer.value.id);
            if (updated) selectTrainerForAssignment(updated);
        }
    } catch (error) {
        console.error('Error al cargar datos de asignación:', error);
    }
};

// === User actions ===
const crearUsuario = async (payload) => {
    errorCrear.value = '';
    successCrear.value = '';
    creando.value = true;
    try {
        await axios.post('/admin/users', payload);
        showToast('Usuario creado exitosamente', 'success');
        nuevoUsuario.value = {
            nick: '',
            name: '',
            email: '',
            telefono: '',
            password: '',
            role: '',
            trainer_id: null,
        };
        await cargarDatos();
    } catch (error) {
        if (error.response?.data?.errors) {
            errorCrear.value = Object.values(error.response.data.errors).flat().join(', ');
        } else {
            errorCrear.value = error.response?.data?.message || 'Error al crear usuario';
        }
        showToast('Error al crear usuario', 'error');
    } finally {
        creando.value = false;
        setTimeout(() => {
            successCrear.value = '';
            errorCrear.value = '';
        }, 5000);
    }
};

const abrirModalEditar = (user) => {
    formularioEdit.value = {
        id: user.id,
        nick: user.nick,
        name: user.name,
        email: user.email,
        telefono: user.telefono || '',
        password: '',
        role: user.role,
        trainer_id: user.trainer_id,
    };
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
};

const guardarEdicion = async (payload) => {
    guardando.value = true;
    try {
        const data = {
            name: payload.name,
            email: payload.email,
            telefono: payload.telefono,
            role: payload.role,
            trainer_id: payload.role === 'alumno' ? payload.trainer_id : null,
        };
        if (payload.password) data.password = payload.password;
        await axios.put(`/api/admin/users/${payload.id}`, data);
        await cargarDatos();
        cerrarModal();
        showToast('Usuario actualizado correctamente', 'success');
    } catch (error) {
        showToast(error.response?.data?.message || error.response?.data?.error || 'Error al guardar cambios', 'error');
    } finally {
        guardando.value = false;
    }
};

const toggleSuspend = async (user) => {
    const accion = user.suspended ? 'activar' : 'suspender';
    const confirmed = await toast.confirm(
        `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} al usuario "${user.name}"?`,
        { title: `${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario`, confirmLabel: `Sí, ${accion}` }
    );
    if (!confirmed) return;
    try {
        await axios.patch(`/api/admin/users/${user.id}/toggle-suspend`);
        user.suspended = !user.suspended;
        showToast(`Usuario ${user.suspended ? 'suspendido' : 'activado'} correctamente`, 'success');
    } catch (error) {
        showToast(error.response?.data?.message || error.response?.data?.error || 'Error al cambiar estado', 'error');
    }
};

const eliminarUsuario = async (user) => {
    const confirmed = await toast.confirm(
        `¿Eliminar al usuario "${user.name}"? Esta acción no se puede deshacer.`,
        { title: 'Eliminar usuario', confirmLabel: 'Sí, eliminar', type: 'error' }
    );
    if (!confirmed) return;
    try {
        await axios.delete(`/api/admin/users/${user.id}`);
        await cargarDatos();
        showToast('Usuario eliminado correctamente', 'success');
    } catch (error) {
        showToast(error.response?.data?.message || error.response?.data?.error || 'Error al eliminar usuario', 'error');
    }
};

// === Trainer assignment actions ===
const selectTrainerForAssignment = (trainer) => {
    selectedTrainer.value = trainer;
    checkedAlumnos.value = alumnoList.value
        .filter((a) => a.trainer_id === trainer.id)
        .map((a) => a.id);
};

const selectedTrainerAlumnosCount = computed(() => checkedAlumnos.value.length);

const seleccionarTodosAlumnos = () => {
    alumnoList.value.forEach((a) => {
        if (!checkedAlumnos.value.includes(a.id)) checkedAlumnos.value.push(a.id);
    });
};

const desmarcarTodosAlumnos = () => {
    checkedAlumnos.value = [];
};

const guardarAsignacionMasiva = async () => {
    if (!selectedTrainer.value) return;
    guardandoAsignacion.value = true;
    try {
        await axios.post(`/api/admin/trainers/${selectedTrainer.value.id}/assign-alumnos`, {
            alumno_ids: checkedAlumnos.value,
        });
        showToast('Asignación guardada correctamente', 'success');
        await cargarDatosAsignacion();
    } catch (error) {
        showToast(error.response?.data?.error || 'Error al guardar asignaciones', 'error');
    } finally {
        guardandoAsignacion.value = false;
    }
};

// === Search handling (con debounce) ===
const debouncedSearch = useDebounce(() => {
    currentPage.value = 1;
    cargarDatos();
}, 300);

const onSearchFilterChange = (val) => {
    searchFilter.value = val;
    debouncedSearch();
};

watch(currentPage, () => cargarDatos());
watch(activeTab, (newTab) => {
    if (newTab === 'trainer_assignment') cargarDatosAsignacion();
});

onMounted(() => cargarDatos());
</script>
