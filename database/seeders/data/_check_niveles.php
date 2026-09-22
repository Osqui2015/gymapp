<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Niveles distintos en BD ===\n";
$rows = DB::table('rutinas')->distinct()->pluck('nivel');
foreach ($rows as $n) {
    echo "  '$n' (len=" . strlen($n) . ", hex=" . bin2hex($n) . ")\n";
}

echo "\n=== RutinaController::index respuesta simulada (Oscar id=1) ===\n";
$req = \Illuminate\Http\Request::create('/api/rutinas', 'GET');
$req->setUserResolver(fn () => \App\Models\User::find(1));
$ctrl = new \App\Http\Controllers\RutinaController();
$resp = $ctrl->index($req);
$data = json_decode($resp->getContent(), true);
echo "Total rutinas devueltas: " . count($data) . "\n";
$niveles = [];
foreach ($data as $r) {
    $niveles[$r['nivel']] = ($niveles[$r['nivel']] ?? 0) + 1;
}
foreach ($niveles as $n => $c) {
    echo "  $n: $c\n";
}

echo "\n=== Primeras 3 rutinas (sample) ===\n";
for ($i = 0; $i < min(3, count($data)); $i++) {
    $r = $data[$i];
    echo sprintf("id=%d nivel='%s' modalidad='%s' dia='%s' cb=%s\n",
        $r['id'], $r['nivel'], $r['modalidad'], $r['dia'], $r['created_by'] ?? 'NULL');
}
