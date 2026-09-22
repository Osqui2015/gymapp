<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

$keywords = [
    'bulgarian',
    'bench press',
    'shoulder press',
    'military press',
    'hip thrust',
    'one arm row',
    'ez bar',
    '45°',
    'leg press',
    'lunge',
    'split squat',
];

foreach ($keywords as $kw) {
    echo "\n=== '$kw' ===\n";
    $ej = Ejercicio::where('source', 'visualgym')
        ->where('nombre', 'like', '%'.$kw.'%')
        ->take(8)
        ->get(['nombre', 'equipamiento']);
    foreach ($ej as $e) {
        echo "  - " . $e->nombre . " [{$e->equipamiento}]\n";
    }
}
