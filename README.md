# GymApp

Aplicación web para gestión de rutinas y ejercicios (Laravel + Vue 3 + Vite + Tailwind CSS).

## Stack

- **Backend**: Laravel 12
- **PHP**: 8.2
- **Base de datos**: MySQL
- **Frontend**: Vue 3, Vite, Pinia, Tailwind CSS 4
- **Autenticación**: Laravel Breeze + Sanctum (API tokens)
- **Testing**: PHPUnit

## Requisitos

- PHP 8.2
- Composer
- Node.js + npm
- MySQL

## Instalación

1. Clonar el repo y entrar al directorio

2. Instalar dependencias:
```bash
composer install
npm install
```

3. Copiar y configurar `.env`:
```bash
cp .env.example .env
```

4. Configurar en `.env` la conexión a MySQL (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

5. Generar key y migrar:
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
```

6. Levantar servidor y assets:
```bash
npm run build
php artisan serve
```

## Base de Datos

### Migraciones

- `create_ejercicios_table` - Ejercicios disponibles (nombre, equipamiento, grupo_muscular, descripcion, url_img, url_video, visibilidad)
- `create_rutinas_table` - Rutinas por nivel (Principiante/Intermedio/Avanzado), modalidad (2/3/4 días), día, ejercicio, series, reps, descanso, orden
- `create_historials_table` - Historial de ejercicios completados por usuario
- `create_user_rutinas_table` - Rutina seleccionada por usuario (nivel, modalidad, dia_actual)
- `create_personal_access_tokens_table` - Token de Sanctum para API

### Modelos

- `User.php` - Usuario con `HasApiTokens` (Sanctum), relación `rutinaSeleccionada()`
- `Ejercicio.php` - Ejercicio individual
- `Rutina.php` - Rutina con relación `ejercicio()` (pertenece a Ejercicio por nombre)
- `Historial.php` - Registro de ejercicio completado
- `UserRutina.php` - Rutina guardada del usuario (nivel, modalidad, dia_actual)

### Seeders

- `DatabaseSeeder` - Ejecuta todos los seeders
- `EjercicioSeeder` - 195 ejercicios pre-cargados
- `RutinaSeeder` - 216 rutinas (Principiante/Intermedio/Avanzado × 2/3/4 días × días)

## Rutas

### Web (requieren autenticación)
- `GET /dashboard` - Panel principal con rutina seleccionada
- `GET /rutinas` - Lista de todas las rutinas conaccordion
- `GET /ejercicios` - Lista de ejercicios con paginación
- `GET /profile` - Editar perfil de usuario

### API
- `GET /api/rutinas` - Obtener todas las rutinas (filtro por nivel/modalidad)
- `GET /api/ejercicios` - Lista de ejercicios con paginación
- `POST /api/ejercicios` - Crear ejercicio
- `DELETE /api/ejercicios/{id}` - Eliminar ejercicio
- `GET/POST /api/user-rutina` - Guardar/obtener rutina seleccionada del usuario
- `POST /api/user-rutina/dia` - Actualizar día actual

### Autenticación (Breeze)
- `GET/POST /login` - Inicio de sesión
- `POST /logout` - Cerrar sesión
- `GET/POST /register` - Registro de usuario
- `GET /forgot-password` - Recuperar contraseña

## Frontend (Vue 3 + Pinia)

### Componentes

- `RutinasAccordion.vue` - Muestra rutinas agrupadas por nivel → modalidad → día con tablas de ejercicios. Permite seleccionar rutina guardándola en `user_rutinas`.
- `DashboardContent.vue` - Muestra ejercicios del día seleccionado con checkboxes para completar. Navega entre días y guarda progreso.
- `EjerciciosList.vue` - Lista de ejercicios con búsqueda y paginación (20 por página).

### Store (Pinia)

- `resources/js/stores/rutina.js` - Estado de la rutina seleccionada (`seleccionada`, `diaActual`)

### Estilos

- Tailwind CSS v4 con `@import "tailwindcss"`
- Soporte para modo oscuro (clases `dark:`)
- Componentes con gradientes, sombras y bordes redondeados

## Comandos útiles

```bash
# Desarrollo
npm run dev      # Vite hot reload
php artisan serve # Servidor Laravel

# Build producción
npm run build

# Base de datos
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Limpiar cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Características Principales

### Gestión de Rutinas
- 3 niveles: Principiante, Intermedio, Avanzado
- 3 modalidades: 2 días, 3 días, 4 días
- Cada día tiene ejercicios específicos con series, reps y descanso
- Selección de rutina guarda en BD (`user_rutinas`) para persistencia entre sesiones

### Dashboard
- Muestra ejercicios del día actual
- Checkbox para marcar completados
- Botones para navegar entre días
- Guarda día actual en `user_rutinas`

### Ejercicios
- Lista completa con búsqueda por nombre/equipamiento
- Paginación de 20 en 20
- Agregar/eliminar ejercicios

### Autenticación
- Login/registro con Laravel Breeze
- Sanctum para API tokens
- Middleware `auth:sanctum` en rutas protegidas

## Notas

- El campo `nick` es usado para login en lugar de email
- La rutina se guarda por usuario en `user_rutinas` para persistencia entre sesiones
- Los ejercicios se relacionan con rutinas por nombre (`ejercicio_nombre`)