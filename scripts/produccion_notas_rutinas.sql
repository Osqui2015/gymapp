-- ============================================================
-- Migración notas de prescripción RIR/RPE/FALLO a producción
-- Fecha: 2026-10-02
-- Origen: capturas del workout del usuario (Día 1 Torso / Día 2 Pierna / Día 3 Full Body)
-- IDs target: 239-260 (rutinas del user_id=14 / Personalizada 3 Días)
-- ============================================================
-- SEGURO: solo UPDATE condicional por id.
-- Solo actualiza rutinas donde notas IS NULL (no pisa notas custom).
-- Para re-correr, basta con setear notas=NULL antes y volver a aplicar.

SET NAMES utf8mb4;
START TRANSACTION;

-- id=239 (orden=1) | Día 1 (Torso) | Press de banca
UPDATE `rutinas` SET `notas` = '2x6 RIR 1 + 2x4 RIR 0' WHERE `id` = 239 AND `notas` IS NULL;

-- id=240 (orden=2) | Día 1 (Torso) | Press inclinado
UPDATE `rutinas` SET `notas` = '3x8 RIR 1 + rest-pause final (8 reps → 5" fallo → 10" fallo)' WHERE `id` = 240 AND `notas` IS NULL;

-- id=241 (orden=3) | Día 1 (Torso) | Press militar de pie
UPDATE `rutinas` SET `notas` = '4x8 RIR 1' WHERE `id` = 241 AND `notas` IS NULL;

-- id=242 (orden=4) | Día 1 (Torso) | Remo sentado
UPDATE `rutinas` SET `notas` = '3x8 RIR 0 + rest-pause intra-set (5" fallo + 10" fallo)' WHERE `id` = 242 AND `notas` IS NULL;

-- id=243 (orden=5) | Día 1 (Torso) | Dominadas
UPDATE `rutinas` SET `notas` = '1x40 (Pull-up australiana, completar todas las reps)' WHERE `id` = 243 AND `notas` IS NULL;

-- id=244 (orden=6) | Día 1 (Torso) | Curl de bíceps
UPDATE `rutinas` SET `notas` = '2x8 RIR 1 + 2x6 RIR 0' WHERE `id` = 244 AND `notas` IS NULL;

-- id=245 (orden=7) | Día 1 (Torso) | Extensión de tríceps con barra recta
UPDATE `rutinas` SET `notas` = '3x10 RIR 0 + rest-pause intra-set (10" descanso + 5 reps por serie)' WHERE `id` = 245 AND `notas` IS NULL;

-- id=246 (orden=1) | Día 2 (Pierna) | Sentadilla
UPDATE `rutinas` SET `notas` = '2x6 RIR 1 + 2x4 RIR 0' WHERE `id` = 246 AND `notas` IS NULL;

-- id=247 (orden=2) | Día 2 (Pierna) | Zancadas con mancuernas
UPDATE `rutinas` SET `notas` = '3x8 (estocadas/zancadas, sin RIR objetivo prescrito)' WHERE `id` = 247 AND `notas` IS NULL;

-- id=248 (orden=3) | Día 2 (Pierna) | Buenos días (Good Mornings)
UPDATE `rutinas` SET `notas` = '4x8 RIR 1' WHERE `id` = 248 AND `notas` IS NULL;

-- id=249 (orden=4) | Día 2 (Pierna) | Hip thrust
UPDATE `rutinas` SET `notas` = '2x8 RIR 1 + 2x6 RIR 0' WHERE `id` = 249 AND `notas` IS NULL;

-- id=250 (orden=5) | Día 2 (Pierna) | Extensión de cuádriceps
UPDATE `rutinas` SET `notas` = '2x8 RIR 1 + 2x8 FALLO' WHERE `id` = 250 AND `notas` IS NULL;

-- id=251 (orden=6) | Día 2 (Pierna) | Curl de isquiotibiales acostado
UPDATE `rutinas` SET `notas` = '2x8 RIR 0 + 2x8 FALLO' WHERE `id` = 251 AND `notas` IS NULL;

-- id=252 (orden=7) | Día 2 (Pierna) | Elevación de talones de pie
UPDATE `rutinas` SET `notas` = '4x15 (gemelos parado, sin RIR objetivo prescrito)' WHERE `id` = 252 AND `notas` IS NULL;

-- id=253 (orden=1) | Día 3 (Full Body) | Press de banca
UPDATE `rutinas` SET `notas` = '2x12 RIR 2 + 2x10 RIR 1 (Superserie con aperturas en polea alta)' WHERE `id` = 253 AND `notas` IS NULL;

-- id=254 (orden=2) | Día 3 (Full Body) | Aperturas / Cruces en polea alta
UPDATE `rutinas` SET `notas` = '2x12 RIR 2 + 2x10 RIR 1 (Superserie con press banca)' WHERE `id` = 254 AND `notas` IS NULL;

-- id=255 (orden=3) | Día 3 (Full Body) | Elevaciones laterales con polea
UPDATE `rutinas` SET `notas` = '2x12 RIR 2 + 2x10 RIR 1' WHERE `id` = 255 AND `notas` IS NULL;

-- id=256 (orden=4) | Día 3 (Full Body) | Jalón al pecho
UPDATE `rutinas` SET `notas` = '2x15 RIR 2 + 2x12 RIR 1' WHERE `id` = 256 AND `notas` IS NULL;

-- id=257 (orden=5) | Día 3 (Full Body) | Curl de bíceps
UPDATE `rutinas` SET `notas` = '2x10 RIR 2 + 2x8 RIR 1 (Superserie con tríceps polea alta)' WHERE `id` = 257 AND `notas` IS NULL;

-- id=258 (orden=6) | Día 3 (Full Body) | Extensión de tríceps sobre la cabeza
UPDATE `rutinas` SET `notas` = '2x10 RIR 2 + 2x8 RIR 1 (Superserie con bíceps polea)' WHERE `id` = 258 AND `notas` IS NULL;

-- id=259 (orden=7) | Día 3 (Full Body) | Prensa de piernas 45 grados
UPDATE `rutinas` SET `notas` = '2x10 RIR 2 + 2x8 RIR 1' WHERE `id` = 259 AND `notas` IS NULL;

-- id=260 (orden=8) | Día 3 (Full Body) | Extensión de cuádriceps
UPDATE `rutinas` SET `notas` = '3x10 RIR 1' WHERE `id` = 260 AND `notas` IS NULL;

COMMIT;

-- Verificación post-update (no destructivo, solo lectura):
-- SELECT id, dia, ejercicio_nombre, notas FROM rutinas WHERE created_by = 14 AND nivel = 'Personalizada' ORDER BY id;