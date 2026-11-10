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

### El scheduler necesita un cron en el server
`bootstrap/app.php` define 4 tareas diarias. Si el cron no está configurado en
el server **no corre ninguna** (ni el backup diario de la base):

| Tarea | Cuándo | Qué hace |
|---|---|---|
| `membresias:actualizar-estados` | 00:05 | marca membresías vencidas (período de gracia) |
| `db:backup --retention-days=7` | 02:00 | backup comprimido de la DB + rotación |
| `reminders:send` | 09:00 | recordatorios de membresía por vencer + inactividad |
| `plateaus:detect` | 10:00 | detecta mesetas de progreso y notifica |

En Hostinger (cPanel → Cron Jobs), una entrada **por minuto**:

```bash
* * * * * cd /home/<usuario>/domains/gym.tecnorexs.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Verificar con `php artisan schedule:list` (debe listar las 4 tareas con su
"Next Due").

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

## Rangos de `series_numero` (NO romper)
- El índice UNIQUE **NO incluye `tipo_serie`**, así que el calentamiento y la
  serie efectiva N NO pueden compartir `series_numero` o se pisan entre sí
  (el warmup se sobreescribía con la efectiva y el volumen subcuentaba).
- Se separan por RANGOS dentro de la misma columna:
  - series efectivas: `1 .. 50`
  - calentamientos: `51 .. 99` (offset de 50)
- Espejo backend/frontend: `Historial::OFFSET_CALENTAMIENTO` /
  `Historial::MAX_SERIES_NUMERO` (PHP) y `resources/js/utils/seriesNumero.js`
  (`OFFSET_CALENTAMIENTO`, `numeroCalentamientoPersisted()`,
  `numeroSerieParaDisplay()`).
- El backend valida `series_numero` con `min:1|max:99`. Si se sube el offset,
  hay que subir el `max` en `HistorialController`.
- Para mostrar el número al usuario hay que usar `numeroSerieParaDisplay()`
  (convierte 51 → "Calentamiento 1"). Mostrar el valor crudo muestra "51".

## Fechas: siempre hora local del gym (NO usar `toISOString()` para fechas)
- `new Date().toISOString().split('T')[0]` devuelve la fecha en **UTC**. Con el
  gym en `America/Argentina/Tucuman` (UTC-3), entre las 21:00 y las 00:00
  devuelve el día SIGUIENTE: la serie se guardaba con fecha de mañana (rompía
  idempotencia y rachas) y los filtros "últimos 30 días" perdían el hoy.
- Usar `resources/js/utils/fecha.js`: `hoyLocal()`, `fechaLocalHaceDias()`,
  `inicioDeAnioLocal()`.
- En backend, `now()` ya es local (la app corre con la tz del gym): usarlo
  directamente, nunca `Carbon::now()->utc()`.

## Carbon 3 devuelve float en `diffInDays()` (NO comparar con `===`)
- `diffInDays()` devuelve `double` (`1.0`). `=== 1` es SIEMPRE false:
  `StatsService::calcLongestStreak()` devolvía 1 para todos los usuarios.
- Comparar con `(int) $x->diffInDays($y) === 1`.
- `Membresia::getDiasRestantes()` también castea a `(int)`: sin el cast
  devolvía `10.000000000023148` días en vez de 10.

## CSP: nonces, no `unsafe-inline` (NO revertir)
- `app/Http/Middleware/SecurityHeadersMiddleware.php` genera un nonce por
  request y lo comparte con las vistas vía `view()->share('cspNonce', ...)`.
- Los `<script>` inline de los layouts DEBEN declarar
  `nonce="{{ $cspNonce ?? '' }}"`. Sin eso la CSP los bloquea.
- `'unsafe-eval'` fue ELIMINADO de `script-src`: Alpine.js ya no está en el
  proyecto (no está en package.json ni en el bundle) y Vue usa el runtime
  compilado (sin runtime template compiler), así que nadie necesita eval.
- `'unsafe-inline'` sigue en `style-src` (Tailwind y estilos inline de Vue).
- El middleware va con `prepend` (NO `append`) en `bootstrap/app.php`: el
  nonce tiene que existir ANTES de que el controller renderice la vista.
  Con `append` el nonce llega tarde y los scripts inline se bloquean.
- Los `<script src="...">` externos NO necesitan nonce: la CSP los permite
  por origen (`'self'`, `https://unpkg.com`, etc.).

## Polling: usar `usePolling` (NO `setInterval` a mano)
- `resources/js/composables/usePolling.js` centraliza el polling con:
  pausa cuando la pestaña está oculta (`visibilitychange`), backoff
  exponencial ante errores, y no solapamiento de requests.
- Consumidores actuales: `NotificationBell.vue` (60s), `ChatPanel.vue` (15s),
  `useRealtimeComments.js` (30s, solo si no hay WebSocket).
- Un `setInterval` plano sigue pegándole al servidor con la pestaña oculta
  y sin frenar cuando el backend se cae.

## Scope por usuario en datos locales (NO compartir entre socios)
- `IndexedDB` de series offline: `gymapp-offline-<userId>`
  (`resources/js/composables/useOfflineDB.js`).
- `localStorage` de sesión activa: `gymapp:training-session:v1:u<userId>`
  (`resources/js/stores/trainingSession.js`).
- En un dispositivo compartido (gimnasio, recepción) un scope compartido
  hacía que las series pendientes de A se guardaran en el historial de B.
- Al hacer logout se purga la DB offline del usuario que se va.

## Vue: `app._context` es API interna (NO usarla)
- `resources/js/app.js` mantiene su propio mapa `registro` (Map de tag →
  componente). Antes leía `app._context.components[tag]`, que no está
  documentada y puede romper entre minors de Vue.
- Todos los montajes pasan por `mountApp({ el, component, props })`, que
  centraliza `createApp` + `use(pinia)` + `registerErrorHandler` + `mount`.
  Olvidar el `use(pinia)` rompe los stores en silencio.

## Routes
- `/login`, `/register` - guest routes
- `/dashboard` - auth + verified middleware
- `/profile` - auth middleware
