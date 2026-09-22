<?php

/**
 * Reporte de mapeo legacy → VisualGym.
 *
 * Lista los nombres de ejercicios legacy (los del seeder original del proyecto)
 * y todos los nombres del dataset VisualGym para que el usuario pueda hacer
 * el mapeo manualmente.
 *
 * Output:
 *  - SECCIÓN 1: Ejercicios legacy únicos (sin contar VisualGym)
 *  - SECCIÓN 2: Ejercicios VisualGym (todos)
 *
 * Uso:
 *   php database/seeders/data/_mapping_report.php > reporte.txt
 */

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

echo "============================================================\n";
echo "REPORTE DE MAPEO — VisualGym ↔ Legacy\n";
echo "Generado: ".date('Y-m-d H:i:s')."\n";
echo "============================================================\n\n";

// ========== SECCIÓN 1: LEGACY ==========
echo "## SECCIÓN 1: EJERCICIOS LEGACY (del seeder original del proyecto)\n";
echo "## (source != 'visualgym', o sea, los que vos cargaste manualmente)\n\n";

$legacy = Ejercicio::query()
    ->where(function ($q) {
        $q->whereNull('source')
            ->orWhere('source', '!=', 'visualgym');
    })
    ->orderBy('nombre')
    ->get(['id', 'nombre', 'equipamiento', 'grupo_muscular']);

echo "Total legacy: ".count($legacy)."\n\n";
printf("%-6s %-50s %-25s %s\n", 'ID', 'NOMBRE LEGACY', 'EQUIPAMIENTO', 'GRUPO MUSCULAR');
echo str_repeat('-', 120)."\n";
foreach ($legacy as $ej) {
    printf(
        "%-6d %-50s %-25s %s\n",
        $ej->id,
        mb_substr($ej->nombre, 0, 50),
        mb_substr($ej->equipamiento ?? '-', 0, 25),
        $ej->grupo_muscular ?? '-'
    );
}

// ========== SECCIÓN 2: VISUALGYM ==========
echo "\n\n## SECCIÓN 2: VISUALGYM (dataset hasaneyldrm/exercises-dataset)\n";
echo "## (todos los nombres disponibles, agrupados por equipamiento)\n\n";

$visualgym = Ejercicio::query()
    ->where('source', 'visualgym')
    ->orderBy('equipamiento')
    ->orderBy('nombre')
    ->get(['id', 'nombre', 'equipamiento', 'body_part', 'target']);

echo "Total VisualGym: ".count($visualgym)."\n\n";

// Agrupar por equipamiento para que sea fácil de escanear
$byEquip = [];
foreach ($visualgym as $ej) {
    $eq = $ej->equipamiento ?? 'sin equip';
    $byEquip[$eq][] = $ej;
}

foreach ($byEquip as $eq => $list) {
    echo "\n### Equipamiento: $eq (".count($list)." ejercicios)\n";
    printf("%-6s %-60s %-20s %s\n", 'ID', 'NOMBRE VISUALGYM', 'BODY PART', 'TARGET');
    echo str_repeat('-', 120)."\n";
    foreach ($list as $ej) {
        printf(
            "%-6d %-60s %-20s %s\n",
            $ej->id,
            mb_substr($ej->nombre, 0, 60),
            mb_substr($ej->body_part ?? '-', 0, 20),
            mb_substr($ej->target ?? '-', 0, 30)
        );
    }
}

echo "\n\n## NOTAS\n";
echo "- Para mapear un legacy a un VisualGym, el formato del alias es:\n";
echo "    'legacy_normalizado' => 'nombre_exacto_visualgym'\n";
echo "  donde legacy_normalizado es el nombre en lowercase sin acentos (con normalize()).\n";
echo "  Ej: 'press de banca' => 'barbell bench press'\n";
echo "\n";
echo "- Si un legacy NO tiene equivalente en VisualGym, poner string vacío ''\n";
echo "  para que caiga al legacy (sin GIF, solo placeholder).\n";
echo "\n";
echo "- Para agregar/modificar aliases editar:\n";
echo "    app/Http/Controllers/EjercicioController.php → NAME_ALIASES\n";
