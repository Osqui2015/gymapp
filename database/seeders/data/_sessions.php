<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = DB::table('sessions')->whereNotNull('user_id')->count();
echo "Sesiones activas con user_id: " . $count . PHP_EOL;
$rows = DB::table('sessions')->whereNotNull('user_id')->orderBy('last_activity', 'desc')->limit(10)->get(['id', 'user_id', 'ip_address', 'last_activity']);
foreach ($rows as $r) {
    $user = DB::table('users')->where('id', $r->user_id)->first();
    echo sprintf("user_id=%d (%s %s) ip=%s activity=%s\n",
        $r->user_id,
        $user->nick ?? '?',
        $user->name ?? '?',
        $r->ip_address,
        date('Y-m-d H:i:s', $r->last_activity)
    );
}
