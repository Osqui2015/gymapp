# 🏋️ VisualGym — Plan de implementación

**Proyecto**: GymApp (Laravel 12 + Vue 3 + Pinia + MySQL)
**Dataset**: [hasaneyldrm/exercises-dataset](https://github.com/hasaneyldrm/exercises-dataset)
**Permiso**: confirmado por el autor para uso en nuestra app
**Fecha de inicio**: 14/09/2026

---

## 🎯 Objetivo

Integrar 1.324 ejercicios de fitness con metadata completa, GIFs animados, thumbnails e instrucciones en 10 idiomas dentro de GymApp, para usarlos como catálogo y permitir armar rutinas con búsqueda, filtros y vista detallada.

---

## 🧭 Visión general

```
1. Setup local      →  bajar dataset y dejarlo accesible
2. Backend Laravel  →  migration, model, seeder, API
3. Frontend Vue     →  store Pinia, catálogo, detalle
4. Assets/media     →  servir thumbnails y GIFs
5. Pulir            →  búsqueda, filtros, favoritos
```

---

## FASE 1 — Setup local

1. **Clonar el repo** en una carpeta hermana a tu proyecto (depth=1 para no bajar todo el historial):
   ```bash
   git clone --depth 1 https://github.com/hasaneyldrm/exercises-dataset.git I:\laragon\www\exercises-dataset
   ```

2. **Copiar solo lo que necesitás** dentro de tu Laravel:
   ```
   GymApp/
   ├── storage/app/public/exercises/
   │   ├── images/   ← copiá el contenido de images/ acá
   │   └── videos/   ← copiá el contenido de videos/ acá
   └── database/seeders/data/
       └── exercises.json  ← copiá data/exercises.json acá
   ```

3. **Crear link simbólico** para que `public/storage` apunte a `storage/app/public`:
   ```bash
   php artisan storage:link
   ```

---

## FASE 2 — Backend Laravel

### 2.1 Migración

Archivo: `database/migrations/2026_09_15_000001_create_exercises_table.php`

Columnas mínimas:
- `id` (string PK, ej `"0001"`)
- `name` (string)
- `category` (string indexable)
- `body_part` (string)
- `equipment` (string indexable)
- `target` (string)
- `muscle_group` (string nullable)
- `secondary_muscles` (JSON)
- `instructions` (JSON — los 10 idiomas)
- `instruction_steps` (JSON — los 10 idiomas)
- `media_id` (string nullable)
- `image_path` (string)
- `gif_path` (string)
- `attribution` (string)
- `timestamps`

### 2.2 Model

`app/Models/Exercise.php`
- `$fillable` con todos los campos
- casts: `secondary_muscles`, `instructions`, `instruction_steps` → `array`
- scopes útiles: `scopeByCategory()`, `scopeByEquipment()`, `scopeSearch($term)`

### 2.3 Seeder

`database/seeders/ExerciseSeeder.php`

- Lee `database/seeders/data/exercises.json`
- Inserta en chunks de 200 con `Exercise::insert()` para no matar la DB
- Mapea `image` → `image_path` y `gif_url` → `gif_path` (strippeando prefijo local)

Registrar en `DatabaseSeeder.php`:
```php
$this->call(ExerciseSeeder::class);
```

Correrlo:
```bash
php artisan db:seed --class=ExerciseSeeder
```

### 2.4 API (routes + controller)

**`routes/api.php`** (con prefijo `/api`):
```php
Route::middleware('auth')->prefix('exercises')->group(function () {
    Route::get('/', [ExerciseController::class, 'index']);     // listado paginado + filtros
    Route::get('/{id}', [ExerciseController::class, 'show']);  // detalle
});
```

**`app/Http/Controllers/ExerciseController.php`**:
- `index()`: paginación (24/página), filtros `?category=&equipment=&q=`, orden por nombre
- `show($id)`: devuelve el ejercicio con sus instrucciones (default `es`)

**Form Requests**:
- `IndexExerciseRequest` (validar filtros)

---

## FASE 3 — Frontend Vue

### 3.1 Store Pinia

`resources/js/stores/exercises.js`

State:
- `exercises: []`
- `current: null`
- `filters: { category, equipment, q }`
- `pagination: { page, lastPage, total }`

Actions:
- `fetchList({ page, filters })`
- `fetchOne(id)`
- `setFilter(key, value)`

Getters:
- `categories`, `equipments` (para popular dropdowns)

### 3.2 Componentes Vue

```
resources/js/components/exercises/
├── ExerciseCatalog.vue       ← grid con paginación + filtros
├── ExerciseCard.vue          ← card con thumbnail + nombre
├── ExerciseFilters.vue       ← barra de filtros
├── ExerciseDetailModal.vue   ← modal con GIF + instrucciones
└── ExercisePicker.vue        ← selector reusable (para crear rutinas)
```

### 3.3 Rutas Vue (si usás vue-router)

`resources/js/router/index.js`:
```js
{ path: '/exercises', component: ExerciseCatalog }
```

Si todavía no tenés vue-router, podés montar todo en una vista Blade primero y meterlo después.

---

## FASE 4 — Assets / media

Los GIFs y thumbnails ya quedan servidos por Laravel si los pusiste en `storage/app/public/exercises/{images,videos}/`.

En el JSON del dataset, las rutas son relativas tipo `images/0001-2gPfomN.jpg`. En tu seeder mapealas a:

```
images/0001-2gPfomN.jpg  →  /storage/exercises/images/0001-2gPfomN.jpg
videos/0001-2gPfomN.gif  →  /storage/exercises/videos/0001-2gPfomN.gif
```

Cuando el front los pida, los servís directo desde `/storage/...`. **Ojo**: 1.324 GIFs pesan bastante. Si te molesta el storage, podés:
- Servirlos desde CDN externo (recomendable para producción)
- O usar `videos/` solo cuando el usuario hace hover/click, lazy-load

---

## FASE 5 — Pulido

Una vez que la base funcione:
1. **Búsqueda full-text** con índice MySQL en `name` + `target` (opcional `FULLTEXT`)
2. **Filtros combinados** (categoría + equipment + búsqueda)
3. **Marcar favoritos** (tabla `user_favorite_exercises`)
4. **Historial "últimos usados"** para autocompletar cuando armás rutinas
5. **Vista previa del GIF** solo on-hover para no cargar 1.324 GIFs al scrollear

---

## 📁 Archivos concretos a crear/modificar

```
database/
├── migrations/2026_09_15_000001_create_exercises_table.php
└── seeders/
    ├── ExerciseSeeder.php
    └── data/exercises.json            ← viene del repo

app/
├── Models/Exercise.php
├── Http/Controllers/ExerciseController.php
└── Http/Requests/IndexExerciseRequest.php

routes/
└── api.php                            ← agregar rutas /api/exercises

resources/js/
├── stores/exercises.js                ← Pinia store
├── components/exercises/
│   ├── ExerciseCatalog.vue
│   ├── ExerciseCard.vue
│   ├── ExerciseFilters.vue
│   ├── ExerciseDetailModal.vue
│   └── ExercisePicker.vue
└── api/exercises.js                   ← cliente HTTP (axios)
```

---

## ⏱️ Estimación realista

| Fase | Tiempo aprox |
|------|-------------|
| 1. Setup | 10 min |
| 2. Backend (migración, model, seeder, API) | 1-2 hs |
| 3. Frontend (store + catálogo + detalle) | 2-3 hs |
| 4. Ajustes + filtros + búsqueda | 1 hs |
| 5. Pulido | depende de cuánto quieras |

---

## 🚦 Log de avance

- [x] Plan creado en `VisualGym.md`
- [x] **Fase 1**: clonando repo y copiando assets
  - Repo clonado: `I:\laragon\www\exercises-dataset`
  - 1.324 imágenes copiadas a `storage/app/public/exercises/images/` (8.46 MB)
  - 1.324 videos copiados a `storage/app/public/exercises/videos/` (122.78 MB)
  - `data/exercises.json` copiado a `database/seeders/data/` (16.72 MB)
  - `public/storage` link verificado (junction → storage/app/public)
- [x] **Fase 2**: backend Laravel
  - Migración `2026_09_15_000001_add_visualgym_fields_to_ejercicios_table.php` corrida
  - Modelo `Ejercicio` extendido (fillable, casts, scopes, accessors)
  - `VisualGymSeeder` creado y ejecutado: **1.324 ejercicios importados** (0 duplicados, 0 errores)
  - DB final: **1.616 ejercicios** (1.324 VisualGym + 292 legacy)
  - Endpoints nuevos en `EjercicioController`:
    - `GET /api/visualgym/facets` → body_parts, targets, equipamientos
    - `GET /api/visualgym/exercises` → catálogo paginado + filtros
    - `GET /api/visualgym/exercises/{externalId}` → detalle + instrucciones por idioma
  - Rutas registradas en `routes/api.php`
  - Probado con `php artisan serve`: todos los endpoints devuelven 200
- [x] **Fase 3**: frontend Vue
  - `resources/js/api/visualgym.js` — cliente axios (fetchFacets, fetchExercises, fetchExercise)
  - `resources/js/stores/visualgym.js` — Pinia store con state, getters, actions
  - `resources/js/components/visualgym/VisualGymCatalog.vue` — vista principal con grid + filtros + paginación
  - `resources/js/components/visualgym/VisualGymCard.vue` — card con thumbnail + GIF on hover/focus
  - `resources/js/components/visualgym/VisualGymDetailModal.vue` — modal con instrucciones por idioma (selector de 10 idiomas)
  - `resources/js/components/visualgym/VisualGymFilters.vue` — barra de filtros (búsqueda + 3 dropdowns + limpiar)
  - Registrado como `visualgym-catalog` en `app.js` (lazy-loaded)
  - Vista Blade `resources/views/visualgym.blade.php`
  - Ruta web `/visualgym` con middleware `auth` + `membership`
  - **Build OK**: chunk `VisualGymCatalog` = 19.72 kB (gzip 5.91 kB)
  - Probado end-to-end: página rinde 200, endpoints devuelven data, assets servibles
- [x] **Fase 5**: pulido
  - **Traducción EN→ES**: `app/Support/VisualGymI18n.php` con mapping de body_part (10), target (19) y equipamiento (28)
  - Backend devuelve `body_part_es`, `target_es`, `equipamiento_es` en listado y detalle
  - Facets ahora devuelven `{value, label_es}` para popular dropdowns en español
  - **Lazy load GIFs**: `resources/js/composables/usePrefetchGif.js` usa IntersectionObserver con `rootMargin: 200px` — pre-carga el GIF solo cuando el card entra al viewport
  - **Favoritos cruzados**:
    - Botón ⭐ en cada card (esquina sup. derecha) con `@click.stop` para no abrir el modal
    - Botón ⭐ en el header del modal
    - Store `toggleFavorite(ejercicio)` reusa `/api/ejercicios/{id}/favorite` (endpoint existente)
    - `is_favorite` agregado al listado y detalle cuando hay auth
    - Probado: toggle crea/borra favorito correctamente, DB refleja cambios
  - Build OK: chunk `VisualGymCatalog` actualizado (segunda build)
  - **Matching legacy → VisualGym** (15/09/2026):
    - Endpoint nuevo `GET /api/ejercicios/media?name=X` para resolver nombre legacy español → VisualGym
    - Usado por el modal de workout activo para mostrar GIF en tiempo real
    - Algoritmo de matching en `EjercicioController::ejercicioMediaByName`:
      1. Exact match en VisualGym (normalizado)
      2. Match en `NAME_ALIASES` (250 aliases, normalizado y substrings del más largo al más corto)
      3. Exact match en legacy
      4. Fuzzy match con expansión de sinónimos y filtro por equipamiento
    - `NAME_ALIASES` se cargó desde CSV curado del usuario (292 entradas legacy)
    - Cobertura final: **289/292 legacy con GIF de VisualGym** (98.97%)
      - 2 casos sin equivalente en dataset: `Dragon Flags` → `flag` (id 3303, mismo movimiento en barra vertical), `Remo Ergómetro (Rowing Machine)` → fallback legacy (no existe en dataset)
      - 1 caso resuelto con alias específico: `Press de banca inclinado` → `barbell incline bench press` (sin el alias específico, el matching parcial devolvía "barbell bench press" por substring más corto)
    - Tests verificados (todos pasan): Dragon Flags → flag, Press banca inclinado → barbell incline bench press, Press banca plano pesado → barbell bench press, Curl bíceps barra Z → ez barbell curl, Buenos días → barbell good morning, Hip thrust → barbell glute bridge, Bicicleta spinning → stationary bike walk, etc.
    - 12 scripts de debug purgados (renombrados a `_OLD.*`)

---

## 📌 Notas

- **Licencia media**: el código/datos son MIT (libre). Las imágenes/GIFs son © Gym visual — para producción propia podés redistribuirlas según el permiso que te dieron. Mantener `attribution` intacto en cada record.
- **Stack paths** (Laragon, Windows):
  - PHP: `I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe`
  - Composer: `I:\laragon\bin\composer\composer.phar`
  - Workspace: `I:\laragon\www\GymApp`
