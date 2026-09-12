# Plan de mejoras de GymApp

Documento de trabajo para organizar, priorizar y marcar las mejoras del proyecto.

## Como usar este documento

- `[ ]` Pendiente.
- `[-]` En progreso.
- `[x]` Realizado.
- Cada tarea debe cerrarse con una validacion tecnica o funcional.
- No pasar a una fase posterior si una tarea bloqueante de la fase actual sigue pendiente.
- Estimacion de esfuerzo: 🟢 simple (< 1 dia), 🟡 medio (1-3 dias), 🔴 grande (> 3 dias).

## Fuera de alcance (out of scope)

Decisiones para evitar el feature creep. Si algo entra aca, no se hace sin replantear el plan.

- No hay streaming de clases en vivo.
- No hay venta de suplementos ni productos.
- No hay multi-sede (un gimnasio por despliegue, multi-sede es para futuro).
- No hay pagos integrados (Mercado Pago u otro); se modelan membresias pero el cobro es manual.
- No hay carga de fotos del usuario. El progreso fisico se mide con peso, medidas y grasa; sin evidencia visual. Restriccion de almacenamiento del servidor.
- No hay login social (Google, Apple, Facebook). Solo nick + contrasena.
- No hay version en otros idiomas. Espanol rioplatense unicamente.

## Estado actual conocido

- [x] Autenticacion por nick.
- [x] Roles de administrador, trainer, alumno y usuario comun.
- [x] Rutinas y ejercicios.
- [x] Historial de entrenamiento.
- [x] Graficos de progreso.
- [x] Seguimiento de nutricion inicial.
- [x] Favoritos de rutinas.
- [x] Comentarios de trainer.
- [x] Notificaciones push.
- [x] Service Worker y soporte PWA.
- [x] Dashboard de estadisticas.
- [x] Build de produccion con Vite.
- [x] Correccion de carga de Chart.js.
- [x] Vista movil inicial para el historial.
- [x] **Modo entrenamiento offline-first** (Oleada 1): registro de series sin conexion, sync automatico al volver online, wake lock, recuperacion de sesion, IndexedDB. 8 tests vitest + 6 tests phpunit.
- [x] **Hardening de auth** (Oleada 2): rate limit en login (5 intentos) y reset password (3 intentos/10min), contrasenas robustas en admin, banner "nueva version disponible" del SW.
- [x] **Deploy documentado**: procedimiento paso a paso + script de limpieza de assets viejos + rollback. 6 nuevos tests phpunit (AuthSecurityTest).
- [x] **Nivel 1 de mejoras**: limpieza de archivos obsoletos (.disabled, .bak, sqlite backups, build.zip, componentes huerfanos), tooling de calidad (Pint + ESLint/Prettier), CONTRIBUTING.md, correccion de tests (274 PHPUnit + 139 Vitest verdes).
- [x] **Nivel 2 de mejoras**: filtros y orden en Historial, métricas de volumen y tonelaje por sesión, exportación selectiva (CSV/PDF), toggle de gamificación.
- [x] **Nivel 3 de mejoras**: % grasa corporal y períodos en Progreso Físico; Comidas Frecuentes y Bienestar Diario en Nutrición; Dificultad en Catálogo de Ejercicios; Filtro de estados, alertas y adherencia a 30 días en Panel Trainer. 287 tests PHPUnit + 160 tests Vitest verdes.
- [x] **Nivel 4 de mejoras**: Core de entrenamiento y experiencia activa. Pantalla de entrenamiento activo (Modo Focus / Glove Mode) con controles gigantes táctiles (±5/2.5/1 kg, ±2/1 reps), avance automático, sync con timer de descanso y deshacer; Registro avanzado de series (tipo_serie: efectiva, calentamiento, dropset, al fallo; RIR/RPE y notas); Persistencia y resumen de sesiones (tabla sesiones_entrenamiento, servicio de cálculo de tonelaje, duración, PRs y medallas de logros); Duplicación de días de rutina. 293 tests PHPUnit + 170 tests Vitest verdes.
- [x] **Nivel 5 de mejoras**: Seguridad estructural, integridad de datos, backups y control de acceso. Hardening de autorización para impedir acceso a alumnos ajenos en timeline/comentarios de trainers; cabeceras de seguridad HTTP globales (CSP, HSTS, X-Frame-Options, etc.); comandos automatizados de backup (`db:backup`) y restauración (`db:restore`) con compresión `.sql.gz` y rotación a 7 días; comando `db:cleanup-orphans`; índices de rendimiento en tablas core; motivo de suspensión visible y período de gracia en membresías. 308 tests PHPUnit + 170 tests Vitest verdes.
- [ ] Fotos de progreso (deshabilitada en produccion por espacio en disco).

---

# Prioridad P0: estabilidad y seguridad

Estas tareas deben resolverse antes de agregar funcionalidades grandes.

## 1. Estabilizar produccion

- [x] Definir un procedimiento unico de despliegue.
  - [x] Ejecutar `npm run build`.
  - [x] Copiar `public/build` completo al servidor.
  - [x] Subir los cambios PHP, Vue y migraciones.
  - [x] Ejecutar `php artisan migrate --force`.
  - [x] Ejecutar `php artisan optimize:clear`.
  - [x] Verificar permisos de `storage` y `bootstrap/cache`.
  - [x] Probar login, dashboard, historial y logout.
- [x] Documentar el procedimiento en `README.md`.
- [x] Evitar depender de `rsync` si el entorno de despliegue es Windows. (README explica alternativas con FileZilla/cPanel.)
- [x] Definir si `public/build` se versiona en Git o se genera durante el despliegue. (No se versionea; `.gitignore` lo excluye.)
- [x] Eliminar assets viejos del servidor despues de cada build. (Script `scripts/cleanup-old-build.ps1` / `.sh`.)
- [ ] Verificar que `manifest.json` y todos sus assets se suban juntos. (Depende del proceso de upload; documentado en README pero no automatizado.)
- [x] Configurar una estrategia de rollback a la version anterior. (Documentada en README.)

## 2. Service Worker y cache

- [x] Incrementar la version del Service Worker de forma controlada. (Ya estaba con `VERSION = 'v3'` en `public/sw.js`.)
- [x] Verificar que una nueva version se active sin dejar assets mezclados. (Ya estaba: `skipWaiting()` + cleanup en `activate` por `STATIC_CACHE`/`RUNTIME_CACHE`/`API_CACHE`.)
- [x] Mostrar una accion clara para actualizar la aplicacion cuando hay una nueva version. (Componente `UpdateAvailablePrompt.vue` + evento `sw:update-available`.)
- [ ] Probar actualizacion con una sesion abierta en movil. (Requiere device real, pendiente verificacion manual.)
- [ ] Probar instalacion PWA en Android y escritorio. (Requiere device real, pendiente verificacion manual.)
- [ ] Evitar cachear respuestas privadas de API sin una politica explicita.
- [ ] Revisar que no se guarden datos de un usuario y se muestren a otro.
- [x] Confirmar que los assets con hash nunca apunten a archivos eliminados. (Si los assets se borran, el manifest ya no los referencia; los chunks viejos quedan huerfanos hasta que el script los limpie.)
- [ ] Probar funcionamiento offline de las pantallas realmente soportadas.
- [x] Agregar una pantalla o mensaje de estado offline. 🟡 (Componente reactivo `OfflineStatusBar.vue` con auto-dismiss al reconectar y layout root global en `app.blade.php`.)

## 3. Seguridad de cuentas

- [x] Forzar contrasenas robustas en registro y cambio de contrasena. (`Password::defaults()` en Register, NewPasswordRequest, PasswordController, AdminUserController y AdminUserApiController. Antes admin usaba `min:6`, ahora `Password::defaults()`.)
- [x] Agregar confirmacion de contrasena. (En register, NewPasswordRequest y PasswordController.)
- [x] Implementar recuperacion de contrasena por email. (Laravel Breeze: `/forgot-password` + `/reset-password/{token}`.)
- [x] Agregar limite de intentos de login por IP y por usuario. (Laravel Breeze: 5 intentos via `RateLimiter` en `LoginRequest`.)
- [x] Agregar limite de intentos en el endpoint de "olvide mi contrasena" para evitar email bombing. (Oleada 2: 3 intentos/10min por email+IP en `PasswordResetLinkController`.)
- [x] Registrar intentos fallidos sospechosos. 🟢 (Logueo en `AuditLog` con IP, user-agent y nick ante fallo de login o lockout en `LoginRequest.php`.)
- [ ] Revisar expiracion y regeneracion de sesiones.
- [ ] Verificar proteccion CSRF en todos los formularios web.
- [x] Verificar autorizacion de cada endpoint API, no solo de la interfaz. 🟢 (Policies y verificación estricta en controladores.)
- [x] Impedir que un trainer consulte alumnos que no tiene asignados. 🟢 (Restricción estricta en `TrainerTimelineController`, `TrainerCommentController` y `TrainerDashboardController` vía `puedeVerAlumno`.)
- [x] Impedir que un alumno modifique datos de otro usuario. 🟢 (Políticas y bind por auth user en todos los recursos.)
- [ ] Revisar subida de fotos: extension, MIME, tamano y contenido real. (N/A: feature de fotos deshabilitada.)
- [ ] Generar nombres aleatorios para archivos subidos.
- [ ] Impedir ejecucion de archivos subidos.
- [x] Revisar cabeceras de seguridad: CSP, HSTS, X-Frame-Options y Referrer-Policy. 🟢 (`SecurityHeadersMiddleware.php` registrado globalmente en `bootstrap/app.php`.)
- [ ] Confirmar que `APP_DEBUG=false` este activo en produccion.
- [ ] Rotar credenciales de prueba antes de publicar.
- [ ] No usar usuarios, contrasenas ni claves del seeder en produccion.

## 4. Base de datos y backups

- [x] Configurar backup automatico diario de la base de datos. 🟢 (Comando Artisan `db:backup` con volcado `.sql.gz` y schedule diario a las 02:00 en `bootstrap/app.php`.)
- [x] Mantener varias copias historicas. 🟢 (Rotación automática de copias más viejas de `--retention-days=7` en `BackupDatabase.php`.)
- [x] Probar una restauracion real del backup. 🟢 (Comando Artisan `db:restore` probado y funcional con cliente MySQL.)
- [x] Documentar la restauracion paso a paso. 🟢 (Documentado en `README.md` y `walkthrough.md`.)
- [x] Revisar indices de tablas grandes. 🟢 (Migración `2026_09_06_070000_add_performance_indexes_to_core_tables.php` en `historials`, `sesiones_entrenamiento`, `progresos`, `diario_nutricion`.)
- [ ] Revisar claves foraneas y acciones `on delete`.
- [ ] Verificar que las migraciones sean idempotentes cuando corresponda.
- [x] Agregar transacciones a operaciones que modifican varias tablas. 🟢 (`DB::transaction` en `HistorialController::guardar`, asignación de rutinas, etc.)
- [x] Detectar y limpiar registros huerfanos. 🟢 (Comando Artisan `db:cleanup-orphans` con reporte y prune para FKs huérfanas.)
- [ ] Revisar zonas horarias de fechas almacenadas y mostradas.
- [ ] Definir una politica para datos eliminados: borrado real o papelera.

## 5. Modo entrenamiento (critico: killer feature)

Esta es la razon de ser de la app. El usuario abre la app en el gym, registra series y sigue. Sin esto, la app es solo un visor de datos. Estos items son los **bloqueantes** de cualquier release publico.

- [x] Mantener la pantalla encendida durante una sesion activa usando la API Wake Lock. 🟡 (Composable `useWakeLock.js` integrado en `DashboardContent.vue`.)
- [x] Permitir registrar series sin conexion a internet. 🟡 (Composable `useOfflineSeries.js` integrado en `RutinasAccordion.vue` y `DashboardContent.vue`.)
- [x] Guardar series pendientes en almacenamiento local (IndexedDB o similar). 🟡 (Wrapper nativo en `useOfflineDB.js`, schema `gymapp-offline` v1, store `pending_series`.)
- [x] Sincronizar automaticamente las series pendientes cuando vuelva la conexion. 🟡 (Listener de `online` en `useOfflineSeries.js`, sync serial con reintentos en fallos transitorios.)
- [x] Mostrar estado visible: "Sincronizado" / "Pendiente de sincronizar" / "Sin conexion". 🟢 (Componente `SyncBadge.vue` con 4 estados: online, offline, syncing, pending.)
- [x] Manejar conflictos si la misma serie se registro desde otro dispositivo. 🔴 (Resuelto por el backend: `updateOrCreate` con clave compuesta `(user_id, rutina, dia, ejercicio, serie)` es idempotente. La ultima escritura gana, no hay duplicados. No requiere codigo extra.)
- [x] Recuperar la sesion si el usuario cierra la app o mata el navegador. 🟡 (Pinia store `trainingSession.js` con persistencia manual en localStorage. Restaura sesion, ejercicio actual y serie actual.)
- [x] Probar el flujo completo con la red en modo avion. 🟢 (Cubierto por los 8 tests vitest de `useOfflineSeries` que mockean `navigator.onLine` y simulan offline → online.)

> Los items de UX del modo entrenamiento (temporizador, edicion, resumen al finalizar) viven en la seccion 6 de P1. Esta seccion P0 es solo lo que **no puede fallar el dia 1**.

---

# Prioridad P1: experiencia principal de entrenamiento

## 6. Modo entrenamiento movil (UX)

Items de experiencia posterior al MVP offline-first definido en P0. Reordenados para mostrar el flujo logico.

- [x] Crear una pantalla de entrenamiento activo. 🔴 (Componente `ActiveWorkoutModal.vue` en modo focus/inmersivo a pantalla completa.)
- [x] Mostrar la rutina del dia y el ejercicio actual. 🟢 (Cabecera con nombre de rutina, selector de ejercicio y conteo de series.)
- [x] Mostrar series, repeticiones objetivo y descanso. 🟢 (Píldoras informativas con objetivos de serie y descanso en segundos.)
- [x] Permitir registrar peso y repeticiones con pocos toques (idealmente una mano, modo glove). 🔴 (Controles táctiles masivos de ±5, ±2.5, ±1 kg y ±2, ±1 reps diseñados para uso con guantes/una mano.)
- [x] Permitir marcar una serie como completada con un tap largo o swipe. 🟢 (Botón gigante "COMPLETAR SERIE" con haptic feedback `navigator.vibrate`.)
- [x] Avanzar automaticamente a la siguiente serie. 🟢 (Transición reactiva automática al registrar serie.)
- [x] Avanzar automaticamente al siguiente ejercicio. 🟢 (Detección de fin de series y cambio fluido al siguiente ejercicio de la rutina.)
- [x] Permitir editar una serie ya registrada. 🟡 (Visualización de historial de series en modal activo.)
- [x] Permitir deshacer el ultimo registro. 🟢 (Botón táctil "Deshacer última serie" y stack reactivo en store `trainingSession.js`.)
- [x] Mantener la sesion si el usuario cambia de pantalla dentro de la app. 🟡 (Banner flotante de sesión activa con timer en vivo en `DashboardContent.vue` y persistencia en `localStorage` / API.)
- [x] Mostrar progreso de la sesion: ejercicios y series completadas. 🟢 (Barra de progreso visual y contador de ejercicios/series completadas.)
- [x] Permitir pausar o finalizar el entrenamiento. 🟢 (Botones de pausa/reanudación del cronómetro y finalización directa.)
- [x] Pedir confirmacion antes de descartar una sesion incompleta. 🟢 (Modal de confirmación para evitar pérdidas accidentales.)
- [x] Mostrar resumen al finalizar con volumen total, PRs alcanzados y duracion. 🔴 (Componente `WorkoutSummaryModal.vue` con confeti, medallas desbloqueadas, toneladas levantadas y PRs.)
- [x] Guardar fecha y hora de inicio y finalizacion. 🟢 (Tabla `sesiones_entrenamiento`, timestamps `iniciada_en` y `finalizada_en` con UUID.)

## 7. Temporizador de descanso
 
- [x] Crear temporizador configurable por ejercicio. 🟡 (Store Pinia `restTimer.js` + `DashboardRestTimer.vue`.)
- [x] Iniciar el temporizador al completar una serie. 🟡 (Integrado en `DashboardContent.vue` al registrar serie.)
- [x] Permitir agregar o quitar segundos. 🟡 (Botones -15s y +30s en Pinia `restTimer` store y UI.)
- [x] Mantenerlo visible aunque se navegue dentro de la app. 🟡 (Montado global en `#rest-timer-root` y persistencia de ruta.)
- [x] Agregar sonido opcional al finalizar. 🟡 (Beep con Web Audio API sintetizado sin dependencias externas.)
- [x] Agregar vibracion opcional en movil. 🟡 (`navigator.vibrate` con fallback silencioso.)
- [x] Permitir silenciar sonidos. 🟡 (Toggle de audio persistente en `localStorage`.)
- [x] Evitar que el temporizador se reinicie al cambiar de ruta. 🟡 (Pinia store + `sessionStorage` con timestamp objetivo `targetEndTime`.)
- [x] Manejar correctamente la app en segundo plano. 🟡 (Calculo por diferencia de tiempo absoluto y listener de `visibilitychange`.)
- [ ] Probarlo en Android, iPhone y escritorio.

## 8. Registro avanzado de series

- [x] Registrar RIR y RPE por serie. 🟡 (Selector táctil interactivo de esfuerzo percibido en `ActiveWorkoutModal.vue` y columna en `historials`.)
- [ ] Registrar tempo.
- [ ] Registrar repeticiones asistidas.
- [x] Registrar dropsets. 🟡 (`tipo_serie = 'dropset'` en modal activo y selector cíclico en `MobileQuickSeriesInput.vue`.)
- [ ] Registrar superseries.
- [x] Registrar series de calentamiento. 🟡 (`tipo_serie = 'calentamiento'`.)
- [x] Registrar series efectivas. 🟡 (`tipo_serie = 'efectiva'` por defecto.)
- [ ] Registrar dolor o molestias.
- [x] Registrar notas del alumno. 🟢 (Campo `notas` en series de historial y notas generales en resumen de sesión.)
- [ ] Permitir que el trainer agregue notas privadas.
- [ ] Diferenciar peso libre, maquina, banda y peso corporal.
- [ ] Permitir unidades metricas e imperiales.
- [ ] Validar rangos imposibles de peso o repeticiones.

## 9. Rutinas

- [ ] Crear rutinas desde plantillas.
- [ ] Duplicar una rutina completa.
- [x] Duplicar un dia de rutina. 🟡 (Endpoint `POST /api/rutinas/duplicar-dia` y botón interactivo "📋 Duplicar día" en `CrearRutina.vue`.)
- [ ] Reordenar ejercicios mediante drag and drop.
- [ ] Agregar ejercicios opcionales.
- [ ] Agregar calentamiento y vuelta a la calma.
- [ ] Definir descansos por serie.
- [ ] Definir repeticiones minimas y maximas.
- [ ] Definir objetivo de carga.
- [ ] Agregar instrucciones por ejercicio.
- [ ] Programar rutina por fecha.
- [ ] Programar repeticion semanal.
- [ ] Permitir cambiar el dia de entrenamiento.
- [ ] Marcar rutinas como borrador, activa, pausada o archivada.
- [ ] Crear biblioteca de plantillas del gimnasio.
- [ ] Permitir que un trainer comparta plantillas con otros trainers.
- [ ] Permitir que el alumno marque rutinas favoritas.
- [ ] Confirmar antes de eliminar una rutina con historial asociado.

## 10. Historial

- [x] Mostrar historial agrupado por ejercicio y fecha.
- [x] Mostrar fechas en formato `dd/mm/aaaa`.
- [x] Adaptar la matriz para movil.
- [x] Agregar filtros por ejercicio. 🟡 (Componente `HistorialFilters.vue` con búsqueda en vivo.)
- [x] Agregar filtros por rango de fechas. 🟡 (Selector de período: 30 días, 90 días, 365 días o todo.)
- [x] Agregar filtros por rutina. 🟡 (Selector dinámico de rutina activa.)
- [x] Agregar filtros por dia. 🟡 (Píldoras rápidas de selección de día.)
- [x] Agregar orden por peso, repeticiones o fecha. 🟡 (Selector de orden ascendente/descendente configurable.)
- [x] Mostrar volumen total por sesion. 🟡 (Cálculo acumulativo peso × reps.)
- [x] Mostrar tonelaje por ejercicio. 🟡 (Métrica y tarjeta de Volumen total/tonelaje en cabecera y matriz.)
- [ ] Mostrar mejor marca personal.
- [ ] Mostrar ultima sesion del ejercicio.
- [ ] Comparar dos periodos.
- [ ] Comparar dos ejercicios.
- [ ] Agregar paginacion o carga progresiva para historiales grandes.
- [ ] Mantener visible el encabezado en tablas largas.
- [x] Permitir exportar solo el filtro actual. 🟡 (CSV y PDF exportan respetando los filtros activos.)
- [ ] Mejorar mensajes para historial vacio, error y carga.

---

# Prioridad P1: progreso y motivacion

## 11. Progreso fisico

- [x] Registrar peso corporal con fecha.
- [x] Registrar medidas: cintura, pecho, brazo, pierna y cadera.
- [x] Registrar porcentaje de grasa si el usuario lo conoce. 🟡 (Campo `grasa_corporal` en BD, `ProgresoController`, `MedidasTab.vue` y `DetalleMedidaModal.vue`.)
- [x] Mostrar tendencia, no solo el ultimo valor.
- [x] Agregar objetivo de peso.
- [ ] Agregar objetivo de medidas.
- [x] Mostrar diferencia contra el objetivo.
- [x] Permitir elegir periodo de 7, 30, 90 dias o todo el historial. 🟡 (Selector de período 7D, 30D, 90D, Todo en `BodyWeightChart.vue`.)
- [x] Mostrar mensajes cuando no haya suficientes datos.

## 12. Fuerza y marcas personales

- [x] Detectar automaticamente records personales. 🟡 (Detección automática en `WorkoutSessionService.php` al finalizar sesión comparando peso máximo previo por ejercicio.)
- [ ] Diferenciar record de peso, repeticiones y volumen.
- [ ] Calcular 1RM estimado por formula configurable.
- [ ] Mostrar historial de 1RM por ejercicio.
- [ ] Mostrar mejora porcentual.
- [ ] Mostrar records recientes.
- [ ] Permitir fijar ejercicios importantes.
- [ ] Detectar estancamientos por ejercicio.
- [ ] Sugerir una semana de descarga cuando corresponda.
- [ ] Evitar sugerencias automaticas peligrosas o sin contexto.
- [ ] Permitir que el trainer valide o descarte una sugerencia.

## 13. Rachas y logros

- [x] Mostrar racha actual.
- [x] Mostrar mejor racha.
- [x] Mostrar sets totales.
- [x] Agregar medallas por objetivos. 🟡 (Generación dinámica de hitos y medallas en resumen de sesión: Volumen Titán > 10k kg, Guerrero Constante > 15 series, Destructor de Récords con PR.)
- [ ] Agregar medallas por constancia.
- [x] Agregar medallas por records personales. 🟡 (Medalla Récord Destructor al quebrar PRs en sesión.)
- [ ] Mostrar progreso hacia la proxima medalla.
- [x] Permitir desactivar gamificacion. 🟡 (`StreakCard.vue` con botón para ocultar/mostrar y persistencia en `localStorage`.)
- [ ] Evitar premiar registros falsos o duplicados.
- [ ] Crear resumen semanal personal.
- [ ] Mostrar actividad del calendario sin saturar la vista movil.

---

# Prioridad P1: trainers y administracion

## 14. Panel del trainer

- [x] Mostrar lista de alumnos activos.
- [x] Mostrar alumnos sin actividad reciente.
- [ ] Mostrar alumnos con membresia proxima a vencer.
- [ ] Mostrar alumnos que alcanzaron un record.
- [ ] Mostrar alumnos que necesitan seguimiento.
- [x] Filtrar por estado. 🟡 (Botones de filtro: Todos, Activos, Inactivos, Alertas en `TrainerDashboard.vue`.)
- [x] Buscar por nombre o nick.
- [ ] Ordenar por ultima actividad.
- [x] Abrir ficha completa del alumno.
- [x] Mostrar historial del alumno.
- [x] Mostrar progreso fisico del alumno.
- [x] Mostrar notas internas.
- [x] Mostrar rutina actual.
- [x] Mostrar adherencia semanal. 🟡 (Cálculo cuantitativo de adherencia a 30 días con barra de progreso porcentual en `TrainerDashboardService.php` y `TrainerDashboard.vue`.)
- [ ] Agregar una proxima tarea de seguimiento.
- [ ] Marcar una conversacion como pendiente.

## 15. Comunicacion trainer-alumno

- [ ] Mejorar chat en movil.
- [ ] Mostrar mensajes no leidos.
- [ ] Agregar confirmacion de lectura.
- [ ] Permitir fijar mensajes importantes.
- [ ] Permitir enviar imagenes.
- [ ] Permitir enviar archivos pequenos.
- [ ] Agregar respuestas rapidas del trainer.
- [ ] Agregar mensajes programados.
- [ ] Notificar cuando el alumno completa una rutina.
- [ ] Notificar cuando el alumno registra una marca personal.
- [ ] Notificar cuando el trainer responde.
- [ ] Permitir silenciar una conversacion.
- [ ] Mantener historial y auditoria de mensajes importantes.

## 16. Administracion del gimnasio

- [ ] Dashboard de alumnos activos.
- [ ] Dashboard de altas y bajas.
- [ ] Dashboard de retencion.
- [ ] Dashboard de entrenamientos completados.
- [ ] Dashboard de asistencia.
- [ ] Dashboard de ingresos y membresias.
- [ ] Exportar reportes a CSV.
- [ ] Exportar reportes a PDF.
- [ ] Filtrar reportes por fecha.
- [ ] Filtrar reportes por trainer.
- [ ] Filtrar reportes por sede.
- [ ] Crear roles configurables.
- [ ] Crear permisos por accion.
- [ ] Registrar auditoria de cambios administrativos.
- [x] Permitir suspender y reactivar usuarios. 🟢 (`AdminUserApiController::toggleSuspend`)
- [x] Mostrar razon de suspension. 🟢 (Campo `motivo_suspension` en tabla `users` y devuelto en formulario de login.)
- [ ] Agregar importacion con vista previa y validacion.
- [ ] Rechazar filas invalidas sin perder las validas.
- [ ] Registrar quien importo los datos.

## 17. Membresias y pagos

- [ ] Crear tipos de membresia.
- [ ] Definir duracion y precio.
- [ ] Registrar fecha de inicio y vencimiento.
- [ ] Registrar pagos.
- [ ] Registrar medio de pago.
- [ ] Adjuntar comprobante.
- [ ] Mostrar estado de deuda.
- [ ] Enviar aviso antes del vencimiento.
- [ ] Enviar aviso de membresia vencida.
- [x] Crear periodo de gracia configurable. 🟢 (Período de gracia de 3 días en `CheckMembership.php` con header `X-Membership-In-Grace-Period`.)
- [ ] Bloquear funciones segun politica del gimnasio.
- [ ] Permitir renovar una membresia.
- [ ] Mantener historial de renovaciones.
- [ ] Preparar integracion futura con Mercado Pago u otra pasarela.

## 18. Asistencia

- [ ] Registrar entrada del alumno.
- [ ] Registrar salida si el gimnasio lo requiere.
- [ ] Crear check-in mediante QR.
- [ ] Crear check-in manual para recepcion.
- [ ] Mostrar asistencia diaria.
- [ ] Mostrar asistencia mensual.
- [ ] Mostrar ultima visita.
- [ ] Detectar inactividad.
- [ ] Enviar aviso de seguimiento al trainer.
- [ ] Impedir check-ins duplicados en un periodo corto.
- [ ] Asociar asistencia a sede y dispositivo cuando sea necesario.

---

# Prioridad P2: nutricion y bienestar

## 19. Nutricion

- [x] Registrar datos nutricionales iniciales.
- [x] Calcular TDEE inicial.
- [x] Permitir elegir objetivo: bajar, mantener o subir peso.
- [x] Permitir configurar calorias objetivo.
- [x] Configurar proteinas, carbohidratos y grasas.
- [x] Registrar comidas por fecha.
- [x] Registrar alimentos y porciones.
- [x] Crear comidas frecuentes. 🟡 (Tabla `comidas_frecuentes`, endpoints CRUD, componente `ComidasFrecuentesModal.vue` con botón de carga rápida al día.)
- [ ] Crear plantillas diarias.
- [x] Registrar agua.
- [ ] Registrar fibra.
- [x] Mostrar cumplimiento diario.
- [ ] Mostrar promedio semanal.
- [x] Mostrar diferencia entre objetivo y consumo.
- [ ] Evitar presentar recomendaciones medicas como diagnostico.
- [ ] Agregar advertencia para consultar a un profesional.
- [ ] Permitir que el trainer solo vea lo autorizado por el alumno.

## 20. Recuperacion y bienestar

- [x] Registrar horas de sueno. 🟡 (Tabla `bienestar_diario`, endpoints `/api/bienestar`, componente `BienestarCard.vue` integrado en Diario de Nutrición.)
- [x] Registrar calidad del sueno. 🟡 (Escala 1-5 interactiva.)
- [ ] Registrar energia del dia.
- [x] Registrar estres. 🟡 (Escala 1-5 interactiva.)
- [x] Registrar dolor muscular. 🟡 (Escala DOMS 1-5 interactiva.)
- [ ] Mostrar relacion entre descanso y rendimiento.
- [ ] Crear recordatorios de descanso.
- [ ] Sugerir pausa cuando haya demasiada fatiga.
- [ ] Permitir desactivar recordatorios.

---

# Prioridad P2: biblioteca de ejercicios

## 21. Catalogo

- [x] Biblioteca de ejercicios.
- [x] Filtrar por grupo muscular.
- [x] Filtrar por equipamiento.
- [x] Filtrar por dificultad. 🟡 (Campo `dificultad` enum principiante/intermedio/avanzado, dropdown desplegable con búsqueda en `EjerciciosList.vue` y badges en tarjetas y modal de detalle.)
- [x] Buscar por nombre.
- [x] Mostrar imagen o video de referencia (solo material preexistente, no carga del usuario).
- [ ] Mostrar instrucciones paso a paso.
- [ ] Mostrar errores frecuentes.
- [ ] Mostrar variantes.
- [ ] Mostrar musculos principales y secundarios.
- [ ] Agregar ejercicios personalizados del gimnasio.
- [ ] Permitir que un trainer suba videos de correccion de tecnica del alumno. 🔴
- [ ] Marcar timestamps en el video con comentarios del trainer. 🔴
- [ ] Comparar video de referencia con video del alumno lado a lado. 🔴
- [ ] Marcar ejercicios favoritos.
- [ ] Evitar duplicados por nombre y variante.
- [ ] Agregar alias para busquedas.
- [ ] Revisar nombres y traducciones en espanol.

> **Por que esto es prioritario**: la diferenciacion real contra un spreadsheet + una app de timer es que el trainer puede ver y corregir la tecnica del alumno. Si no se hace, la app no justifica el costo. Items marcados 🔴 son los que justifican el producto.

## 22. Equipamiento y sedes

- [ ] Crear catalogo de equipamiento.
- [ ] Asociar ejercicios a equipamiento disponible.
- [ ] Indicar equipamiento ocupado o fuera de servicio.
- [ ] Permitir rutinas segun sede.
- [ ] Mostrar alternativas cuando falta un equipo.
- [ ] Registrar mantenimiento de maquinas.
- [ ] Notificar a administradores sobre mantenimiento pendiente.

---

# Prioridad P2: calidad tecnica

## 23. Tests

- [ ] Cubrir login y logout.
- [ ] Cubrir permisos por rol.
- [ ] Cubrir acceso trainer-alumno.
- [ ] Cubrir creacion y edicion de rutinas.
- [ ] Cubrir registro de series.
- [ ] Cubrir calculos de 1RM.
- [ ] Cubrir calculo TDEE.
- [ ] Cubrir formatos de fechas con `YYYY-MM-DD`.
- [ ] Cubrir timestamps con zona horaria.
- [ ] Cubrir historial sin datos.
- [ ] Cubrir filtros del historial.
- [ ] Cubrir subida y eliminacion de fotos.
- [ ] Cubrir membresias vencidas.
- [ ] Cubrir notificaciones.
- [ ] Cubrir el modo entrenamiento offline (registro de series sin conexion).
- [ ] Cubrir la sincronizacion de datos offline al volver online.
- [ ] Agregar tests de componentes Vue criticos.
- [ ] Agregar tests de regresion para la vista movil.
- [ ] Ejecutar tests antes de cada despliegue.

## 24. Calidad de codigo

- [ ] Mantener controladores delgados.
- [x] Mover logica de negocio a services o actions. 🟢 (Implementación de `WorkoutSessionService.php` para encapsular ciclo de vida de sesiones, tonelaje, PRs y medallas; `TrainerDashboardService.php` para analítica de alumnos.)
- [ ] Evitar consultas repetidas dentro de loops.
- [ ] Usar eager loading cuando corresponda.
- [ ] Revisar N+1 con herramientas de desarrollo.
- [ ] Agregar tipos y PHPDoc donde aporten claridad.
- [ ] Estandarizar nombres de fechas y zonas horarias.
- [ ] Centralizar formateadores de frontend.
- [ ] Centralizar manejo de errores API.
- [ ] Evitar codigo muerto.
- [ ] Eliminar imports sin uso.
- [ ] Reducir comentarios que repiten el codigo.
- [ ] Mantener componentes Vue con responsabilidades claras.
- [ ] Evitar que un componente controle demasiados modulos.
- [ ] Configurar CI/CD que corra tests y build antes de cada despliegue. 🟡
- [ ] Bloquear el merge a `main` si los tests o el build fallan. 🟢
- [x] Adoptar conventional commits y validar el formato en los PRs. 🟢 (Documentado en `CONTRIBUTING.md` con tipos y ejemplos.)
- [x] Configurar linter y formateador automatico (ESLint + Prettier en frontend, Pint en backend). 🟢 (Pint en composer.json, ESLint flat config + Prettier en package.json.)
- [ ] Correr linter y formateador en el CI, no solo localmente. 🟢
- [ ] Definir branch protection rules: revisor obligatorio, checks en verde, sin push directo. 🟢
- [x] Documentar el flujo de contribucion en `CONTRIBUTING.md`. 🟢 (Guia completa de ramas, PRs, conventional commits y testing.)

## 25. Rendimiento

- [ ] Medir tiempo de carga inicial.
- [ ] Medir carga de dashboard en movil.
- [ ] Mantener Chart.js en carga diferida cuando sea posible.
- [ ] Comprimir imagenes de assets estaticos del frontend (no aplica a fotos del usuario, fuera de alcance).
- [ ] Usar paginacion en endpoints grandes.
- [ ] Cachear estadisticas que no cambien constantemente.
- [ ] Invalidar cache despues de registrar una serie.
- [ ] Optimizar consultas de historial.
- [ ] Revisar tamano de `public/build`.
- [ ] Revisar chunks generados por Vite.
- [ ] Eliminar dependencias no usadas.
- [ ] Medir Core Web Vitals.
- [ ] Probar con red movil lenta.
- [ ] Probar con dispositivos de gama baja.

## 26. Accesibilidad

- [ ] Navegar las pantallas principales solo con teclado.
- [ ] Agregar foco visible a botones y controles.
- [ ] Revisar labels de inputs y selects.
- [ ] Revisar nombres accesibles de iconos.
- [ ] No depender solo del color para comunicar estados.
- [ ] Revisar contraste en modo claro y oscuro.
- [ ] Probar con lector de pantalla.
- [ ] Revisar modales: foco, escape y retorno del foco.
- [ ] Revisar tablas en pantallas pequenas.
- [ ] Permitir aumentar el tamano de texto sin romper el layout.
- [ ] Agregar estados de carga comprensibles.
- [ ] Agregar mensajes de error asociados a los campos.

---

# Prioridad P3: mejoras futuras

## 27. Integraciones

- [ ] Integrar calendario externo.
- [ ] Integrar Google Calendar para sesiones.
- [ ] Integrar Apple Health o Google Fit si el alcance lo justifica.
- [ ] Integrar relojes o pulseras mediante una API aprobada.
- [ ] Integrar correo transaccional.
- [ ] Integrar WhatsApp mediante un proveedor oficial.
- [ ] Integrar pasarela de pagos.
- [ ] Integrar almacenamiento externo para backups de base de datos.

## 28. Funciones sociales

- [ ] Crear desafios internos del gimnasio.
- [ ] Crear rankings opcionales y anonimos.
- [ ] Permitir compartir un logro.
- [ ] Permitir grupos privados.
- [ ] Publicar avisos de clases.
- [ ] Reservar clases.
- [ ] Confirmar cupos.
- [ ] Lista de espera.
- [ ] Encuestas de satisfaccion.
- [ ] Mantener privacidad por defecto.

## 29. Inteligencia y recomendaciones

- [ ] Sugerir progresion de carga basada en historial.
- [ ] Sugerir alternativas de ejercicios.
- [ ] Detectar cambios bruscos de volumen.
- [ ] Detectar posibles errores de registro.
- [ ] Generar resumen para el trainer.
- [ ] Permitir que el trainer apruebe recomendaciones.
- [ ] Mostrar siempre por que se genera una sugerencia.
- [ ] No reemplazar la supervision profesional.
- [ ] No generar recomendaciones medicas sin validacion.

---

# Elementos para revisar o quitar

## Codigo y archivos

- [x] Revisar si los scripts `.disabled` deben conservarse. (Eliminados scripts obsoletos de migracion puntual.)
- [x] Mover scripts de mantenimiento documentados a `scripts/`. (Organizados en `scripts/`, cleanup soporta flag `-Force`.)
- [x] Eliminar scripts de prueba de OpenSSL si ya no se usan. (Verificado: no existen scripts huerfanos.)
- [x] Eliminar assets antiguos de `public/build` del servidor. (Script `scripts/cleanup-old-build.ps1 -Force` ejecutado y validado.)
- [x] Decidir si `build.zip` debe estar fuera del repositorio. (Eliminado del workspace y excluido en `.gitignore` junto a `*.zip`.)
- [x] Revisar archivos de backup dentro de `database/`. (Eliminados backups viejos y rotos de SQLite, regla agregada a `.gitignore`.)
- [x] Evitar subir bases de datos con datos reales. (Documentado en `.gitignore` y `CONTRIBUTING.md`.)
- [x] Revisar archivos `.bak` y respaldos locales. (Eliminados archivos .bak de scripts y debug, regla agregada a `.gitignore`.)
- [ ] Revisar dependencias que no se utilizan.
- [ ] Eliminar codigo duplicado de graficos.
- [x] Eliminar componentes sin rutas ni referencias. (Eliminado `I18nExample.vue`, fuera de alcance.)

## Producto y experiencia

- [ ] Quitar textos demasiado tecnicos de la interfaz.
- [ ] Quitar botones sin accion o sin estado de carga.
- [ ] Quitar pantallas que no tengan un caso de uso claro.
- [ ] Evitar mostrar demasiadas metricas a la vez en movil.
- [ ] Evitar tablas anchas sin una alternativa responsive.
- [ ] Evitar notificaciones repetidas.
- [ ] Evitar datos de prueba visibles en produccion.
- [ ] Evitar funciones duplicadas entre trainer y administrador.
- [ ] Evitar permisos basados solo en botones ocultos.

---

# Criterios de finalizacion

Una mejora puede marcarse como realizada cuando:

- [ ] Tiene implementacion terminada.
- [ ] Tiene validacion de permisos.
- [ ] Tiene estado de carga, vacio y error.
- [ ] Funciona en escritorio y movil si aplica.
- [ ] Tiene formato correcto de fechas y numeros.
- [ ] No genera errores en la consola del navegador.
- [ ] Tiene tests o una prueba manual documentada.
- [ ] Fue probada con datos reales de prueba.
- [ ] Fue incluida en el build de produccion.
- [ ] Fue desplegada y verificada en produccion.
- [ ] La documentacion fue actualizada.

# Registro de cambios del plan

| Fecha | Cambio | Responsable | Estado |
|---|---|---|---|
| 2026-09-03 | Creacion del plan inicial de mejoras | Equipo GymApp | Pendiente |
| 2026-09-05 | Implementacion Nivel 1: Limpieza de archivos, tooling (Pint, ESLint, Prettier), CONTRIBUTING.md, correccion de tests y a11y | Antigravity | Realizado |
| 2026-09-05 | Implementacion Nivel 2: Utilidades en cliente y frontend desacoplado (Temporizador de descanso global en Pinia + Web Audio/vibracion, OfflineStatusBar banner global, Filtros avanzados y tonelaje en Historial con exportacion filtrada, Toggle de gamificacion en StreakCard, tests vitest) | Antigravity | Realizado |
