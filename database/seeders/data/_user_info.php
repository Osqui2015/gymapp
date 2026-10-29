<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$u = User::find(14);
echo "email: {$u->email} nick: {$u->nick} role: {$u->role} suspended: ".($u->suspended ?? 'no').PHP_EOL;
echo 'hasRole admin: '.($u->hasRole('administrador') ? 'yes' : 'no').PHP_EOL;
echo 'hasRole comun: '.($u->hasRole('comun') ? 'yes' : 'no').PHP_EOL;
echo 'membresia: '.($u->membresia_activa ?? '?').PHP_EOL;
echo 'password hash: '.substr($u->password, 0, 20).'...'.PHP_EOL;
