# Changelog

Todos los cambios notables del proyecto GymApp se documentan acá.

El formato sigue (parcialmente) [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/) cuando aplica.

---

## [Unreleased] — Sesión actual 2026-08-17

### 🟢 Added
- **D1 — Source of truth en `UserRutina`**: se elimina la denormalización de `nivel` y
  `modalidad`. Los accessors existentes pasan a leer **siempre** de la relación `rutina`
  (no más fallback a la columna denormalizada). Migración que dropea las columnas
  `user_rutinas.nivel` y `user_rutinas.modalidad`. Backwards-incompatible para código
  que escribía a esas columnas; ya no es necesario.
- **D2/D3 — `Historial.ejercicio_id` FK**: nueva columna + FK a `ejercicios` con
  backfill idempotente. `ejercicio_nombre` queda como string legacy para datos
  históricos previos al backfill (nunca más se escribe). Mismo patrón que ya se
  usó para `rutinas.ejercicio_id` (commit `cc56bf7`).
- **D4 — Policies faltantes**: creadas `UserPolicy`, `EjercicioPolicy`,
  `EjercicioClavePolicy`, `MetaPolicy`, `MedallaPolicy`, `MembresiaPolicy`,
  `ProgresoPolicy`, `DiarioNutricionPolicy`, `AuditLogPolicy`. Registradas en
  `AuthServiceProvider` con `Gate::policy(...)`. Complementan los Gates genéricos
  del `AppServiceProvider` con reglas por instancia (`view`, `update`, `delete`).
- **VAPID keys generadas**: `php artisan webpush:vapid` ejecutado y claves guardadas
  en `.env`. `PushService` listo para enviar notificaciones reales.

### ❌ Removed
- `resources/js/composables/useAuth.deprecated.js` (verificado sin imports).
- `resources/js/stores/user.deprecated.js` (verificado sin imports).
- `resources/js/sw-register.js` (verificado sin imports).

### 🛠 Tooling
- `scripts/webpush-vapid.ps1`: wrapper PowerShell que setea `OPENSSL_CONF` antes
  de invocar `artisan webpush:vapid`. Necesario en Laragon/Windows donde
  `C:\Program Files\Common Files\SSL\openssl.cnf` no existe por defecto y PHP
  no puede generar EC P-256 sin un config file válido. Apunta a la copia que
  trae Git for Windows.

---

## [Sin release] — Sesión de mejoras 2026-08-16

Cambios acumulados durante una sesión de auditoría + mejoras. No se bump-ea versión
porque el contrato público de la API no cambió (los cambios son internos/refactors).

### 🟢 Seguridad
- **`npm audit`**: 11 vulnerabilidades (1L/1M/6H/3C) → **0**. Migrado `axios 1.x→1.18+`, `jspdf 2.x→4.x`, `jspdf-autotable 3.x→5.x`, `dompurify 2.x→3.x`, `postcss`, `esbuild`, `form-data`, `nanoid`, `shell-quote` (vía `concurrently`).
- **Auth**: `useAuth` composable migrado a Pinia store (`useAuthStore`). 5 componentes actualizados, sistema paralelo eliminado.
- **Authorización**: 8 Gates definidos en `AppServiceProvider` (`admin`, `staff`, `trainer-or-admin`, `manage-users`, `view-audit-logs`, `view-global-stats`, `manage-import-export`, `assign-routines`, `view-alumno`).
- **Model strict mode en dev**: `Model::shouldBeStrict(!app()->isProduction())` activado. Falla rápido (en vez de silenciosamente) ante N+1, fills que descartan atributos, accesos a atributos inexistentes.
- **Email lowercase**: `prepareForValidation()` en las 3 FormRequests de Auth normaliza email a minúsculas antes de validar.

### ♻️ Refactor
- **FormRequests faltantes**: creadas `NewPasswordRequest`, `PasswordResetLinkRequest`, `RegisteredUserRequest`. Los 3 controllers de Auth ahora las inyectan en vez de validar con `Request` plano.
- **Middleware muerto**: `CheckRole` marcado como `@deprecated` (duplicado de `EnsureUserHasRole`).
- **vue-router quitado**: nunca se usó (los componentes se registran globalmente).
- **`useRutinaStore`**: state puro (sin `localStorage` en `state()`), acción `hidratar()` + `persistir()` explícitas. Llamadas desde `DashboardContent.vue` y `RutinasAccordion.vue` en `onMounted`.
- **`useAuth` → `useAuthStore` (Pinia)**: 5 componentes migrados con `storeToRefs`. Shims `useAuth.deprecated.js` y `user.deprecated.js` quedan como punteros históricos.
- **Web Push split**: lógica pura en `services/webPushService.js` + wrapper reactivo en `composables/useWebPush.js`. Shim `sw-register.js` mantiene compat.
- **Dark mode CSS**: selectores `[class*="bg-white"]` con `!important` reemplazados por `:where(...)` con specificity 0. Las utilities compuestas (`bg-white/50`, etc.) vuelven a ganar.
- **Denormalización `UserRutina`**: documentada con plan de cleanup. Accessors y migración quedan como decisión de producto (resuelta luego en [Unreleased] D1).

### 🐛 Bugs fixeados
- **Memory leak Chart.js**: `HistorialContent.vue` ahora destruye los charts en `onBeforeUnmount` (antes quedaban en memoria al cambiar de vista).
- **Email en mayúsculas rechazado en registro**: la regla `'lowercase'` solo validaba; ahora `prepareForValidation()` normaliza antes.
- **Doble auth system**: `useAuth` (composable) y `useUserStore` (Pinia) duplicaban funcionalidad. Consolidado en `useAuthStore`.
- **`useUserStore` muerto**: store Pinia definido pero nunca importado. Marcado como deprecated.
- **`@vue/tsconfig` no usado**: `package.json` tenía `typescript: ^6.0.3` que no se usa en runtime (Vite ya lo trae).

### 🧪 Tests
- **PHP — RegistrationTest**: +3 tests (email duplicado, password sin confirmation, email normalizado a lowercase).
- **PHP — PasswordResetTest**: +3 tests (email inválido, token requerido, password sin confirmation).
- **JS — Vitest setup**: `vitest.config.js` + `test-setup.js`. Alias `@/`, jsdom, limpieza entre tests.
- **JS — `auth.spec.js`**: 6 tests (init, getters, fetchUser con cache, force refetch, error handling, logout).
- **JS — `toast.spec.js`**: 14 tests (add con string/objeto, success/error/warning/info, durations, dismiss, persistent, auto-dismiss con fake timers).
- **JS — `rutina.spec.js`**: 10 tests (seleccionar, limpiar, setDiaActual, crearRutina, hidratar, round-trip a localStorage, JSON malformado).

**Total tests JS: 30** (antes: 0)

### 🌐 Resiliencia
- **Axios timeout global**: 30s por request (antes: infinito).
- **Axios retry**: reintentos automáticos en GET con error de red o 5xx, hasta 2 con backoff exponencial (1s, 2s). POST/PUT/DELETE **no** se reintentan (pueden duplicar efectos).

### 📁 Archivos nuevos
```
app/Http/Requests/Auth/NewPasswordRequest.php
app/Http/Requests/Auth/PasswordResetLinkRequest.php
app/Http/Requests/Auth/RegisteredUserRequest.php
resources/js/composables/useWebPush.js
resources/js/stores/auth.js
resources/js/services/webPushService.js
resources/js/stores/auth.spec.js
resources/js/stores/toast.spec.js
resources/js/stores/rutina.spec.js
resources/js/test-setup.js
vitest.config.js
CHANGELOG.md
```

### 📁 Archivos modificados (no exhaustivo)
```
app/Http/Controllers/Auth/{NewPassword,PasswordResetLink,RegisteredUser}Controller.php
app/Http/Middleware/CheckRole.php (deprecado)
app/Models/UserRutina.php (documentación de denormalización)
app/Providers/AppServiceProvider.php (Gates + shouldBeStrict)
package.json (vue-router out, jspdf 4, scripts test)
resources/css/app.css (dark mode :where())
resources/js/bootstrap.js (timeout + retry + interceptors)
resources/js/components/DashboardContent.vue
resources/js/components/EjerciciosList.vue
resources/js/components/HistorialContent.vue (cleanup chart + jspdf 4)
resources/js/components/RutinasAccordion.vue
resources/js/components/TrainerAlumnos.vue
resources/js/stores/rutina.js
resources/js/composables/useAuth.js → renamed to .deprecated.js
resources/js/stores/user.js → renamed to .deprecated.js
resources/js/sw-register.js (deprecado, re-exporta del service)
tests/Feature/Auth/RegistrationTest.php
tests/Feature/Auth/PasswordResetTest.php
```

---

## [Sprint 2026-06-21] — Mejoras FE + Push real (commit `cc56bf7`)

Sprint grande de features de front-end y push real. Se rompía nada del API.

### 🟢 Added (mejoras 1.9 / 2.2 / 2.5 / 3.5 / 3.6 / 8.1 / 8.6 / 8.7)

| # | Mejora | Archivo(s) |
|---|--------|-----------|
| 1.9 | Pull-to-refresh mobile | `components/historial/HistorialPullRefresh.vue`, `composables/usePullToRefresh.js` |
| 2.2 | Gestos swipe | `composables/useSwipe.js`, usado en `RutinasAccordion.vue` |
| 2.5 | Vista mobile quick input | `components/rutinas/MobileQuickSeriesInput.vue` |
| 3.5 | TypeScript (parcial) | `composables/useDebounce.ts` (único .ts, resto en .js) |
| 3.6 | DataTable con paginación | `components/ResponsiveTable.vue`, `components/config/Paginador.vue` |
| 8.1 | Comentarios trainer realtime | `composables/useRealtimeComments.js`, `Api/TrainerCommentController.php`, `laravel-echo` + `pusher-js` |
| 8.6 | Exportar historial a PDF | `HistorialContent.vue::exportarPDF()` con jspdf 4 + autoTable 5 (lazy loaded) |
| 8.7 | Push notifications real | `app/Services/PushService.php` con `minish/web-push`, `app/Console/Commands/GenerateVapidKeys.php`, `Api/PushSubscriptionController.php` |

### ♻️ Refactor
- **Modelo `Rutina`**: agregada FK `ejercicio_id` con backfill. Se mantiene
  `ejercicio_nombre` por compat (legacy). Misma estrategia se replica luego
  para `Historial` en [Unreleased] D2/D3.
- **Modelo `UserRutina`**: documentada la denormalización de `nivel/modalidad`
  con accessors de fallback. Resuelta en [Unreleased] D1.
- **Provider `AppServiceProvider`**: agregados Gates de autorización.
- **Service `PushService`**: integración completa con `minish/web-push`,
  manejo de 404/410 (limpia suscripciones expiradas), timeouts razonables,
  `setReuseVAPIDHeaders` para retry del cliente.

### 🐛 Bugs fixeados
- **`webpush:vapid` fallaba en PHP 8.3 / OpenSSL 3.x** (commit `48b0cd3`): las
  coordenadas EC P-256 venían como string decimal gigante; sin GMP/BCMath no se
  podía convertir. Solución: aritmética de strings pura.
- **`webpush:vapid` fallaba con binario crudo** (commit `cfa7686`): algunas
  builds de OpenSSL retornan las coordenadas como bytes binarios sin formato.
  Solución: fallback final a `bin2hex()`.
- **Acordeones de rutinas Personalizadas/Comunitarias no se expandían** (commit
  `140a794`): faltaban los handlers `@toggle` y `@toggle-dia` en
  `RutinasAccordion.vue`. Las instancias no escuchaban los eventos emitidos
  por `RutinaAcordeon`.

### 📁 Archivos nuevos (sprint)
```
app/Console/Commands/GenerateVapidKeys.php
app/Http/Controllers/Api/PushSubscriptionController.php
app/Http/Controllers/Api/TrainerCommentController.php
app/Models/PushSubscription.php
app/Models/TrainerComment.php
app/Services/PushService.php
database/migrations/2026_06_07_000000_add_share_token_to_rutinas_table.php
database/migrations/2026_06_08_000000_create_push_subscriptions_table.php
database/migrations/2026_06_08_000001_create_trainer_comments_table.php
database/migrations/2026_08_16_000000_add_ejercicio_id_to_rutinas_table.php
database/migrations/2026_08_16_000001_backfill_ejercicio_id_on_rutinas.php
resources/js/components/historial/HistorialPullRefresh.vue
resources/js/components/rutinas/MobileQuickSeriesInput.vue
resources/js/composables/useDebounce.ts
resources/js/composables/usePullToRefresh.js
resources/js/composables/useRealtimeComments.js
resources/js/composables/useSwipe.js
```

---

## Decisiones de producto — estado actual

| # | Decisión | Estado | Resuelto en |
|---|----------|--------|-------------|
| D1 | Denormalización `UserRutina.nivel/modalidad` | ✅ Resuelto (source of truth) | [Unreleased] 2026-08-17 |
| D2/D3 | `Rutina.ejercicio_nombre` y `Historial.ejercicio_nombre` → FK | ✅ Resuelto | Sprint 2026-06-21 (Rutina) + [Unreleased] (Historial) |
| D4 | Policies específicas por modelo | ✅ Resuelto | [Unreleased] 2026-08-17 |
| D5 | Push real (integrar `minish/web-push`) | ✅ Resuelto | Sprint 2026-06-21 |

---

## Convenciones para próximas entradas

- **🟢 Added**: features nuevas
- **♻️ Changed**: cambios en código existente
- **🐛 Fixed**: bug fixeado
- **🗑️ Deprecated**: marcado para remover
- **❌ Removed**: removido en esta versión
- **🔒 Security**: fix de seguridad
- **⚠️ Breaking**: cambio que rompe compat

Cuando el proyecto empiece a tener releases, usar:
- `[Unreleased]` para cambios en desarrollo
- `[X.Y.Z] - YYYY-MM-DD` para releases taggeados
- Mantener secciones `Added / Changed / Fixed / Security` por release
