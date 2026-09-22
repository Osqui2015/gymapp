<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

$keywords = [
    'stiff-leg deadlift',
    'barbell row',
    't-bar row',
    'rear delt row',
    'face pull',
    'tricep pushdown',
    'tricep extension',
    'lying triceps',
    'decline lying triceps',
    'crunch',
    'bicycle',
    'leg raise',
    'lying leg',
    'hanging knee',
    'plank',
    'side plank',
    'wheel roller',
    'dragon',
    'jumping jack',
];

foreach ($keywords as $kw) {
    echo "\n=== '$kw' ===\n";
    $ej = Ejercicio::where('source', 'visualgym')->where('nombre', 'like', '%'.$kw.'%')->take(5)->get(['nombre', 'equipamiento']);
    foreach ($ej as $x) echo "  - " . $x->nombre . " [{$x->equipamiento}]\n";
}
