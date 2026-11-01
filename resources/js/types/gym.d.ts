/**
 * Definiciones de tipos TypeScript globales para GymApp.
 * Usado por componentes Vue, composables y Pinia stores.
 */

export type UserRole = 'comun' | 'trainer' | 'coordinador' | 'recepcionista' | 'administrador';

export interface User {
    id: number;
    nick: string;
    name: string;
    email: string;
    role: UserRole;
    suspended?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Ejercicio {
    id: number;
    nombre: string;
    grupo_muscular: string;
    equipamiento?: string;
    descripcion?: string;
    gif_url?: string | null;
    video_url?: string | null;
    instrucciones?: string | null;
    musculos_secundarios?: string[];
    is_favorite?: boolean;
}

export type TipoSerie = 'efectiva' | 'calentamiento' | 'aproximacion' | 'drop_set' | 'al_fallo';

export interface PrescripcionBloque {
    tipo?: TipoSerie;
    series: number;
    reps: string | number;
    reps_min?: number;
    reps_max?: number;
    rir?: number | null;
    rpe?: number | null;
    descanso_segundos?: number;
    tempo?: string | null;
    notas?: string;
}

export interface RutinaItem {
    id?: number;
    rutina_id?: number;
    nivel: string;
    modalidad: string;
    dia: string;
    ejercicio_id?: number | null;
    ejercicio_nombre: string;
    series: number;
    reps_min?: number;
    reps_max?: number;
    descanso_min?: number;
    orden?: number;
    superserie_grupo?: number | null;
    notas?: string;
    prescripcion?: {
        bloques: PrescripcionBloque[];
        notas_generales?: string;
    };
}

export interface RutinaDia {
    nombre: string;
    ejercicios: RutinaItem[];
    busquedaEjercicio?: string;
    resultadosBusqueda?: Ejercicio[];
}

export interface HistorialRecord {
    id?: number;
    client_id?: string;
    user_id?: number;
    rutina_nombre: string;
    dia: string;
    ejercicio_id?: number | null;
    ejercicio_nombre: string;
    series_numero: number;
    reps: number;
    peso: number;
    completado: boolean;
    fecha: string;
    sesion_uuid?: string | null;
    tipo_serie?: TipoSerie;
    rpe?: number | null;
    rir?: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface WorkoutSession {
    id?: number;
    uuid: string;
    user_id: number;
    rutina_id?: number | null;
    rutina_nombre: string;
    dia: string;
    started_at: string;
    ended_at?: string | null;
    duration_seconds?: number;
    total_volume?: number;
    prs_count?: number;
    series_totales?: number;
    series_completadas?: number;
    estado?: 'activa' | 'finalizada' | 'descartada';
}

export interface ToastAction {
    label: string;
    onClick: () => void;
}

export interface ToastItem {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    title?: string;
    duration?: number;
    persistent?: boolean;
    action?: ToastAction;
}
