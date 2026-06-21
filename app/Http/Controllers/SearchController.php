<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\User;
use App\Models\Rutina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    /**
     * Búsqueda global: devuelve resultados categorizados (ejercicios, alumnos, rutinas).
     * Para alumnos solo si el usuario logueado es trainer/admin.
     * Para rutinas propias (o públicas si sos alumno).
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        if (mb_strlen($q) < 2) {
            return response()->json([
                'ejercicios' => [],
                'alumnos' => [],
                'rutinas' => [],
                'q' => $q,
            ]);
        }

        $limit = 8;

        // === Ejercicios: siempre ===
        $ejercicios = Ejercicio::query()
            ->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('grupo_muscular', 'like', "%{$q}%")
                    ->orWhere('equipamiento', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'nombre', 'grupo_muscular', 'equipamiento']);

        // === Rutinas: según rol ===
        $rutinasQuery = Rutina::query()
            ->where(function ($query) use ($q) {
                $query->where('nivel', 'like', "%{$q}%")
                    ->orWhere('modalidad', 'like', "%{$q}%");
            });

        // Alumnos solo ven las públicas; trainers/admins ven todas las del sistema
        if ($user->hasRole('alumno')) {
            $rutinasQuery->where('publica', true);
        }

        $rutinas = $rutinasQuery->limit($limit)
            ->get(['id', 'nivel', 'modalidad']);

        // === Alumnos: solo trainer/admin ===
        $alumnos = [];
        if ($user->hasRole(['trainer', 'administrador'])) {
            $alumnos = User::query()
                ->where('suspended', false)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('nick', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get(['id', 'name', 'nick', 'email', 'role']);
        }

        return response()->json([
            'q' => $q,
            'ejercicios' => $ejercicios,
            'alumnos' => $alumnos,
            'rutinas' => $rutinas,
        ]);
    }

    /**
     * Stats globales de la comunidad por ejercicio (cacheadas 5 min).
     * Devuelve percentiles (p25, p50, p75, p90) del peso levantado por ejercicio.
     */
    public function comunidadStats(Request $request)
    {
        $ejercicio = trim((string) $request->query('ejercicio', ''));
        if (mb_strlen($ejercicio) < 2) {
            return response()->json(['error' => 'Ejercicio requerido.'], 422);
        }

        $cacheKey = 'community.stats.' . md5(mb_strtolower($ejercicio));
        $payload = Cache::remember($cacheKey, 300, function () use ($ejercicio) {
            // Obtener el peso máximo por (usuario, ejercicio)
            $maximos = DB::table('historials')
                ->where('ejercicio_nombre', $ejercicio)
                ->where('peso', '>', 0)
                ->groupBy('user_id', 'ejercicio_nombre')
                ->select('user_id', 'ejercicio_nombre', DB::raw('MAX(peso) as max_peso'))
                ->get();

            $totalUsuarios = $maximos->count();
            if (!$totalUsuarios) {
                return ['total_usuarios' => 0, 'percentiles' => []];
            }

            $pesos = $maximos->pluck('max_peso')->sort()->values();
            $percentil = function ($p) use ($pesos) {
                if ($pesos->isEmpty()) return null;
                $idx = (int) ceil(($p / 100) * $pesos->count()) - 1;
                return round((float) $pesos[max(0, min($idx, $pesos->count() - 1))], 1);
            };

            return [
                'total_usuarios' => $totalUsuarios,
                'percentiles' => [
                    'p25' => $percentil(25),
                    'p50' => $percentil(50),
                    'p75' => $percentil(75),
                    'p90' => $percentil(90),
                ],
                'max' => round((float) $pesos->max(), 1),
                'promedio' => round((float) $pesos->avg(), 1),
            ];
        });

        return response()->json(['ejercicio' => $ejercicio] + $payload);
    }
}