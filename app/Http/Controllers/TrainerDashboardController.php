<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\Progreso;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use App\Models\Ejercicio;
use App\Models\EjercicioTrainer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrainerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $trainer = $request->user();

        // Obtener todos los alumnos del trainer
        $alumnos = User::where('trainer_id', $trainer->id)
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'email']);

        $alumnoIds = $alumnos->pluck('id')->toArray();

        // === MÉTRICAS CLAVE ===
        
        // Alumnos activos vs inactivos esta semana
        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        $alumnosActivos = Historial::whereIn('user_id', $alumnoIds)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->distinct('user_id')
            ->count('user_id');

        $alumnosInactivos = count($alumnoIds) - $alumnosActivos;

        // Alertas de inactividad (más de 7 días sin entrenar)
        $alumnosInactivos7Dias = [];
        foreach ($alumnos as $alumno) {
            $ultimoEntrenamiento = Historial::where('user_id', $alumno->id)
                ->where('completado', true)
                ->orderBy('fecha', 'desc')
                ->first();

            if (!$ultimoEntrenamiento) {
                $diasSinEntrenar = 999;
            } else {
                $diasSinEntrenar = Carbon::now()->diffInDays($ultimoEntrenamiento->fecha);
            }

            if ($diasSinEntrenar >= 7) {
                $alumnosInactivos7Dias[] = [
                    'id' => $alumno->id,
                    'name' => $alumno->name,
                    'nick' => $alumno->nick,
                    'dias_inactividad' => $diasSinEntrenar,
                    'ultimo_entrenamiento' => $ultimoEntrenamiento?->fecha?->format('d/m/Y'),
                ];
            }
        }

        // Últimos entrenamientos completados
        $ultimosEntrenamientos = Historial::whereIn('user_id', $alumnoIds)
            ->where('completado', true)
            ->with('user:id,name,nick')
            ->select('user_id', 'rutina_nombre', 'dia', 'fecha')
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name ?? 'Usuario',
                    'user_nick' => $item->user->nick ?? '',
                    'rutina' => $item->rutina_nombre,
                    'dia' => $item->dia,
                    'fecha' => $item->fecha->format('d/m/Y'),
                ];
            });

        // Alumnos con info básica y rutina
        $alumnosConInfo = collect($alumnoIds)->map(function ($id) use ($alumnos, $inicioSemana, $finSemana) {
            $alumno = $alumnos->firstWhere('id', $id);
            $tieneActividadSemana = Historial::where('user_id', $id)
                ->whereBetween('fecha', [$inicioSemana, $finSemana])
                ->exists();

            $rutina = UserRutina::where('user_id', $id)->first();

            return [
                'id' => $id,
                'name' => $alumno->name ?? '',
                'nick' => $alumno->nick ?? '',
                'email' => $alumno->email ?? '',
                'activo_semana' => $tieneActividadSemana,
                'rutina' => $rutina ? "{$rutina->nivel} {$rutina->modalidad}" : null,
                'dia_actual' => $rutina?->dia_actual,
            ];
        });

        return response()->json([
            'alumnos_activos' => $alumnosActivos,
            'alumnos_inactivos' => $alumnosInactivos,
            'alumnos_inactivos_7dias' => $alumnosInactivos7Dias,
            'ultimos_entrenamientos' => $ultimosEntrenamientos,
            'total_alumnos' => count($alumnoIds),
            'alumnos' => $alumnosConInfo,
        ]);
    }

    public function verAlumno(Request $request, User $alumno)
    {
        $trainer = $request->user();

        // Verificar que el alumno pertence al trainer
        if ($alumno->trainer_id !== $trainer->id) {
            return response()->json(['error' => 'No tienes acceso a este alumno'], 403);
        }

        // Historial de pesos por ejercicio
        $historialPesos = Historial::where('user_id', $alumno->id)
            ->whereNotNull('peso')
            ->where('peso', '>', 0)
            ->orderBy('fecha', 'asc')
            ->get()
            ->groupBy('ejercicio_nombre')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'fecha' => $item->fecha->format('d/m/Y'),
                        'dia' => $item->dia,
                        'peso' => (float) $item->peso,
                        'reps' => $item->reps_realizadas,
                        'completado' => $item->completado,
                    ];
                });
            });

        // Tonelaje total por sesión (agrupado por fecha)
        $tonelajeSesiones = Historial::where('user_id', $alumno->id)
            ->whereNotNull('peso')
            ->where('completado', true)
            ->selectRaw('fecha, SUM(peso * reps_realizadas) as volumen_total, COUNT(*) as ejercicios_completados')
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                return [
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'volumen_total' => round($item->volumen_total, 1),
                    'ejercicios_completados' => $item->ejercicios_completados,
                ];
            });

        // Registro de medidas corporales
        $medidasCorporales = Progreso::where('user_id', $alumno->id)
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'peso' => $item->peso,
                    'altura' => $item->altura,
                    'cuello' => $item->cuello,
                    'hombros' => $item->hombros,
                    'pecho' => $item->pecho,
                    'brazos' => $item->brazos,
                    'cintura' => $item->cintura,
                    'cadera' => $item->cadera,
                    'muslos' => $item->muslos,
                    'pantorrillas' => $item->pantorrillas,
                ];
            });

        // Rutina actual del alumno
        $rutinaActual = UserRutina::where('user_id', $alumno->id)->first();

        // Historial de entradas (para poder dejar comentarios)
        $historialCompleto = Historial::where('user_id', $alumno->id)
            ->with('trainer:id,name')
            ->orderBy('fecha', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fecha' => $item->fecha->format('d/m/Y'),
                    'rutina_nombre' => $item->rutina_nombre,
                    'dia' => $item->dia,
                    'ejercicio_nombre' => $item->ejercicio_nombre,
                    'series_numero' => $item->series_numero,
                    'peso' => $item->peso,
                    'reps_realizadas' => $item->reps_realizadas,
                    'completado' => $item->completado,
                    'comentario_trainer' => $item->comentario_trainer,
                    'trainer_nombre' => $item->trainer?->name,
                ];
            });

        return response()->json([
            'alumno' => [
                'id' => $alumno->id,
                'name' => $alumno->name,
                'nick' => $alumno->nick,
                'email' => $alumno->email,
            ],
            'rutina_actual' => $rutinaActual,
            'historial_pesos' => $historialPesos,
            'tonelaje_sesiones' => $tonelajeSesiones,
            'medidas_corporales' => $medidasCorporales,
            'historial_completo' => $historialCompleto,
        ]);
    }

    public function agregarComentario(Request $request, User $alumno)
    {
        $trainer = $request->user();

        if ($alumno->trainer_id !== $trainer->id && !$trainer->hasRole('administrador')) {
            return response()->json(['error' => 'No tienes acceso a este alumno'], 403);
        }

        $validated = $request->validate([
            'historial_id' => 'required|exists:historials,id',
            'comentario' => 'required|string|max:500',
        ]);

        $historial = Historial::where('id', $validated['historial_id'])
            ->where('user_id', $alumno->id)
            ->first();

        if (!$historial) {
            return response()->json(['error' => 'Entrada de historial no encontrada'], 404);
        }

        $historial->update([
            'comentario_trainer' => $validated['comentario'],
            'trainer_id' => $trainer->id,
        ]);

        return response()->json(['success' => true, 'comentario' => $historial->comentario_trainer]);
    }

    public function duplicarRutina(Request $request)
    {
        $trainer = $request->user();
        $validated = $request->validate([
            'rutina_id' => 'required|exists:rutinas,id',
            'nombre_nuevo' => 'required|string|max:255',
        ]);

        $rutinaOriginal = Rutina::find($validated['rutina_id']);

        // Copiar la rutina y todos sus ejercicios
        $rutinaOriginal->each(function ($ejercicio) use ($trainer, $validated) {
            Rutina::create([
                'nivel' => $validated['nombre_nuevo'],
                'modalidad' => $ejercicio->modalidad,
                'dia' => $ejercicio->dia,
                'created_by' => $trainer->id,
                'series' => $ejercicio->series,
                'reps_min' => $ejercicio->reps_min,
                'reps_max' => $ejercicio->reps_max,
                'descanso_min' => $ejercicio->descanso_min,
                'ejercicio_nombre' => $ejercicio->ejercicio_nombre,
                'orden' => $ejercicio->orden,
            ]);
        });

        return response()->json([
            'success' => true,
            'nombre' => $validated['nombre_nuevo'],
        ]);
    }

    public function ejerciciosPrivados(Request $request)
    {
        $trainer = $request->user();

        $ejercicios = EjercicioTrainer::where('trainer_id', $trainer->id)
            ->orderBy('grupo_muscular')
            ->orderBy('nombre')
            ->get();

        return response()->json($ejercicios);
    }

    public function crearEjercicioPrivado(Request $request)
    {
        $trainer = $request->user();

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'grupo_muscular' => 'required|string|max:100',
            'equipamiento' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $ejercicio = EjercicioTrainer::create([
            'trainer_id' => $trainer->id,
            'nombre' => $validated['nombre'],
            'grupo_muscular' => $validated['grupo_muscular'],
            'equipamiento' => $validated['equipamiento'] ?? 'Ninguno',
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return response()->json($ejercicio, 201);
    }

    public function eliminarEjercicioPrivado(Request $request, $id)
    {
        $trainer = $request->user();

        $ejercicio = EjercicioTrainer::where('id', $id)
            ->where('trainer_id', $trainer->id)
            ->first();

        if (!$ejercicio) {
            return response()->json(['error' => 'Ejercicio no encontrado'], 404);
        }

        $ejercicio->delete();

        return response()->json(['success' => true]);
    }

    public function obtenerTodasRutinas(Request $request)
    {
        $trainer = $request->user();

        $rutinas = Rutina::where('created_by', $trainer->id)
            ->get()
            ->groupBy('nivel')
            ->map(function ($items, $nombre) {
                return [
                    'id' => $items->first()->id,
                    'nombre' => $nombre,
                    'dias' => $items->pluck('dia')->unique()->count(),
                    'modalidad' => $items->first()->modalidad,
                ];
            })
            ->values();

        return response()->json($rutinas);
    }
}