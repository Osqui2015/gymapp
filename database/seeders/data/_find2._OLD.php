<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

foreach (['hip thrust', 'military press', 'shoulder press', 'one arm row', 'ez bar', 'reverse grip', 'shrug', 'incline row', 'upright row', 'seated row', 'face pull', 'lateral raise', 'rear delt', 'overhead', 'triceps', 'curl', 'reverse', 'crunch', 'plank', 'leg raise', 'lying leg', 'hanging', 'wheel', 'dragon', 'russian', 'mountain', 'burpee', 'jumping', 'stiff', 'sumo', 'romanian', 'leg press', 'leg curl', 'rear lunge', 'lateral lunge', 'bulgarian', 'split squat', 'single leg', 'stiff-leg'] as $kw) {
    $count = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%'.$kw.'%')->count();
    echo "  '$kw': $count\n";
}

echo "\n=== Muestra hip thrust ===\n";
$e = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%hip thrust%')->take(5)->get(['nombre']);
foreach ($e as $x) echo "  - " . $x->nombre . "\n";

echo "\n=== shoulder press (barbell) ===\n";
$e = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%shoulder press%')->where('equipamiento', 'barbell')->take(5)->get(['nombre']);
foreach ($e as $x) echo "  - " . $x->nombre . "\n";

echo "\n=== one arm row (dumbbell) ===\n";
$e = Ejercicio::where('source', 'visualgym')->where('equipamiento', 'dumbbell')->where('nombre', 'like', '%row%')->take(15)->get(['nombre']);
foreach ($e as $x) echo "  - " . $x->nombre . "\n";

echo "\n=== ez bar curl ===\n";
$e = Ejercicio::where('source', 'visualgym')->where('equipamiento', 'ez barbell')->where('nombre', 'like', '%curl%')->take(10)->get(['nombre']);
foreach ($e as $x) echo "  - " . $x->nombre . "\n";

echo "\n=== triceps dip ===\n";
$e = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%dip%')->take(15)->get(['nombre', 'equipamiento']);
foreach ($e as $x) echo "  - " . $x->nombre . " [{$x->equipamiento}]\n";
