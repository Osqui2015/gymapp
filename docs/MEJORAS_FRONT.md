# 🎨 Mejoras de Front-End

Listado completo de mejoras sugeridas, organizadas por categoría con prioridad asignada.

**Leyenda de prioridad:**
- ⭐ = alta
- ☆ = media
- ✦ = baja

**Estado:** ✅ = hecho · ⚠️ = hecho parcialmente · ❌ = pendiente

> **Última actualización:** 2026-08-17. El sprint `cc56bf7` (2026-06-21) implementó
> 1.9, 2.2, 2.5, 3.5, 3.6, 8.1, 8.6, 8.7 — este doc quedó desactualizado entre jun y
> ago. Ahora refleja el estado real.

---

## 🎯 1. UX / Interacción del usuario (lo que más se nota)

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 1.1 | **Sistema de toasts/notifications centralizado** | ⭐⭐⭐ | ✅ `stores/toast.js` + `useToast.js` con `success/error/warning/info/confirm()` (Promise). Listeners globales para 401/403/419/422/500 |
| 1.2 | **Skeletons (loading states)** | ⭐⭐⭐ | ✅ `BaseSkeleton.vue` (5 variantes) + fade transition |
| 1.3 | **Confirmaciones con undo** | ⭐⭐⭐ | ✅ `useUndoable.js` (optimistic + commit diferido). Usado en 5 componentes |
| 1.4 | **Empty states** con CTA | ⭐⭐⭐ | ✅ `EmptyState.vue` |
| 1.5 | **Búsqueda global** en el header | ⭐⭐ | ✅ `GlobalSearch.vue` con `⌘K/Ctrl+K`. Backend `/api/search` cacheado. Resultados categorizados con navegación por teclado |
| 1.6 | **Toggle de dark mode** | ⭐⭐ | ✅ `DarkModeToggle.vue` + anti-FOUC + localStorage |
| 1.7 | **Breadcrumbs** | ⭐ | ✅ En 16 páginas Vue + profile |
| 1.8 | **Keyboard shortcuts** | ☆ | ✅ `useKeyboardShortcuts.js` estilo Gmail (g+d/r/e/h/p/a/s, ?=ayuda). Se desactivan en inputs |
| 1.9 | **Pull-to-refresh** en mobile | ✦ | ✅ `HistorialPullRefresh.vue` + `usePullToRefresh.js`. Montado en `HistorialContent.vue`. Extensible a otros listados |

## 📱 2. Mobile & Responsive

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 2.1 | **Bottom navigation bar** | ⭐⭐⭐ | ✅ `mobile-bottom-nav.blade.php` con sheet "Menú" |
| 2.2 | **Gestos swipe** | ⭐⭐ | ✅ `useSwipe.js` usado en `RutinasAccordion.vue` |
| 2.3 | **FAB** en vistas de lista | ⭐⭐ | ✅ En `RutinasAccordion`, `EjerciciosList`, `TrainerAlumnos` |
| 2.4 | **Mejorar tablas en mobile** | ⭐⭐ | ✅ `ResponsiveTable.vue` en 4+ lugares |
| 2.5 | **Vista mobile dedicada quick input** | ⭐⭐ | ✅ `MobileQuickSeriesInput.vue` (295 líneas) usado en `RutinasAccordion.vue` |

## 🧩 3. Componentización & arquitectura

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 3.1 | **Refactorizar componentes grandes** | ⭐⭐⭐ | ✅ DashboardContent -50%, RutinasAccordion -45%, TrainerDashboard -16%. 10+ sub-componentes extraídos |
| 3.2 | **Biblioteca de componentes compartidos** | ⭐⭐⭐ | ✅ BaseButton, BaseCard, BaseInput, BaseSkeleton, EmptyState |
| 3.3 | **Composition API** | ⭐⭐ | ✅ 100% `<script setup>` |
| 3.4 | **Composables** | ⭐⭐ | ✅ useAuth, useToast, useFocusTrap, useUndoable, useDebounce, useConfetti, useApiCache, useKeyboardShortcuts, usePullToRefresh, useSwipe, useRealtimeComments, useWebPush, useUrlFilters |
| 3.5 | **TypeScript** | ⭐⭐ | ⚠️ **Parcial**: `useDebounce.ts` (único archivo migrado). Migración masiva pendiente, decisión del equipo |
| 3.6 | **DataTable con sort/filter** | ⭐ | ⚠️ **Parcial**: `ResponsiveTable.vue` responsive pero sin sort/filter; `Paginador.vue` separado. Sort/filter pendiente |

## 🎨 4. Visualizaciones & data display

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 4.1 | **Dashboard con más gráficos** | ⭐⭐ | ✅ `DashboardWeeklyChart.vue` con Chart.js (PR diario 30 días) + heatmap semanal. vendor-chart.js lazy loaded |
| 4.2 | **Calendario/timeline** | ⭐⭐ | ✅ `HistorialCalendar.vue` (heatmap 7×N) + `DashboardHeatmap.vue` |
| 4.3 | **Progress bars animadas** | ⭐⭐ | ✅ `ProgressBar.vue` |
| 4.4 | **Comparación con la comunidad** | ⭐ | ✅ Sección "🌎 Comparación con la comunidad" en `HistorialComparison.vue`. Backend `/api/comunidad/stats` con percentiles cacheados 5min. Ranking (top 10/25%, mitad, etc) + barra visual |
| 4.5 | **Visualización de superseries** | ⭐ | ✅ Bloques agrupados + badges |
| 4.6 | **Cards de medallas con animación** | ⭐⭐ | ✅ `useConfetti.js` + `LogrosTab.vue` detecta medallas nuevas |
| 4.7 | **Timeline del alumno** para el trainer | ☆ | ✅ `TrainerAlumnoTimeline.vue` con eventos (entrenamientos, medallas, metas, medidas). Backend `TrainerTimelineController` |

## 🎬 5. Animaciones & feedback

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 5.1 | **Page transitions** | ⭐⭐ | ✅ `body { animation: pageFadeIn 0.3s ease-out }` en `app.css` |
| 5.2 | **Skeleton → real content fade** | ⭐⭐ | ✅ `<transition name="skeleton-fade">` en BaseSkeleton |
| 5.3 | **Confeti al desbloquear logro** | ⭐⭐ | ✅ `useConfetti.js` (celebrate/bigCelebration/mini) |
| 5.4 | **Microinteracciones** | ⭐ | ✅ Clase `.ripple` con CSS + active:scale en FABs. Hover suave universal |
| 5.5 | **Progress animada** en upload | ☆ | ✅ XHR.upload.progress en `AdminImportExport.vue` con barras gradiente |

## ♿ 6. Accesibilidad

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 6.1 | **Aria labels** en icon buttons | ⭐⭐ | ✅ En todos los componentes críticos |
| 6.2 | **Focus trap** en modales | ⭐⭐ | ✅ `useFocusTrap` en 6 modales |
| 6.3 | **Skip to content** | ⭐ | ✅ En `app.blade.php` |
| 6.4 | **Contraste WCAG AA** | ⭐ | ✅ `app.css`: text-gray-500/600 ajustados a gray-300/400 en dark. Focus visible 2px indigo |
| 6.5 | **Reducción de movimiento** | ☆ | ✅ `@media (prefers-reduced-motion)` |

## ⚡ 7. Performance

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 7.1 | **Code splitting / lazy loading** | ⭐⭐⭐ | ✅ `defineAsyncComponent` + `manualChunks` (vue/axios/alpine/chart/confetti separados) |
| 7.2 | **Virtual scrolling** | ⭐⭐ | ✅ `VirtualList.vue` en `AdminAuditLogs` |
| 7.3 | **Debounce** | ⭐ | ✅ `useDebounce.js` (300ms en AdminMembresias) |
| 7.4 | **Cache LRU cliente** | ☆ | ✅ `useApiCache.js` con `cachedAxiosGet`, TTL configurable, invalidación por patrón, max 50 entries. Aplicado en `EjerciciosList` y `AdminMembresias` |
| 7.5 | **Image lazy loading + WebP** | ☆ | ❌ N/A — No hay `<img>` (usan SVG inline) |

## 🛠️ 8. Features faltantes

| # | Mejora | ⭐ | Estado |
|---|--------|-----|--------|
| 8.1 | **Comentarios trainer en tiempo real** | ⭐⭐ | ✅ `useRealtimeComments.js` + `TrainerCommentController` + Laravel Echo/Pusher. Bootstrap.js lo registra. **Requiere WebSocket server externo** (Pusher/Soketi) en producción |
| 8.2 | **Drag & drop** reordenar ejercicios | ⭐⭐ | ✅ Dentro Y entre días |
| 8.3 | **Vista comparación pesos** | ⭐⭐ | ✅ Tab Comparación con Chart.js dual axis + comparación comunidad |
| 8.4 | **Filtros AdminMembresias** | ⭐ | ✅ Estado + búsqueda debounced |
| 8.5 | **Print-friendly** | ⭐ | ✅ `@media print` en `app.css`: oculta nav/FAB/modales, fondo blanco, URLs en links, sin page-break |
| 8.6 | **Exportar historial** | ⭐⭐ | ✅ CSV + PDF (`exportarPDF()` con jspdf 4 + autoTable 5, lazy loaded) |
| 8.7 | **Push notifications** | ☆ | ✅ `PushService` con `minish/web-push`, VAPID keys generadas (`webpush:vapid`), `PushSubscriptionController` completo. Listo para enviar |

## 🔧 9. Limpieza de código muerto

| Item | Estado |
|---|--------|
| `composables/useFetch.js` (código muerto) | ✅ ELIMINADO |
| `VirtualList.vue` (no se usaba) | ✅ APLICADO en AdminAuditLogs |
| `confirm()` nativos en DashboardContent | ✅ MIGRADO a `toast.confirm()` |
| `confirm()` en RutinasAccordion.compartirRutina | ✅ MIGRADO a `toast.confirm()` |
| `composables/useAuth.deprecated.js` | ✅ ELIMINADO (2026-08-17) |
| `stores/user.deprecated.js` | ✅ ELIMINADO (2026-08-17) |
| `sw-register.js` | ✅ ELIMINADO (2026-08-17) |

## 🔧 10. Refactor de archivos grandes

| Archivo | Antes | Después | Reducción |
|---|---|---|---|
| DashboardContent.vue | 38.5KB | 19KB | **-50%** |
| RutinasAccordion.vue | 41KB | 22.4KB | **-45%** |
| TrainerDashboard.vue | 33KB | 27.6KB | -16% |

**Sub-componentes extraídos (15+):**
- `dashboard/`: RutinaHeader, Stats, SeriesList, Heatmap, RestTimer, WeeklyChart
- `trainer/`: Metrics, RecentWorkouts, AlumnosStats, AlumnoTimeline
- `rutinas/`: AlumnoView, RutinaAcordeon, Publica, MobileQuickSeriesInput
- `historial/`: Header, Matrix, Calendar, Evolution, RmCalculator, KeyExercises, Comparison, PullRefresh
- `progreso/`: MedidasTab, MetasTab, LogrosTab, MedidaInput, DetalleMedidaModal
- `config/`: UserManagement, TrainerAssignment, CampoForm, Paginador

## 🆕 11. Nuevos componentes (sprint 2026-06-21)

| Componente | Propósito |
|---|---|
| `HistorialPullRefresh.vue` | Indicador de pull-to-refresh en mobile para historial |
| `MobileQuickSeriesInput.vue` | Input rápido de series en mobile (295 líneas) |
| `useDebounce.ts` | Útil para búsquedas/filtros; único .ts del proyecto |
| `usePullToRefresh.js` | Composable para detectar gesto de pull-to-refresh |
| `useSwipe.js` | Detector de gestos swipe (izq/der/arriba/abajo) |
| `useRealtimeComments.js` | Suscripción a eventos realtime de comentarios trainer |

## 🆕 12. Backend additions

| Endpoint | Propósito |
|---|---|
| `GET /api/search?q=` | Búsqueda global (ejercicios, rutinas, alumnos) |
| `GET /api/comunidad/stats?ejercicio=` | Percentiles del peso por ejercicio |
| `GET /api/rutinas/publica/{token}` | Ver rutina compartida sin auth |
| `GET /api/trainer/alumnos/{id}/timeline` | Timeline de eventos del alumno |
| `POST /api/rutinas/compartir` | Genera `share_token` y devuelve `public_url` |
| `POST /api/push/subscription` | Registra suscripción Web Push del usuario |
| `DELETE /api/push/subscription` | Elimina suscripción |
| `GET /api/push/vapid-public-key` | Devuelve la public key (público, sin auth) |
| `GET /api/trainer-comments` | Lista comentarios del usuario (auth) |
| `POST /api/trainer-comments` | Crear comentario trainer→alumno |
| `POST /api/trainer-comments/{id}/read` | Marcar como leído |
| `GET /api/notifications` | Lista notificaciones in-app del usuario |
| `POST /api/notifications/{id}/read` | Marcar como leída |
| `POST /api/notifications/read-all` | Marcar todas como leídas |
| `GET /api/messages/conversations` | Lista de conversaciones |
| `GET /api/messages/with/{user}` | Mensajes con un usuario |
| `POST /api/messages` | Enviar mensaje 1-a-1 |

**Migraciones clave del sprint:**
- `2026_06_07_000000_add_share_token_to_rutinas_table.php` — columna `share_token` string 32 unique nullable
- `2026_06_08_000000_create_push_subscriptions_table.php` — suscripciones Web Push
- `2026_06_08_000001_create_trainer_comments_table.php` — comentarios trainer↔alumno
- `2026_08_16_000000_add_ejercicio_id_to_rutinas_table.php` — FK a `ejercicios`
- `2026_08_16_000001_backfill_ejercicio_id_on_rutinas.php` — backfill idempotente

---

# 📊 Reporte final de calidad

| Categoría | Total | ✅ | ⚠️ | ❌ |
|---|---|---|---|---|
| UX/Interacción | 9 | 9 | 0 | 0 |
| Mobile | 5 | 5 | 0 | 0 |
| Componentización | 6 | 4 | 2 | 0 |
| Visualizaciones | 7 | 7 | 0 | 0 |
| Animaciones | 5 | 5 | 0 | 0 |
| Accesibilidad | 5 | 5 | 0 | 0 |
| Performance | 5 | 4 | 0 | 0 (1 N/A) |
| Features | 8 | 8 | 0 | 0 |
| **Total** | **50** | **47 (94%)** | **2 (4%)** | **0 (0%)** |

> **Diferencia vs el reporte viejo (43/50):** el sprint de 2026-06-21 implementó
> 6 features que figuraban como pendientes (1.9, 2.2, 2.5, 8.1, 8.6, 8.7). Las 2
> que quedan parciales son 3.5 (TS masivo) y 3.6 (sort/filter en DataTable).

---

# 🎯 Top 10 (todos hechos)

1. ✅ Sistema de toasts centralizado
2. ✅ Refactorizar componentes grandes
3. ✅ Bottom navigation en mobile
4. ✅ Drag & drop en CrearRutina (entre días también)
5. ✅ Code splitting
6. ✅ Push notifications reales (Web Push + VAPID)
7. ✅ PDF export de historial
8. ✅ Comentarios trainer realtime (Echo/Pusher)
9. ✅ Pull-to-refresh mobile
10. ✅ Mobile quick input

---

# ❓ Lo único parcial

## ⚠️ Pendiente menor
- **3.5 TypeScript** — solo `useDebounce.ts` migrado. Migración masiva de
  stores/composables a `.ts` es decisión del equipo (costo/beneficio).
- **3.6 DataTable con sort/filter** — `ResponsiveTable.vue` es responsive
  pero sin sort/filter. `Paginador.vue` está separado. Pendiente.

## 🔴 Requieren infra externa (no son bugs)
- **8.1 Comentarios trainer realtime** — código completo y listo, pero
  requiere WebSocket server (Pusher/Soketi) configurado en `BROADCAST_DRIVER`.
  Sin eso, los comentarios funcionan pero sin push en vivo (hay que refrescar).
- **8.7 Push notifications** — código completo y VAPID keys generadas,
  pero requiere HTTPS en producción (los Service Workers solo funcionan en
  contextos seguros).
