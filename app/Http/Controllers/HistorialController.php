<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\HistorialService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function __construct(
        private HistorialService $historial,
        private StatsService $stats,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id) {
            if (! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($user->hasRole(User::ROLE_TRAINER)) {
                $target = User::findOrFail($targetUserId);
                if ($target->trainer_id !== $user->id) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
            }
        }

        $query = Historial::where('user_id', $targetUserId)
            ->when($request->filled('rutina_nombre'), function ($query) use ($request) {
                $query->where('rutina_nombre', $request->rutina_nombre);
            })
            ->when($request->filled('ejercicio'), function ($query) use ($request) {
                $query->where('ejercicio_nombre', 'like', '%'.$request->ejercicio.'%');
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->where('fecha', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->where('fecha', '<=', $request->to);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('id');

        // Paginación opcional
        if ($request->boolean('paginated') || $request->has('page')) {
            $perPage = min((int) $request->input('per_page', 50), 200);

            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function guardar(Request $request)
    {
        $user = $request->user();
        $hoy = Carbon::now()->toDateString();
        // Ventana valida para fechas offline: 7 dias en el pasado hasta hoy.
        // No aceptamos fechas futuras para evitar registros fraudulentos.
        $minFecha = Carbon::now()->subDays(7)->toDateString();

        // Acepta DOS formatos (backward compat):
        //   1) Single set: campos a nivel raíz (usado por el test SuperserieTest)
        //   2) Array de sets: { ..., series: [ {...}, {...} ] } (usado por MobileQuickSeriesInput)
        $series = $request->input('series');
        if (is_array($series) && ! empty($series)) {
            $records = $series;
        } else {
            $records = [$request->all()];
        }

        $validatedRecords = [];
        foreach ($records as $rec) {
            $data = validator($rec, [
                'rutina_nombre' => ['required', 'string', 'max:255'],
                'dia' => ['required', 'string', 'max:255'],
                'ejercicio_nombre' => ['required', 'string', 'max:255'],
                'series_numero' => ['required', 'integer', 'min:1', 'max:50'],
                'series_completadas' => ['nullable', 'integer', 'min:0', 'max:50'],
                'reps_min' => ['required', 'string', 'max:255'],
                'reps_max' => ['required', 'string', 'max:255'],
                'reps_realizadas' => ['nullable', 'integer', 'min:0', 'max:1000'],
                'descanso_min' => ['required', 'numeric', 'min:0', 'max:30'],
                'peso' => ['nullable', 'numeric', 'min:0', 'max:1000'],
                'completado' => ['nullable', 'boolean'],
                'superserie_grupo' => ['nullable', 'integer', 'min:0'],
                // Nota libre por set (cómo se sintió, dolor, RPE subjetivo, etc.)
                'nota_user' => ['nullable', 'string', 'max:500'],
                // Fase 3: esfuerzo por set
                'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe'],
                'esfuerzo_valor' => ['nullable', 'integer', 'min:0', 'max:10'],
                // Nivel 4: tipo de serie y sesión activa
                'tipo_serie' => ['nullable', 'string', 'in:efectiva,calentamiento,dropset,al_fallo'],
                'sesion_uuid' => ['nullable', 'string', 'max:64'],
                // === Soporte offline (Oleada 1 — Modo entrenamiento) ===
                // client_id: UUID generado en el cliente. Solo lo recibimos para logs/debug;
                // no lo persistimos porque la idempotencia esta garantizada por la clave
                // compuesta (user_id, rutina, dia, ejercicio, serie).
                'client_id' => ['nullable', 'string', 'max:64'],
                // fecha: fecha real en que se hizo la serie (no la del sync).
                // Si esta ausente, usamos hoy. Si viene, validamos que este en la ventana valida.
                'fecha' => ['nullable', 'date', 'after_or_equal:'.$minFecha, 'before_or_equal:'.$hoy],
            ])->validate();

            $data['user_id'] = $user->id;
            $data['tipo_serie'] = $data['tipo_serie'] ?? 'efectiva';
            // Si el cliente mando fecha, respetarla (caso offline).
            // Si no, usar hoy (caso online, retrocompat).
            $data['fecha'] = $rec['fecha'] ?? $hoy;
            // client_id no se persiste: la clave compuesta ya garantiza idempotencia.
            unset($data['client_id']);
            $validatedRecords[] = $data;
        }

        // Nivel 5: transacción para que un fallo en una serie no deje el resto
        // parcialmente persistidas (atomicidad all-or-nothing).
        \DB::transaction(function () use ($validatedRecords) {
            foreach ($validatedRecords as $data) {
                Historial::updateOrCreate(
                    [
                        'user_id' => $data['user_id'],
                        'rutina_nombre' => $data['rutina_nombre'],
                        'dia' => $data['dia'],
                        'ejercicio_nombre' => $data['ejercicio_nombre'],
                        'series_numero' => $data['series_numero'],
                    ],
                    $data
                );
            }
        });

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Guardado',
            'count' => count($validatedRecords),
            'new_medals' => $newMedals,
        ]);
    }

    public function marcarCompletado(Request $request)
    {
        $user = $request->user();

        $historial = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $request->rutina_nombre)
            ->where('dia', $request->dia)
            ->where('ejercicio_nombre', $request->ejercicio_nombre)
            ->where('series_numero', $request->series_numero)
            ->first();

        if ($historial) {
            $historial->update(['completado' => true]);
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Completado',
            'new_medals' => $newMedals,
        ]);
    }

    public function obtenerProgreso(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'rutina_nombre' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->historial->obtenerProgreso($user->id, $data['rutina_nombre'])
        );
    }

    /**
     * Edita una serie ya registrada. Nivel 6: necesario para corregir
     * errores del gym (cargar mal un peso, equivocarse de repeticiones,
     * cambiar tipo de serie, etc.).
     *
     * Autorización:
     *   - El dueño siempre puede editar sus series.
     *   - Un trainer/admin puede editar series de un alumno asignado
     *     (Nivel 5 - regla unica en TrainerTimelineController::puedeVerAlumno).
     */
    public function update(Request $request, int $id)
    {
        $historial = Historial::findOrFail($id);
        $auth = $request->user();

        if (! $this->puedeEditarSerie($auth, $historial)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'peso' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'reps_realizadas' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'series_completadas' => ['nullable', 'integer', 'min:0', 'max:50'],
            'completado' => ['nullable', 'boolean'],
            'tipo_serie' => ['nullable', 'string', 'in:efectiva,calentamiento,dropset,al_fallo'],
            'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe'],
            'esfuerzo_valor' => ['nullable', 'integer', 'min:0', 'max:10'],
            'nota_user' => ['nullable', 'string', 'max:500'],
            'descanso_min' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ]);

        $historial->fill($data);
        $historial->save();

        return response()->json(['data' => $historial->fresh()]);
    }

    /**
     * Elimina una serie registrada. Útil para borrar un duplicado o
     * un registro erróneo (ej: cargaste dos veces la misma serie).
     *
     * Misma autorización que update().
     */
    public function destroy(Request $request, int $id)
    {
        $historial = Historial::findOrFail($id);
        $auth = $request->user();

        if (! $this->puedeEditarSerie($auth, $historial)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $historial->delete();

        return response()->json(['ok' => true]);
    }

    private function puedeEditarSerie(User $auth, Historial $serie): bool
    {
        if ($serie->user_id === $auth->id) {
            return true;
        }

        return TrainerTimelineController::puedeVerAlumno(
            $auth,
            User::findOrFail($serie->user_id)
        );
    }

    public function finalizarRutina(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $result = $this->historial->finalizarRutinaDia($user);

        if (isset($result['error'])) {
            $code = $result['error'] === 'No hay rutina seleccionada' ? 404 : 400;

            return response()->json($result, $code);
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Rutina finalizada',
            'dia_actual' => $result['dia_actual'],
            'rutina_nombre' => $result['rutina_nombre'],
            'new_medals' => $newMedals,
        ]);
    }

    /**
     * Devuelve las fechas con sesiones del user en un mes/año.
     * Para el componente del calendario.
     *
     * Query params: ?year=2026&month=8&user_id=X (opcional, trainer/admin)
     * Response: { dates: ['2026-08-15', '2026-08-16'], counts: { '2026-08-15': 3, ... } }
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id && ! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($user->hasRole(User::ROLE_TRAINER) && $targetUserId !== $user->id) {
            $target = User::findOrFail($targetUserId);
            if ($target->trainer_id !== $user->id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);

        return response()->json(
            $this->historial->buildCalendar($targetUserId, $year, $month)
        );
    }

    /**
     * Fase 7 — Resumen de la semana con dots para WeekCalendar.
     *
     * Por defecto la semana actual (lunes a domingo).
     * Query: ?week_start=YYYY-MM-DD&user_id=X
     */
    public function weekSummary(Request $request)
    {
        $user = $request->user();
        $targetUserId = (int) $request->integer('user_id', $user->id);

        if ($targetUserId !== $user->id && ! $user->hasRole([User::ROLE_TRAINER, User::ROLE_ADMINISTRADOR])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        if ($user->hasRole(User::ROLE_TRAINER) && $targetUserId !== $user->id) {
            $target = User::findOrFail($targetUserId);
            if ($target->trainer_id !== $user->id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        return response()->json(
            $this->historial->buildWeekSummary(
                $targetUserId,
                $request->filled('week_start') ? $request->week_start : null,
                $this->stats,
            )
        );
    }

    /**
     * Compara el rendimiento de un ejercicio entre dos fechas.
     * GET /api/historial/comparar?ejercicio=X&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function comparar(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'ejercicio' => 'required|string|max:255',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $reporte = $this->historial->compararEjercicio(
            $user->id,
            $data['ejercicio'],
            $data['desde'],
            $data['hasta'],
        );

        return response()->json($reporte);
    }

    /**
     * Devuelve la ultima sesion en la que el usuario trabajo un ejercicio,
     * con todas sus series (efectivas + calentamiento). Usado por la pantalla
     * de entrenamiento activo para mostrar "ultima vez: Xkg x Y reps" y
     * sugerir carga para el set actual.
     *
     * GET /api/historial/ultimo?ejercicio=Press%20de%20banca
     *
     * Response 200:
     *   {
     *     "encontrado": true,
     *     "ejercicio": "Press de banca",
     *     "fecha": "2026-09-12",
     *     "series": [ { peso, reps, tipo_serie, esfuerzo_tipo, esfuerzo_valor } ],
     *     "peso_top": 60,        // peso mas alto usado en series efectivas
     *     "reps_en_peso_top": 8, // reps realizadas a ese peso top
     *     "ultimo_esfuerzo": { tipo: 'rir', valor: 2 } | null
     *   }
     */
    public function ultimo(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'ejercicio' => 'required|string|max:255',
        ]);

        $nombre = trim($data['ejercicio']);
        if ($nombre === '') {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        // Tomamos la fecha maxima en la que hay sets del ejercicio.
        $ultimaFecha = Historial::where('user_id', $user->id)
            ->where('ejercicio_nombre', $nombre)
            ->where('completado', true)
            ->max('fecha');

        if (! $ultimaFecha) {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        $series = Historial::where('user_id', $user->id)
            ->where('ejercicio_nombre', $nombre)
            ->where('fecha', $ultimaFecha)
            ->orderBy('series_numero')
            ->get(['peso', 'reps_realizadas', 'tipo_serie', 'esfuerzo_tipo', 'esfuerzo_valor', 'series_numero']);

        if ($series->isEmpty()) {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        // Calculamos el peso top entre las series EFECTIVAS (descartamos calentamiento,
        // dropset y al_fallo porque no son representativas del "techo" de trabajo).
        $efectivas = $series->filter(function ($s) {
            $tipo = strtolower((string) ($s->tipo_serie ?? 'efectiva'));
            return $tipo === '' || $tipo === 'efectiva';
        });

        if ($efectivas->isEmpty()) {
            $efectivas = $series;
        }

        $pesoTop = null;
        $repsEnPesoTop = null;
        foreach ($efectivas as $s) {
            $peso = (float) ($s->peso ?? 0);
            if ($peso <= 0) {
                continue;
            }
            if ($pesoTop === null || $peso > $pesoTop) {
                $pesoTop = $peso;
                $repsEnPesoTop = (int) ($s->reps_realizadas ?? 0);
            }
        }

        // Si ninguna serie efectiva tuvo peso, caemos al primer registro con peso.
        if ($pesoTop === null) {
            foreach ($series as $s) {
                $peso = (float) ($s->peso ?? 0);
                if ($peso > 0) {
                    $pesoTop = $peso;
                    $repsEnPesoTop = (int) ($s->reps_realizadas ?? 0);
                    break;
                }
            }
        }

        // Ultimo esfuerzo percibido del set mas pesado.
        $ultimoEsfuerzo = null;
        if ($pesoTop !== null) {
            $setReferencia = $efectivas->first(function ($s) use ($pesoTop) {
                return (float) ($s->peso ?? 0) === $pesoTop;
            });
            if ($setReferencia && $setReferencia->esfuerzo_tipo !== null && $setReferencia->esfuerzo_valor !== null) {
                $ultimoEsfuerzo = [
                    'tipo' => $setReferencia->esfuerzo_tipo,
                    'valor' => (int) $setReferencia->esfuerzo_valor,
                ];
            }
        }

        return response()->json([
            'encontrado' => true,
            'ejercicio' => $nombre,
            'fecha' => Carbon::parse($ultimaFecha)->toDateString(),
            'series' => $series->map(function ($s) {
                return [
                    'peso' => (float) ($s->peso ?? 0),
                    'reps' => (int) ($s->reps_realizadas ?? 0),
                    'tipo_serie' => $s->tipo_serie ?? 'efectiva',
                    'esfuerzo_tipo' => $s->esfuerzo_tipo,
                    'esfuerzo_valor' => $s->esfuerzo_valor !== null ? (int) $s->esfuerzo_valor : null,
                ];
            })->values(),
            'peso_top' => $pesoTop,
            'reps_en_peso_top' => $repsEnPesoTop,
            'ultimo_esfuerzo' => $ultimoEsfuerzo,
        ]);
    }
}
