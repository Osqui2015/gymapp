# Producción — Finalización de Fases 3-7

**Última actualización:** 2026-08-27 (post commit `89a92dc`)

## Deploy a Hostinger (gym.tecnorexs.com)

### 1) Storage symlink (manual, `artisan storage:link` falla por `exec()` y `symlink()` deshabilitados)

```bash
cd ~/domains/gym.tecnorexs.com/public_html

# Backup si ya existe
[ -d public/storage ] && [ ! -L public/storage ] && mv public/storage public/storage.bak.$(date +%s)

# Crear symlink manualmente
ln -s ../storage/app/public public/storage
ls -la public/storage
```

### 2) Migrar las nuevas tablas (RIR/RPE + reschedule log)

```bash
cd ~/domains/gym.tecnorexs.com/public_html
php artisan migrate
```

Migraciones pendientes:
- `2026_08_27_030000_add_esfuerzo_to_historials_table` (esfuerzo_tipo + esfuerzo_valor)
- `2026_08_27_040000_add_reschedule_log_to_user_rutinas_table` (user_rutina_reschedules)

### 3) Caches de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos
chmod -R 775 storage bootstrap/cache
```

### 4) Verificación post-deploy

```bash
# Endpoints nuevos (con sesión/auth):
curl -s https://gym.tecnorexs.com/api/dashboard/today | jq .
curl -s https://gym.tecnorexs.com/api/stats/esfuerzo | jq .
curl -s https://gym.tecnorexs.com/api/historial/week-summary | jq .
curl -s https://gym.tecnorexs.com/api/user-rutina/available-days | jq .

# Cache TTL (debería ser < 1s en cache hit):
# - body-map: 5min
# - stats/*: 5min
# - heatmap: 10min
# - dashboard/today: 1min
```

### 5) Si algo falla (rollback)

```bash
php artisan migrate:rollback --step=2   # deshace las 2 nuevas
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Resumen de cambios

### Backend
- **3 endpoints nuevos**: `/api/stats/esfuerzo`, `/api/stats/estimated-1rm`, `/api/dashboard/today`
- **1 endpoint modificado**: `/api/historial/guardar` ahora acepta `esfuerzo_tipo` y `esfuerzo_valor`
- **2 endpoints nuevos (reschedule)**: `/api/user-rutina/reschedule` y `/api/user-rutina/available-days`
- **1 endpoint nuevo (week)**: `/api/historial/week-summary`
- **1 modelo nuevo**: `UserRutinaReschedule`
- **2 migraciones nuevas**: RIR/RPE columns + reschedule log table
- **6 archivos de test nuevos**: 41 tests PHP adicionales (171 → 212)

### Frontend
- **5 componentes nuevos**:
  - `EffortCard.vue` — card de esfuerzo RIR/RPE con sparkline + distribución
  - `OneRmChart.vue` — gráfico SVG de evolución de 1RM
  - `RescheduleButton.vue` — modal para reprogramar día
  - `HomeHero.vue` — banner de bienvenida con quick action
  - `WeekCalendar.vue` — calendario semanal con dots de actividad
- **3 componentes modificados**:
  - `HistorialContent.vue` — integra EffortCard, RescheduleButton, WeekCalendar, OneRmChart expandible
  - `DashboardContent.vue` — HomeHero arriba + RIR/RPE input en cada set
  - `dashboard/DashboardSeriesList.vue` — input RIR/RPE en set form (mobile + desktop)
- **Limpieza**: `.gitignore` excluye `scripts/debug/*.php` y `yo/`

### Bundle sizes
- HistorialContent: 110KB (gzip 31KB) — incluye body map, drilldown, charts
- ProgresoContent: 425KB (gzip 125KB) — recharts
- DashboardContent: 43KB (gzip 13KB) — incluye HomeHero
- BodyMap SVG paths: lazy loaded, 18-30KB por vista

### Tests
- **PHP**: 212/212 ✅ (subió de 171)
- **JS**: 118/118 ✅
- **Build**: vite build OK en 8.4s

## Próximos pasos opcionales (no incluidos)
- Export CSV/PDF con datos RIR/RPE incluidos
- Notificación push cuando el usuario rompe PR
- Mobile-optimized view del body map
- Selector de género (male/female) persistente en localStorage
