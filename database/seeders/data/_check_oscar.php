<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$oscar = User::find(14);
echo "Oscar id=14: nick={$oscar->nick} role={$oscar->role} name={$oscar->name}" . PHP_EOL;
echo "isAdmin: " . ($oscar->hasRole(User::ROLE_ADMINISTRADOR) ? 'yes' : 'no') . PHP_EOL;

// Rutinas del sistema (oficiales, cb=null)
$oficiales = DB::table('rutinas')->whereNull('created_by')->count();
$oscarCreadas = DB::table('rutinas')->where('created_by', 14)->count();
echo "Rutinas oficiales: $oficiales" . PHP_EOL;
echo "Rutinas creadas por Oscar: $oscarCreadas" . PHP_EOL;

// Verificar que la query del index devuelve algo para Oscar
$req = \Illuminate\Http\Request::create('/api/rutinas', 'GET');
$req->setUserResolver(fn () => $oscar);
$ctrl = new \App\Http\Controllers\RutinaController();
$resp = $ctrl->index($req);
$data = json_decode($resp->getContent(), true);
echo "Total rutinas devueltas para Oscar: " . count($data) . PHP_EOL;
$niveles = [];
foreach ($data as $r) {
    $niveles[$r['nivel']] = ($niveles[$r['nivel']] ?? 0) + 1;
}
foreach ($niveles as $n => $c) {
    echo "  $n: $c" . PHP_EOL;
}
