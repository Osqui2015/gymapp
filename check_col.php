<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$col = DB::selectOne('SHOW COLUMNS FROM user_rutinas WHERE Field = ?', ['ciclo_inicio']);
echo "Type: " . ($col->Type ?? 'NOT FOUND') . PHP_EOL;

$row = DB::table('user_rutinas')->first();
if ($row) {
    echo "ciclo_inicio raw: " . var_export($row->ciclo_inicio, true) . PHP_EOL;
}
