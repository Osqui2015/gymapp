<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ejercicio;

$targets = [
    'barbell bench press',
    'barbell incline bench press',
    'barbell decline bench press',
    'dumbbell incline bench press',
    'dumbbell decline bench press',
    'push-up',
    'chest dip',
    'barbell full squat',
    'barbell single leg split squat',
    'kettlebell goblet squat',
    'barbell front squat',
    'sled 45° leg press (side pov)',
    'lever leg extension',
    'barbell deadlift',
    'barbell romanian deadlift',
    'barbell stiff-leg deadlift',
    'barbell sumo deadlift',
    'barbell lunge',
    'walking lunge',
    'lever lying leg curl',
    'pull-up',
    'weighted pull-up',
    'cable pulldown',
    'barbell incline row',
    'barbell pendlay row',
    'barbell row',
    'dumbbell one arm bent-over row',
    'cable seated row',
    't-bar row with handle',
    'dumbbell deadlift',
    'barbell standing wide military press',
    'barbell standing close grip military press',
    'dumbbell seated shoulder press',
    'dumbbell lateral raise',
    'dumbbell rear delt row',
    'dumbbell rear delt row_shoulder',
    'cable face pull',
    'barbell shrug',
    'barbell upright row',
    'dumbbell biceps curl',
    'dumbbell alternate biceps curl',
    'ez barbell curl',
    'dumbbell incline biceps curl',
    'barbell curl',
    'ez barbell close grip preacher curl',
    'dumbbell hammer curl',
    'ez barbell spider curl',
    'cable tricep pushdown',
    'cable overhead tricep extension',
    'lying triceps extension',
    'decline lying triceps extension',
    'crunch',
    'bicycle crunch',
    'leg raise',
    'lying leg raise',
    'hanging leg raise',
    'hanging knee raise',
    'plank',
    'side plank',
    'wheel roller rollout',
    'dragon flag',
    'russian twist',
    'mountain climber',
    'dead bug',
    'burpee',
    'jumping jack',
];

$missing = [];
foreach ($targets as $t) {
    $count = Ejercicio::where('source', 'visualgym')->where('nombre', $t)->count();
    if ($count === 0) {
        $missing[] = $t;
    }
}

if (empty($missing)) {
    echo "✓ Todos los aliases apuntan a VisualGym existentes\n";
} else {
    echo "✗ Faltan en VisualGym:\n";
    foreach ($missing as $m) echo "  - '$m'\n";
}
