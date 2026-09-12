<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANTES del UPDATE ===" . PHP_EOL;
$fila = DB::table('user_rutinas')
    ->where('user_id', 14)
    ->first();
print_r($fila);
