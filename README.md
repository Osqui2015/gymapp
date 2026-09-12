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

## Deploy a producción

Procedimiento unico y obligatorio. NO improvisar.

### Pre-requisitos

- Acceso SSH o cPanel File Manager al server de producción (`gym.tecnorexs.com`).
- Acceso a la base de datos MySQL.
- Node.js y npm instalados en tu maquina local (para el build).

### Paso a paso

1. **Build local** (en tu maquina, NO en el server):
```bash
npm run build
```

2. **Verificar que pasa los tests** (antes de subir nada):
```bash
npm run test:run
& "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan test
```

3. **Subir cambios** al server. Si tenés SSH:
```bash
# Cambios PHP/Vue/migrations: rsync o git pull (lo que uses)
git pull origin main
```

Si NO tenés SSH: usar cPanel File Manager o FileZilla para subir:
- Todo el contenido del repo **excepto** `node_modules/`, `vendor/`, `.env`, `public/build/`.
- Subir la nueva carpeta `public/build/` completa (con TODOS sus assets).
- Subir las nuevas migraciones si las hay.

4. **Limpiar caches del server** (CRITICO, sino los cambios no se ven):
```bash
php artisan optimize:clear
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

5. **Si hay migraciones nuevas**:
```bash
php artisan migrate --force
```

6. **Limpiar assets viejos del build anterior** (los hashes cambiaron):
```bash
# Ver que quedo en el server
ls public/build/assets/
# Borrar cualquier .js o .css que NO este en tu nuevo build
```

7. **Verificar**:
- Login con un usuario existente.
- Dashboard carga sin errores de consola.
- Historial se ve completo.
- Logout funciona.

8. **Si algo sale mal** (rollback):
- Re-subir el `public/build/` anterior.
- Revertir los cambios de PHP (git checkout HEAD~1).
- Limpiar caches de nuevo.

### Purga de Cloudflare

Si el server esta detras de Cloudflare (probable), despues del deploy:
- Purgar cache: Dashboard > Caching > Purge Everything.
- O purgar solo URLs especificas si sabes cuales son.

### Hard refresh en el browser

Despues de cada deploy, los usuarios deben hacer `Ctrl+Shift+R` (o `Cmd+Shift+R` en Mac) para evitar la cache del Service Worker.

### ⚠️ Que NO hacer

- **NO** hacer `npm install` o `npm run build` en el server compartido. No hay Node.js y no deberia estar.
- **NO** commitear `public/build/` a git. El `.gitignore` ya lo excluye.
- **NO** dejar assets viejos en `public/build/assets/`. Cada build genera hashes nuevos, los viejos quedan huerfanos.
- **NO** cambiar `APP_DEBUG=true` en produccion. Tiene que ser `false`.

### Variables de entorno criticas en produccion

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gym.tecnorexs.com
CACHE_DRIVER=database  # ya esta asi
SESSION_DRIVER=database  # ya esta asi
QUEUE_CONNECTION=database  # ya esta asi
```

### Procedimiento de rollback

Si despues de un deploy algo se rompe:

1. Revertir el codigo: `git revert HEAD` o `git checkout HEAD~1`.
2. Re-subir los archivos al server.
3. Si era una migracion: `php artisan migrate:rollback --step=1`.
4. Limpiar caches: `php artisan optimize:clear`.
5. Si era un build: re-subir el `public/build/` anterior.

## Base de Datos

### Migraciones

- `create_ejercicios_table` - Ejercicios disponibles (nombre, equipamiento, grupo_muscular, descripcion, url_img, url_video, visibilidad)
- `create_rutinas_table` - Rutinas por nivel (Principiante/Intermedio/Avanzado), modalidad (2/3/4 días), día, ejercicio, series, reps, descanso, orden
- `create_historials_table` - Historial de ejercicios completados por usuario
- `create_user_rutinas_table` - Rutina seleccionada por usuario (nivel, modalidad, dia_actual)
- `create_personal_access_tokens_table` - Token de Sanctum para API

### Backups automáticos (Nivel 5)

La app incluye un sistema de backup diario de la base de datos MySQL con retención configurable.

**Comandos disponibles:**

```bash
# Generar backup manualmente
php artisan db:backup
php artisan db:backup --retention-days=14

# Listar backups disponibles
ls -lh storage/app/backups/

# Restaurar (pide confirmación explícita)
php artisan db:restore                # interactivo
php artisan db:restore --latest       # usa el más reciente
php artisan db:restore --file=backup-2026-09-06_020000.sql.gz
```

**Automático:** el scheduler corre `db:backup --retention-days=7` todos los días a las 02:00 hs.
El archivo se guarda en `storage/app/backups/backup-YYYY-MM-DD_HHMMSS.sql.gz` (formato gzip).

**Configuración opcional vía `.env`:**

```dotenv
# Ruta explícita al binario mysqldump (si no está en PATH)
MYSQLDUMP_PATH=/usr/bin/mysqldump
# Ruta explícita al binario mysql (para restauración)
MYSQL_PATH=/usr/bin/mysql
```

**Restauración ante desastres:**

1. Bajar la app (`php artisan down`).
2. Confirmar conexión a la DB destino con el mismo `.env`.
3. Ejecutar `php artisan db:restore --latest` y confirmar.
4. Levantar la app (`php artisan up`).
5. Verificar login y endpoints clave.

**Importante:** el restore SOBREESCRIBE todos los datos. Hacer un backup fresco antes de restaurar en producción.

**Limpieza de huérfanos:**

```bash
# Detectar registros con user_id inválido (modo dry-run)
php artisan db:cleanup-orphans

# Eliminarlos (pide confirmación por tabla)
php artisan db:cleanup-orphans --prune

# Sin pedir confirmación
php artisan db:cleanup-orphans --prune --force
```

Útil cuando se hace hard-delete de usuarios sin cascade. Detecta huérfanos en
`historials`, `sesiones_entrenamiento`, `progresos`, `diario_nutricion`,
`user_rutinas`, `trainer_comments`, `user_rutina_reschedules`, `audit_logs`
y `notifications`.

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