<?php

require __DIR__.'/../../../vendor/autoload.php';

$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

$total = Ejercicio::count();
$visualgym = Ejercicio::where('source', 'visualgym')->count();
$legacy = $total - $visualgym;

echo "Total ejercicios en DB: $total\n";
echo "  VisualGym: $visualgym\n";
echo "  Legacy (seeders manuales): $legacy\n";
echo "---\n";

echo "Sample (external_id=0025 - barbell bench press):\n";
$e = Ejercicio::where('external_id', '0025')->first();
if (! $e) {
    echo "  No encontrado!\n";
    exit(1);
}
echo "  nombre:           {$e->nombre}\n";
echo "  body_part:        {$e->body_part}\n";
echo "  equipamiento:     {$e->equipamiento}\n";
echo "  target:           {$e->target}\n";
echo "  grupo_muscular:   {$e->grupo_muscular}\n";
echo "  secondary_muscles: " . json_encode($e->secondary_muscles) . "\n";
echo "  image_url:        {$e->image_url}\n";
echo "  gif_url:          {$e->gif_url}\n";
echo "  instruction (es): " . substr($e->getInstruction('es'), 0, 100) . "...\n";
echo "---\n";

echo "Top 5 body_parts:\n";
foreach (Ejercicio::where('source', 'visualgym')
    ->selectRaw('body_part, count(*) as c')
    ->groupBy('body_part')
    ->orderByDesc('c')
    ->limit(5)
    ->get() as $r) {
    echo "  {$r->body_part}: {$r->c}\n";
}
echo "---\n";

echo "Sample de instruction_steps (es) para 0025:\n";
$steps = $e->instruction_steps['es'] ?? [];
foreach (array_slice($steps, 0, 3) as $i => $s) {
    echo "  " . ($i + 1) . ". $s\n";
}
