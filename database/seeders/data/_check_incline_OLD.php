<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Ejercicio;
use Illuminate\Contracts\Console\Kernel;

$queries = ['press de banca inclinado', 'press banca inclinado', 'press de banca', 'press inclinado'];
foreach ($queries as $q) {
    echo "=== '$q' ===\n";
    $rows = Ejercicio::where('source', 'visualgym')
        ->where('nombre', 'like', '%bench%')
        ->orWhere('nombre', 'like', '%press%')
        ->get(['id', 'nombre', 'source']);
    foreach ($rows as $r) {
        if (stripos($r->nombre, 'bench') !== false || stripos($r->nombre, 'press') !== false) {
            echo $r->id.' | '.$r->nombre."\n";
        }
    }
    echo "\n";
}
