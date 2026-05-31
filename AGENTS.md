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

## Routes
- `/login`, `/register` - guest routes
- `/dashboard` - auth + verified middleware
- `/profile` - auth middleware
