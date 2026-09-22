<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ejercicio;

function normalizeNoAccents($s) {
    $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
    $s = preg_replace('/[^a-z0-9\s\/()]/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// Cargar NAME_ALIASES
$src = file_get_contents(__DIR__ . '/../../../app/Http/Controllers/EjercicioController.php');
preg_match('/private const NAME_ALIASES = \[(.*?)\];/s', $src, $m);
$body = preg_replace('/\/\/.*$/m', '', $m[1]);
preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $body, $matches, PREG_SET_ORDER);

$aliases = [];
foreach ($matches as $mm) {
    $aliases[normalizeNoAccents(stripslashes($mm[1]))] = stripslashes($mm[2]);
}

// Tests específicos
$tests = [
    'Dragon Flags' => 'flag',
    'Remo Ergómetro (Rowing Machine)' => null, // No hay VG
    'Press de banca inclinado' => 'barbell incline bench press',
    'Press de banca plano pesado' => 'barbell bench press',
    'Press de banca' => 'barbell bench press',
    'Sentadilla' => 'barbell full squat',
    'Curl de bíceps con barra Z' => 'ez barbell curl',
];

echo "Tests:\n";
foreach ($tests as $input => $expected) {
    $norm = normalizeNoAccents($input);
    $aliasKey = null;
    foreach (array_keys($aliases) as $k) {
        if ($k === $norm || (strlen($k) >= 3 && strpos($norm, $k) !== false)) {
            $aliasKey = $k;
            break;
        }
    }
    $resolved = $aliasKey !== null ? $aliases[$aliasKey] : null;
    $check = is_null($expected) ? ($resolved === null || $resolved === '' ? '✓ legacy' : '?') :
             ($resolved === $expected ? '✓' : "✗ esperado=$expected");

    echo sprintf("%-40s → %-40s [%s]\n", $input, $resolved ?? '(no match)', $check);
}
