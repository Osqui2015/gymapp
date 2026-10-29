<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\User;
use App\Http\Controllers\Concerns\ResuelveAlumnoObjetivo;
use App\Services\AchievementService;
use App\Services\CacheHistorial;
use App\Services\HistorialService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    use ResuelveAlumnoObjetivo;

    public function __construct(
        private HistorialService $historial,
        private StatsService $stats,
    ) {}

    /**
     * Columnas que consume el frontend de /historial.
     * Se trimming el payload: created_at, updated_at, trainer_id,
     * comentario_trainer, sesion_uuid y series_completadas no los usa nadie
     * y-each row- eran ~25% del tamaño de la respuesta.
     */
    private const COLUMNAS_HISTORIAL = [
        'id', 'user_id', 'rutina_nombre', 'dia', 'ejercicio_nombre', 'ejercicio_id',
        'series_numero', 'reps_min', 'reps_max', 'reps_realizadas', 'descanso_min',
        'peso', 'completado', 'esfuerzo_tipo', 'esfuerzo_valor', 'tipo_serie',
        'nota_user', 'fecha', 'superserie_grupo',
    ];

    public function index(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);

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

        return response()->json($query->get(self::COLUMNAS_HISTORIAL));
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
                //   rir   = RIR 0-5 (0 = al límite, 5 = muy fácil)
                //   rpe   = RPE 6-10 (10 = al límite percibido)
                //   fallo = al fallo absoluto (no podés sacar ni una rep más)
                'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe,fallo'],
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
            // Normalizada a YYYY-MM-DD porque la columna es DATE: la clave de
            // idempotencia tiene que comparar contra el mismo valor que se
            // guarda, si no el WHERE no matchea nunca.
            $data['fecha'] = isset($rec['fecha']) && $rec['fecha'] !== null
                ? Carbon::parse($rec['fecha'])->toDateString()
                : $hoy;
            // client_id no se persiste: la clave compuesta ya garantiza idempotencia.
            unset($data['client_id']);
            $validatedRecords[] = $data;
        }

        // Nivel 5: transacción para que un fallo en una serie no deje el resto
        // parcialmente persistidas (atomicidad all-or-nothing).
        \DB::transaction(function () use ($validatedRecords) {
            foreach ($validatedRecords as $data) {
                $this->guardarSerieIdempotente($data);
            }
        });

        // El historial cambió: el cache de stats/body map/1RM tiene que
        // invalidarse, si no el usuario ve KPIs de hasta 10 minutos de antigüedad
        // justo después de guardar la serie.
        CacheHistorial::invalidate($user->id);

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Guardado',
            'count' => count($validatedRecords),
            'new_medals' => $newMedals,
        ]);
    }

    /**
     * Guarda una serie de forma idempotente.
     *
     * La clave incluye `fecha`: registrar el MISMO set en OTRA fecha crea una
     * fila nueva en vez de pisar la sesión anterior. Sin `fecha` en la clave,
     * reentrenar el mismo set sobreescribía el historial y todos los KPIs
     * (volumen, 1RM, rachas, heatmap) subcontaban.
     *
     * El índice UNIQUE `uq_historials_idempotencia` (migración
     * 2026_10_29_000000_fix_historials_idempotencia_y_fecha) garantiza la
     * invariante en la base; este método maneja la carrera entre el SELECT y
     * el INSERT cuando dos requests llegan al mismo tiempo.
     *
     * @param  array<string, mixed>  $data  Datos validados de la serie.
     */
    private function guardarSerieIdempotente(array $data): void
    {
        Historial::guardarIdempotente($data);
    }

    public function marcarCompletado(Request $request)
    {
        $user = $request->user();
        $hoy = Carbon::now()->toDateString();

        $data = $request->validate([
            'rutina_nombre' => ['required', 'string', 'max:255'],
            'dia' => ['required', 'string', 'max:255'],
            'ejercicio_nombre' => ['required', 'string', 'max:255'],
            'series_numero' => ['required', 'integer', 'min:1', 'max:50'],
            'fecha' => ['nullable', 'date', 'before_or_equal:'.$hoy],
        ]);

        // Sin fecha explícita se marca la de HOY. Antes tomaba la fila MÁS
        // VIEJA de cualquier fecha (sin order, el primero que encontrara),
        // así que con colisiones marcaba un registro de semanas atrás.
        $fecha = isset($data['fecha']) && $data['fecha'] !== null
            ? Carbon::parse($data['fecha'])->toDateString()
            : $hoy;

        $historial = Historial::where('user_id', $user->id)
            ->where('rutina_nombre', $data['rutina_nombre'])
            ->where('dia', $data['dia'])
            ->where('ejercicio_nombre', $data['ejercicio_nombre'])
            ->where('series_numero', $data['series_numero'])
            ->whereDate('fecha', $fecha)
            ->orderByDesc('id')
            ->first();

        if ($historial) {
            $historial->update(['completado' => true]);
            CacheHistorial::invalidate($user->id);
        }

        $newMedals = AchievementService::checkWorkoutMilestones($user);

        return response()->json([
            'message' => 'Completado',
            'completada' => (bool) $historial,
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
            'esfuerzo_tipo' => ['nullable', 'string', 'in:rir,rpe,fallo'],
            'esfuerzo_valor' => ['nullable', 'integer', 'min:0', 'max:10'],
            'nota_user' => ['nullable', 'string', 'max:500'],
            'descanso_min' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ]);

        $historial->fill($data);
        $historial->save();

        CacheHistorial::invalidate($historial->user_id);

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

        CacheHistorial::invalidate($historial->user_id);

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
        $targetUserId = $this->usuarioObjetivo($request);

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
        $targetUserId = $this->usuarioObjetivo($request);

        return response()->json(
            $this->historial->buildWeekSummary(
                $targetUserId,
                $request->filled('week_start') ? $request->week_start : null,
                $this->stats,
            )
        );
    }

    /**
     * Análisis de esfuerzo (RIR/RPE) para la card "Esfuerzo" del historial.
     *
     * GET /api/historial/effort-analysis?range=30d|90d|1y|all&user_id=X
     *
     * - range: ventana temporal del análisis (default '30d').
     * - user_id: opcional. Permite a trainer/admin consultar el análisis
     *   de un alumno (misma regla de autorización que /historial/calendar).
     *
     * Response 200:
     *   {
     *     "range": "30d",
     *     "summary": {
     *       "total_sets": 42,          // sets completados en la ventana
     *       "sets_with_effort": 30,    // sets con esfuerzo registrado
     *       "avg_rir": 2.1,            // RIR promedio (RPE normalizado)
     *       "high_effort_pct": 43,     // % de sets con RIR <= 2
     *       "coverage_pct": 71         // % de sets con esfuerzo
     *     },
     *     "distribution": { "0": 2, "1": 5, "2": 8, "3": 9, "4": 6 },
     *     "weekly_trend": [ { "week_start": "2026-10-19", "avg_rir": 2.0, "sets": 12 } ],
     *     "by_exercise": [ { "ejercicio": "Press banca", "sets": 8, "avg_rir": 1.8 } ]
     *   }
     */
    public function effortAnalysis(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);

        $range = $this->stats->normalizeEffortRange($request->input('range', '30d'));

        $payload = $this->stats->buildEffortAnalysis($targetUserId, $range);
        // El front espera un mapa {"0": n, "1": n, ...}; sin este cast PHP
        // lo serializaría como lista indexada y rompería el acceso por clave.
        $payload['distribution'] = (object) $payload['distribution'];

        return response()->json($payload);
    }

    /**
     * Compara el rendimiento de un ejercicio entre dos fechas.
     * GET /api/historial/comparar?ejercicio=X&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     */
    public function comparar(Request $request)
    {
        $targetUserId = $this->usuarioObjetivo($request);

        $data = $request->validate([
            'ejercicio' => 'required|string|max:255',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $reporte = $this->historial->compararEjercicio(
            $targetUserId,
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
            'dia' => 'nullable|string|max:255',
            'rutina_nombre' => 'nullable|string|max:255',
        ]);

        $nombre = trim($data['ejercicio']);
        if ($nombre === '') {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        $queryBase = Historial::where('user_id', $user->id)
            ->where('ejercicio_nombre', $nombre)
            ->where('completado', true);

        // Si se especificó rutina_nombre, filtramos preferentemente por esa rutina
        if (! empty($data['rutina_nombre'])) {
            $rutinaQuery = (clone $queryBase)->where('rutina_nombre', $data['rutina_nombre']);
            if ($rutinaQuery->exists()) {
                $queryBase = $rutinaQuery;
            }
        }

        // Si se especificó día, priorizamos el historial correspondiente a ese día específico
        // (por ejemplo si el mismo ejercicio existe en Día 1 y en Día 3, cada día tiene su propio récord)
        if (! empty($data['dia'])) {
            $diaParam = trim($data['dia']);
            $diaQuery = (clone $queryBase)->where(function ($q) use ($diaParam) {
                $q->where('dia', $diaParam)
                  ->orWhere('dia', 'like', $diaParam . '%')
                  ->orWhere('dia', 'like', '%' . $diaParam . '%');
            });
            if ($diaQuery->exists()) {
                $queryBase = $diaQuery;
            }
        }

        // Buscamos la fecha más reciente:
        // 1. Priorizamos fechas que tengan series efectivas CON peso > 0
        $ultimaFecha = (clone $queryBase)
            ->where(function ($q) {
                $q->whereNull('tipo_serie')
                  ->orWhere('tipo_serie', '!=', 'calentamiento');
            })
            ->where('peso', '>', 0)
            ->max('fecha');

        // 2. Si no hay con peso > 0 (ej. ejercicios de peso corporal), tomamos cualquier fecha con series efectivas
        if (! $ultimaFecha) {
            $ultimaFecha = (clone $queryBase)
                ->where(function ($q) {
                    $q->whereNull('tipo_serie')
                      ->orWhere('tipo_serie', '!=', 'calentamiento');
                })
                ->max('fecha');
        }

        // 3. Fallback: cualquier fecha completada
        if (! $ultimaFecha) {
            $ultimaFecha = (clone $queryBase)->max('fecha');
        }

        if (! $ultimaFecha) {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        $series = (clone $queryBase)
            ->where('fecha', $ultimaFecha)
            ->orderBy('series_numero')
            ->get(['peso', 'reps_realizadas', 'tipo_serie', 'esfuerzo_tipo', 'esfuerzo_valor', 'series_numero']);

        if ($series->isEmpty()) {
            return response()->json(['encontrado' => false, 'ejercicio' => $nombre]);
        }

        // Calculamos el peso top y peso mínimo EXCLUSIVAMENTE entre las series EFECTIVAS (excluyendo calentamiento)
        $efectivas = $series->filter(function ($s) {
            $tipo = strtolower(trim((string) ($s->tipo_serie ?? 'efectiva')));
            return $tipo !== 'calentamiento';
        });

        // Solo si no hubo ninguna serie efectiva en ese día caemos a todas
        if ($efectivas->isEmpty()) {
            $efectivas = $series;
        }

        // Si hay series con peso > 0, usamos esas; de lo contrario usamos todas
        $conPeso = $efectivas->filter(fn ($s) => (float) ($s->peso ?? 0) > 0);
        $pool = $conPeso->isNotEmpty() ? $conPeso : $efectivas;

        // Ordenamos por peso desc, luego reps desc para encontrar el Máximo (Top)
        $sortedDesc = $pool->sort(function ($a, $b) {
            $pesoA = (float) ($a->peso ?? 0);
            $pesoB = (float) ($b->peso ?? 0);
            if ($pesoA !== $pesoB) {
                return $pesoB <=> $pesoA;
            }
            return ((int) ($b->reps_realizadas ?? 0)) <=> ((int) ($a->reps_realizadas ?? 0));
        })->values();

        // Ordenamos por peso asc, luego reps asc para encontrar el Mínimo
        $sortedAsc = $pool->sort(function ($a, $b) {
            $pesoA = (float) ($a->peso ?? 0);
            $pesoB = (float) ($b->peso ?? 0);
            if ($pesoA !== $pesoB) {
                return $pesoA <=> $pesoB;
            }
            return ((int) ($a->reps_realizadas ?? 0)) <=> ((int) ($b->reps_realizadas ?? 0));
        })->values();

        $setTop = $sortedDesc->first();
        $pesoTop = $setTop ? (float) ($setTop->peso ?? 0) : 0.0;
        $repsEnPesoTop = $setTop ? (int) ($setTop->reps_realizadas ?? 0) : 0;

        $setMin = $sortedAsc->first();
        $pesoMin = $setMin ? (float) ($setMin->peso ?? 0) : 0.0;
        $repsEnPesoMin = $setMin ? (int) ($setMin->reps_realizadas ?? 0) : 0;

        // Ultimo esfuerzo percibido del set mas pesado
        $ultimoEsfuerzo = null;
        if ($setTop && $setTop->esfuerzo_tipo !== null && $setTop->esfuerzo_valor !== null) {
            $ultimoEsfuerzo = [
                'tipo' => $setTop->esfuerzo_tipo,
                'valor' => (int) $setTop->esfuerzo_valor,
            ];
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
            'peso_max' => $pesoTop,
            'reps_en_peso_max' => $repsEnPesoTop,
            'peso_min' => $pesoMin,
            'reps_en_peso_min' => $repsEnPesoMin,
            'ultimo_esfuerzo' => $ultimoEsfuerzo,
        ]);
    }
}
