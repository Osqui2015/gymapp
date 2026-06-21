<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\MedallaUsuario;
use App\Models\Meta;
use App\Models\Progreso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainerTimelineController extends Controller
{
    /**
     * Devuelve la línea de tiempo de eventos de un alumno:
     * - Entrenamientos (sesiones de entrenamiento registradas)
     * - Medallas desbloqueadas
     * - Metas creadas / completadas
     * - Medidas corporales registradas
     * - PRs (peso máximo histórico nuevo por ejercicio)
     */
    public function show($alumnoId)
    {
        $alumno = User::findOrFail($alumnoId);

        $eventos = [];

        // Entrenamientos: agrupar por día con peso total
        Historial::where('user_id', $alumno->id)
            ->select(DB::raw('DATE(fecha) as dia'), DB::raw('COUNT(*) as series'), DB::raw('SUM(peso) as volumen'))
            ->groupBy('dia')
            ->orderBy('dia', 'desc')
            ->limit(30)
            ->get()
            ->each(function ($row) use (&$eventos) {
                $eventos[] = [
                    'tipo' => 'entrenamiento',
                    'fecha' => $row->dia,
                    'titulo' => "Entrenó {$row->series} series",
                    'descripcion' => $row->volumen > 0 ? "Volumen total: {$row->volumen} kg" : null,
                    'icono' => '💪',
                    'color' => 'bg-emerald-500',
                ];
            });

        // Medallas desbloqueadas
        MedallaUsuario::where('user_id', $alumno->id)
            ->orderBy('ganado_at', 'desc')
            ->get()
            ->each(function ($mu) use (&$eventos) {
                $eventos[] = [
                    'tipo' => 'medalla',
                    'fecha' => $mu->ganado_at?->toDateString(),
                    'titulo' => '🏆 Desbloqueó medalla: ' . ($mu->nombre ?? 'Sin nombre'),
                    'descripcion' => $mu->descripcion,
                    'icono' => '🏆',
                    'color' => 'bg-amber-500',
                ];
            });

        // Metas creadas/completadas
        Meta::where('user_id', $alumno->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->each(function ($m) use (&$eventos) {
                $eventos[] = [
                    'tipo' => 'meta',
                    'fecha' => $m->created_at?->toDateString(),
                    'titulo' => $m->completada ? "✅ Completó meta: {$m->titulo}" : "🎯 Nueva meta: {$m->titulo}",
                    'descripcion' => $m->descripcion,
                    'icono' => $m->completada ? '✅' : '🎯',
                    'color' => $m->completada ? 'bg-emerald-500' : 'bg-indigo-500',
                ];
            });

        // Medidas corporales
        Progreso::where('user_id', $alumno->id)
            ->whereNotNull('peso_corporal')
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get()
            ->each(function ($p) use (&$eventos) {
                $eventos[] = [
                    'tipo' => 'medida',
                    'fecha' => $p->fecha?->toDateString(),
                    'titulo' => "Se pesó: {$p->peso_corporal} kg",
                    'descripcion' => null,
                    'icono' => '⚖️',
                    'color' => 'bg-blue-500',
                ];
            });

        // Ordenar por fecha desc
        usort($eventos, function ($a, $b) {
            return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
        });

        return response()->json(['alumno' => $alumno->only(['id', 'name', 'nick']), 'eventos' => $eventos]);
    }
}