<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ejercicio;

$rows = Ejercicio::where('source', 'visualgym')
    ->where(function ($q) {
        $q->where('nombre', 'like', '%incline%bench%')
          ->orWhere('nombre', 'like', '%bench%incline%')
          ->orWhere('nombre', 'like', '%incline%press%');
    })
    ->get(['id', 'nombre']);
foreach ($rows as $r) {
    echo $r->id . " | " . $r->nombre . "\n";
}

echo "\n=== Legacy 'inclinado' (no visualgym) ===\n";
$legacy = Ejercicio::whereNull('source')
    ->where(function ($q) {
        $q->where('nombre', 'like', '%inclinado%')
          ->orWhere('nombre', 'like', '%inclinada%');
    })
    ->get(['id', 'nombre']);
foreach ($legacy as $r) {
    echo $r->id . " | " . $r->nombre . "\n";
}
