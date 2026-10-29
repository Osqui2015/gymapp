# GymApp - Laravel + Vue 3 + Vite + Pinia

## Stack
- Laravel 12 + Tailwind CSS (vite build)
- Vue 3 + Vite + Pinia (SPA mode via `#app` mount in Blade)
- MySQL (Laragon: `I:\laragon\bin\mysql\mysql-8.0.30-winx64`)
- PHP 8.2 via Laragon: `I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe`
- Composer via Laragon: `I:\laragon\bin\composer\composer.phar`

## Key Commands

```bash
# Development (Vite hot reload)
npm run dev

# Production build
npm run build

# Laravel artisan (full path on Windows)
& "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" "I:\laragon\www\GymApp\artisan" <command>

# Run migrations
& "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" "I:\laragon\www\GymApp\artisan" migrate

# Composer (full path on Windows)
& "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" "I:\laragon\bin\composer\composer.phar" <command>
```

## Auth System
- Login uses **nick** field (not email)
- Users table has: `id`, `nick` (unique), `name`, `email`, `password`, `role`, `suspended`
- Login controller: `app\Http\Controllers\Auth\AuthenticatedSessionController.php`
- Login request: `app\Http\Requests\Auth\LoginRequest.php` (authenticates via `nick`)
- Default admin: nick=`admin`, password=`password`

## Vue Integration
- Vue entry: `resources/js/app.js` creates app and mounts to `#app`
- Pinia store: `createPinia()` configured in `app.js`
- Vite config: `vite.config.js` includes `@vitejs/plugin-vue`

## Database
- MySQL database: `gym_app` on Laragon
- Session/queue/cache use database driver

## Deploy a producción (NO romper datos)
Producción: `gym.tecnorexs.com` → `domains/gym.tecnorexs.com/public_html/`.

- `public/build/` está versionado en git: los assets compilados se commitean.
  En producción NO hay que correr `npm ci` ni `npm run build` (genera hashes
  distintos y deja el repo sucio).
- NUNCA `migrate:fresh`, `migrate:refresh` ni `db:seed` sin `--class`.
  `DatabaseSeeder` crea usuarios, historiales y ejercicios clave: correrlo
  duplica filas. Para seeders puntuales usar siempre `--class=X --force`
  (en producción `db:seed` pide confirmación "APPLICATION IN PRODUCTION").
- Los seeders de rutinas deben ser idempotentes y BORRAR sólo sus propias
  filas (por `created_by` + `nivel` + `modalidad`), nunca las de otros.
- Invariante de `rutinas`: la suma de `series` de `prescripcion.bloques`
  tiene que coincidir EXACTAMENTE con la columna `series`, y `notas` tiene
  que derivar lo mismo que `prescripcion`
  (`App\Services\RutinaPrescripcionService` ↔ `resources/js/utils/rutinaPrescripcion.js`).

### Procedimiento real de deploy (Hostinger)
El server de producción **NO tiene repo git**: no hay `git pull` ni remote
configurado. El deploy es manual, subiendo un paquete con las rutas exactas
del repo (`.tar.gz` o `.zip`) por el File Manager / FTP.

1. Backup de la DB **antes** de migrar: `mysqldump` (o export de phpMyAdmin).
   Backup de código: `tar -czf ~/backups/app_pre_<fecha>.tar.gz app database/migrations public .env`.
2. En el server (`~/domains/gym.tecnorexs.com/public_html`):
   ```bash
   mv public/build public/build.bak.$(date +%Y%m%d_%H%M)   # el build viejo ES el rollback
   tar -xzf deploy.tar.gz -C .
   ```
3. `php artisan config:clear` **ANTES** de `migrate`: si quedó una
   `config:cache` con una `DB_PASSWORD` vieja, Laravel ignora el `.env` y
   `migrate` muere con `Access denied for user ... (using password: YES)`.
4. `php artisan migrate --force` → `php artisan webpush:vapid --no-interaction`
   → `php artisan optimize:clear` → `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. Verificar: 0 colisiones sobre `(user_id, rutina_nombre, dia, ejercicio_nombre,
   series_numero, fecha)`, catálogo `created_by IS NULL` = 238 rutinas,
   `favicon.ico` ~15 KB (antes era 0).
6. Si cambian los assets, **bumpear `VERSION` en `public/sw.js`**: el service
   worker cachea `/favicon.ico` y `/icons/*` con CacheFirst (URLs estables, sin
   hash) y sin bumpear la versión los usuarios siguen viendo los iconos viejos.
7. Después: purgar cache de Cloudflare y pedir recarga fuerte a los usuarios
   con la PWA instalada.

## Invariante de idempotencia del historial (NO romper)
- Una fila de `historials` es única por
  `(user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha)`.
  Lo garantiza el índice UNIQUE `uq_historials_idempotencia` (migración
  `2026_10_29_000000_fix_historials_idempotencia_y_fecha`).
- La `fecha` es PARTE de la clave a propósito: registrar el mismo set en otra
  fecha tiene que crear una fila nueva, no pisar la sesión anterior. Sin
  `fecha` en la clave, reentrenar el mismo set sobreescribía el historial y
  todos los KPIs (volumen, 1RM, rachas, heatmap) subcontaban.
- `HistorialController::guardar()` resuelve la fila con `whereDate('fecha', ...)`
  (no `where('fecha', ...)`): la columna es DATE pero el cast `date` del modelo
  la escribe como `Y-m-d H:i:s`, y en SQLite (tests) la comparación exacta no
  matchearía.
- En MySQL el índice se crea con prefijos de 64 caracteres: la clave completa
  con VARCHAR(255) utf8mb4 excede el límite de 3072 bytes de InnoDB.
- La tabla `historials_reparacion_fecha` guarda el `fecha` original de las
  filas que la migración reparó: sirve para auditar y para revertir.

## Routes
- `/login`, `/register` - guest routes
- `/dashboard` - auth + verified middleware
- `/profile` - auth middleware
