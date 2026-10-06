# Deploy checklist — Producción GymApp

**Fecha:** 2026-10-02
**Cambios:** Notas de prescripción RIR/RPE/FALLO + UI nueva

---

## 1. Backup obligatorio

Antes de tocar nada, hacé un dump fresco de la DB de producción:

```bash
mysqldump -u USUARIO -p NOMBRE_DB > backup_pre_deploy_$(date +%Y%m%d).sql
```

Verificá que el archivo `.sql` se generó bien y tiene todas las tablas.

---

## 2. Subir código

Subí al servidor:

- ✅ Todos los `.vue` y `.js` modificados
- ✅ El archivo `produccion_notas_rutinas.sql` (generado en este paso)
- ✅ Las migraciones Laravel nuevas:
  - `database/migrations/2026_10_01_220000_extend_esfuerzo_tipo_with_fallo.php`

**Sobre la migración**: en producción la columna `historials.esfuerzo_tipo` ya está en **VARCHAR(8)** (verificado en tu backup). Por lo tanto la migración es **no-op** — no cambia nada, no rompe nada.

---

## 3. Correr migraciones Laravel

```bash
php artisan migrate
```

**Esperado:** Una sola línea tipo `... 2026_10_01_220000_extend_esfuerzo_tipo_with_fallo ... DONE`

Si querés confirmar qué hizo:

```bash
php artisan migrate:status
```

> **Nota MariaDB**: si tu producción es MariaDB (parece que sí por el dump), no se enforce el CHECK constraint por default — pero la validación de Laravel (en el controller) sí bloquea cualquier valor fuera de `'rir' | 'rpe' | 'fallo'`. Doble red de seguridad, ningún problema.

---

## 4. Aplicar las notas de las rutinas

Correr el archivo `produccion_notas_rutinas.sql` contra la DB de producción:

```bash
mysql -u USUARIO -p NOMBRE_DB < produccion_notas_rutinas.sql
```

O desde el cliente (phpMyAdmin, DBeaver, etc.): pegar el contenido del archivo y ejecutar.

**Lo que hace:**
- 22 UPDATE condicionales (uno por ejercicio)
- Solo actualiza donde `notas IS NULL` (no pisa notas custom)
- Envuelto en `START TRANSACTION / COMMIT` — si algo falla, rollback total
- Cero DROP / TRUNCATE / DELETE — tu histórico está 100% intacto

---

## 5. Verificación post-deploy

Correr estas queries de lectura para confirmar:

```sql
-- 1. Las notas nuevas están en producción
SELECT COUNT(*) FROM rutinas
WHERE created_by = 14
  AND nivel = 'Personalizada'
  AND notas IS NOT NULL;
-- Esperado: 22

-- 2. Tu histórico sigue completo
SELECT user_id, COUNT(*) AS sets, COUNT(esfuerzo_tipo) AS con_esfuerzo
FROM historials
WHERE user_id = 14
GROUP BY user_id;
-- Esperado: misma cantidad que tenías antes

-- 3. Ver el mapeo de notas por día
SELECT id, dia, orden, ejercicio_nombre, notas
FROM rutinas
WHERE created_by = 14 AND nivel = 'Personalizada'
ORDER BY dia, orden;
```

---

## 6. Build del frontend

```bash
npm install     # solo si hay dependencias nuevas
npm run build
php artisan optimize:clear
```

---

## 7. Probar el flujo

1. Login con tu user (osqui / nick='oscar')
2. Ir a `/rutinas` → ver que aparecen los pills `2×6 RIR 1`, `2×4 RIR 0`, etc. en cada día
3. Iniciar la sesión → los pills también aparecen sobre el nombre del ejercicio en el workout
4. En la serie que tiene target `RIR 1`, el botón **1** del selector debe prenderse solo con el + automática
5. Si tocás el botón **⚠ FALLO**, podés marcar la serie como al fallo absoluto (en series con target FALLO de las rutinas 250/251)

---

## Rollback

Si algo sale mal:

```bash
# 1. Revertir migración Laravel
php artisan migrate:rollback --step=1

# 2. Limpiar notas (si querés volver al estado anterior)
mysql -u USUARIO -p NOMBRE_DB -e "UPDATE rutinas SET notas = NULL WHERE created_by = 14 AND nivel = 'Personalizada';"

# 3. Restaurar desde backup si hace falta
mysql -u USUARIO -p NOMBRE_DB < backup_pre_deploy_*.sql
```

---

## Resumen de archivos a subir

| Archivo | Acción |
|---|---|
| `produccion_notas_rutinas.sql` | Correr contra la DB |
| `database/migrations/2026_10_01_220000_extend_esfuerzo_tipo_with_fallo.php` | `php artisan migrate` lo aplica solo |
| `resources/js/components/**` | Build con `npm run build` |
| `app/Http/Controllers/**` | Sin acción manual (ya están en el deploy) |
| `resources/js/stores/trainingSession.js` | Sin acción manual (ya está en el deploy) |