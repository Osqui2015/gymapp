<?php

namespace App\Http\Controllers;

use App\Models\Rutina;
use App\Models\RutinaFavorita;
use App\Models\User;
use App\Models\UserRutina;
use App\Services\AchievementService;
use App\Services\RutinaPrescripcionService;
use App\Services\RutinasSugeridasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RutinaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Rutina::query();

        if ($request->boolean('comunitarias')) {
            $query->where('publica', true);
            if ($user) {
                // Excluir rutinas del user actual, pero mantener las del
                // sistema (created_by IS NULL). En MySQL `null != 1` da NULL,
                // no TRUE, asi que sin el orWhereNull las rutinas oficiales
                // desaparecen del catalogo.
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', '!=', $user->id)
                        ->orWhereNull('created_by');
                });
            }
        } else {
            $query->where(function ($q) use ($user) {
                $q->whereNull('created_by');
                if ($user) {
                    $q->orWhere('created_by', $user->id);
                }
            });
        }

        if ($request->has('nivel') && $request->nivel) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->has('modalidad') && $request->modalidad) {
            $query->where('modalidad', $request->modalidad);
        }
        if ($request->has('dia') && $request->dia) {
            $query->where('dia', $request->dia);
        }

        $query->with(['ejercicio', 'creador'])->orderBy('orden');

        // Soporte de paginación: si el cliente lo pide, devolver paginado
        if ($request->boolean('paginated') || $request->has('page')) {
            $perPage = min((int) $request->input('per_page', 50), 200);
            $page = $query->paginate($perPage);
            $this->attachFavoritaFlag($page->getCollection(), $user);

            return response()->json($page);
        }

        // Si no, devolver todo (compatibilidad con clientes actuales)
        $rutinas = $query->get();
        $this->attachFavoritaFlag($rutinas, $user);

        return response()->json($rutinas);
    }

    /**
     * Adjunta el flag `is_favorita` a cada rutina de la colección.
     *
     * La pivot es por rutina_id (cada fila de `rutinas` es un día/ejercicio).
     * Pero para el UX el favorito es por MODALIDAD (nivel + modalidad): si
     * marcás "Intermedio 3 Días" como favorita, todos los días quedan
     * marcados. Por eso acá resolvemos por (nivel, modalidad) y propagamos
     * el flag a todas las filas del grupo en la respuesta.
     */
    protected function attachFavoritaFlag($rutinas, ?User $user): void
    {
        if (! $user || $rutinas->isEmpty()) {
            return;
        }

        // Traemos los IDs de las rutinas favoritas del user, agrupados por (nivel, modalidad).
        $favRutinaIds = RutinaFavorita::where('user_id', $user->id)
            ->whereIn('rutina_id', $rutinas->pluck('id')->all())
            ->pluck('rutina_id')
            ->all();

        if (empty($favRutinaIds)) {
            foreach ($rutinas as $r) {
                $r->is_favorita = false;
            }

            return;
        }

        $favSet = array_flip($favRutinaIds);
        $favGroups = []; // (nivel|modalidad) => true si alguna rutina del grupo es fav
        foreach ($rutinas as $r) {
            if (isset($favSet[$r->id])) {
                $favGroups[$r->nivel.'|'.$r->modalidad] = true;
            }
        }

        foreach ($rutinas as $r) {
            $r->is_favorita = isset($favGroups[$r->nivel.'|'.$r->modalidad]);
        }
    }

    /**
     * Reglas de validación de la prescripción estructurada.
     *
     * Misma forma que genera el seeder Rutina3DiasV2Seeder:
     *   - bloques: [{series, reps, tipo, valor}] con tipo = rir | rpe | fallo
     *     (valor es null cuando tipo = fallo).
     *   - rest_pause: {minisets: [{reps, descanso_seg, tipo, valor}]} o null.
     *   - progresion / consejo / etiqueta: texto libre.
     *
     * @return array<string, string>
     */
    protected function reglasPrescripcion(): array
    {
        return [
            'prescripcion' => 'nullable|array',
            'prescripcion.bloques' => 'nullable|array',
            'prescripcion.bloques.*.series' => 'nullable|integer|min:1',
            'prescripcion.bloques.*.reps' => 'nullable|integer|min:0',
            'prescripcion.bloques.*.tipo' => 'nullable|string|in:rir,rpe,fallo',
            'prescripcion.bloques.*.valor' => 'nullable|integer',
            'prescripcion.rest_pause' => 'nullable|array',
            'prescripcion.rest_pause.minisets' => 'nullable|array',
            'prescripcion.rest_pause.minisets.*.reps' => 'nullable|integer|min:0',
            'prescripcion.rest_pause.minisets.*.descanso_seg' => 'nullable|integer|min:0',
            'prescripcion.rest_pause.minisets.*.tipo' => 'nullable|string|in:rir,rpe,fallo',
            'prescripcion.rest_pause.minisets.*.valor' => 'nullable|integer',
            'prescripcion.progresion' => 'nullable|string|max:500',
            'prescripcion.consejo' => 'nullable|string|max:500',
            'prescripcion.etiqueta' => 'nullable|string|max:64',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'dia' => 'required|string',
            'ejercicio_nombre' => 'required|string',
            'series' => 'required|integer',
            'reps_min' => 'required|string',
            'reps_max' => 'required|string',
            'descanso_min' => 'required|numeric',
            'orden' => 'required|integer',
            'superserie_grupo' => 'nullable|integer',
            // Notas libres para prescripción (RIR/RPE/FALLO blocks + técnicas).
            'notas' => 'nullable|string|max:1000',
            // Prescripción estructurada (bloques, rest-pause, progresión).
            ...$this->reglasPrescripcion(),
        ]);

        // La prescripción se guarda tal cual la mandó el cliente (la forma ya
        // quedó validada arriba): el validador anidado recorta las claves
        // vacías y no queremos perder parte del payload.
        $prescripcion = $request->input('prescripcion');
        $data['prescripcion'] = is_array($prescripcion)
            ? $prescripcion
            : RutinaPrescripcionService::fromNotas($data['notas'] ?? null);

        $data['created_by'] = $request->user()->id;

        $rutina = Rutina::create($data);

        return response()->json($rutina, 201);
    }

    /**
     * Actualiza un ejercicio (fila de rutinas) del usuario.
     *
     * PUT /api/rutinas/{rutina}
     *
     * Payload parcial: sólo se aplican los campos enviados. `prescripcion`
     * es opcional; si viene reemplaza completa la prescripción estructurada
     * (mandarla en null la borra).
     */
    public function update(Request $request, Rutina $rutina)
    {
        $user = $request->user();

        // Sólo el creador de la rutina (o un admin) puede editarla.
        if ($rutina->created_by !== null
            && $rutina->created_by !== $user->id
            && ! $user->hasRole(User::ROLE_ADMINISTRADOR)) {
            return response()->json(['error' => 'No autorizado a modificar esta rutina'], 403);
        }

        $data = $request->validate([
            'nivel' => 'sometimes|required|string',
            'modalidad' => 'sometimes|required|string',
            'dia' => 'sometimes|required|string',
            'ejercicio_nombre' => 'sometimes|required|string',
            'series' => 'sometimes|required|integer',
            'reps_min' => 'sometimes|required|string',
            'reps_max' => 'sometimes|required|string',
            'descanso_min' => 'sometimes|required|numeric',
            'orden' => 'sometimes|required|integer',
            'superserie_grupo' => 'sometimes|nullable|integer',
            'notas' => 'sometimes|nullable|string|max:1000',
            ...$this->reglasPrescripcion(),
        ]);

        if ($request->has('prescripcion')) {
            $prescripcion = $request->input('prescripcion');
            $data['prescripcion'] = is_array($prescripcion) ? $prescripcion : null;
        } elseif (array_key_exists('notas', $data)) {
            // Si cambian las notas y NO viene una prescripción explícita, la
            // regeneramos para que no queden desfasadas (el front prioriza
            // `prescripcion` sobre `notas`).
            $data['prescripcion'] = RutinaPrescripcionService::fromNotas($data['notas']);
        }

        $rutina->fill($data);
        $rutina->save();

        return response()->json($rutina);
    }

    public function compartir(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string|in:Personalizada',
            'modalidad' => 'required|string',
        ]);

        // Token público del PLAN: todas las filas del plan (una por ejercicio)
        // comparten el MISMO token, porque la vista pública reagrupa por token
        // para reconstruir los días. Por eso `share_token` no tiene índice
        // unique (ver migración 2026_10_27_000000).
        //
        // El token sí tiene que ser único entre PLANES: si ya está en uso por
        // otra rutina se genera otro.
        do {
            $shareToken = bin2hex(random_bytes(8)); // 16 caracteres hex
        } while (Rutina::where('share_token', $shareToken)->exists());

        $affected = Rutina::where('created_by', $user->id)
            ->where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->update(['publica' => true, 'share_token' => $shareToken]);

        if ($affected === 0) {
            return response()->json(['error' => 'Rutina no encontrada o no pertenece a este usuario.'], 404);
        }

        // Check if creator achievements are unlocked
        $newMedals = AchievementService::checkRoutineMilestones($user);

        return response()->json([
            'message' => 'Rutina compartida con la comunidad con éxito.',
            'new_medals' => $newMedals,
            'public_url' => route('rutina.publica', ['token' => $shareToken]),
            'share_token' => $shareToken,
        ]);
    }

    /**
     * Vista pública de una rutina compartida (sin auth).
     * Devuelve JSON para que la cargue el componente Vue de la vista rutina-publica.
     */
    public function verPublica($token)
    {
        $rutinas = Rutina::where('share_token', $token)
            ->where('publica', true)
            ->orderBy('dia')
            ->orderBy('orden')
            ->get();

        if ($rutinas->isEmpty()) {
            return response()->json(['error' => 'Rutina no encontrada o no es pública.'], 404);
        }

        $creador = User::find($rutinas->first()->created_by);
        $meta = $rutinas->first();

        // Agrupar por día
        $porDia = $rutinas->groupBy('dia')->map(function ($rows) {
            return $rows->map(function ($r) {
                return [
                    'id' => $r->id,
                    'ejercicio_nombre' => $r->ejercicio_nombre,
                    'series' => $r->series,
                    'reps_min' => $r->reps_min,
                    'reps_max' => $r->reps_max,
                    'descanso_min' => $r->descanso_min,
                    'superserie_grupo' => $r->superserie_grupo,
                    'prescripcion' => $r->prescripcion,
                ];
            })->values();
        });

        return response()->json([
            'nivel' => $meta->nivel,
            'modalidad' => $meta->modalidad,
            'creador' => $creador ? ['name' => $creador->name, 'nick' => $creador->nick] : null,
            'dias' => $porDia,
        ]);
    }

    public function importar(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            // Nullable: las rutinas del sistema tienen created_by=null.
            // Si viene null matcheamos por IS NULL; si viene entero matcheamos exacto.
            'created_by' => 'nullable|integer',
            // Opcional: si viene, importa solo los ejercicios de ese dia. Si no
            // viene, importa todos los dias de la rutina (modalidad + nivel).
            'dia' => 'nullable|string',
        ]);

        $query = Rutina::where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->where('publica', true);

        if (is_null($data['created_by'])) {
            $query->whereNull('created_by');
        } else {
            $query->where('created_by', $data['created_by']);
        }

        if (! empty($data['dia'])) {
            $query->where('dia', $data['dia']);
        }

        $rutinasToImport = $query->get();

        if ($rutinasToImport->isEmpty()) {
            return response()->json(['error' => 'La rutina seleccionada no está disponible para importar.'], 404);
        }

        // Resolve name collision. Si el front filtro por dia, lo incluimos en
        // el nombre para distinguir "3 Dias - Dia 1" del full "3 Dias".
        $importedName = ! empty($data['dia'])
            ? $data['modalidad'].' - '.$data['dia']
            : $data['modalidad'];

        $exists = Rutina::where('created_by', $user->id)
            ->where('nivel', 'Personalizada')
            ->where('modalidad', $importedName)
            ->exists();

        if ($exists) {
            $importedName = $importedName.' (Importada)';
            $counter = 1;
            while (Rutina::where('created_by', $user->id)->where('nivel', 'Personalizada')->where('modalidad', $importedName)->exists()) {
                $importedName = preg_replace('/( \(Importada\)( \d+)?)?$/', '', $importedName).' (Importada) '.$counter;
                $counter++;
            }
        }

        // Copy each exercise
        foreach ($rutinasToImport as $rutina) {
            Rutina::create([
                'nivel' => 'Personalizada',
                'modalidad' => $importedName,
                'dia' => $rutina->dia,
                'created_by' => $user->id,
                'series' => $rutina->series,
                'reps_min' => $rutina->reps_min,
                'reps_max' => $rutina->reps_max,
                'descanso_min' => $rutina->descanso_min,
                'ejercicio_nombre' => $rutina->ejercicio_nombre,
                'orden' => $rutina->orden,
                'superserie_grupo' => $rutina->superserie_grupo,
                'notas' => $rutina->notas,
                // Si el origen no tiene prescripción estructurada, la derivamos
                // de sus notas para que la copia muestre el mismo plan.
                'prescripcion' => $rutina->prescripcion ?: RutinaPrescripcionService::fromNotas($rutina->notas),
                'publica' => false, // imported copy is private
            ]);
        }

        return response()->json([
            'message' => 'Rutina importada con éxito.',
            'modalidad' => $importedName,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nivel' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'string', 'max:255'],
            // Alcance del borrado.
            //   'propias'  -> rutinas creadas por quien pide el borrado (default, seguro)
            //   'catalogo' -> catálogo oficial (created_by IS NULL). Solo para admin.
            //
            // Antes el filtro `created_by` se aplicaba ÚNICAMENTE a los no
            // admin: un admin borraba por (nivel, modalidad) sin filtro y se
            // carried el catálogo del sistema entero, desasignándolo de todos
            // los alumnos. Ahora el alcance hay que pedirlo explícitamente.
            'scope' => ['nullable', 'string', 'in:propias,catalogo'],
        ]);

        $esAdmin = $user && $user->hasRole(User::ROLE_ADMINISTRADOR);
        $scope = $data['scope'] ?? 'propias';

        // Un admin que pide 'catalogo' sin autorización explícita no puede:
        // 'catalogo' solo aplica si el usuario es admin.
        if ($scope === 'catalogo' && ! $esAdmin) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado para eliminar rutinas del catálogo.',
            ], 403);
        }

        $resultado = \DB::transaction(function () use ($data, $user, $esAdmin, $scope) {
            $query = Rutina::query()
                ->where('nivel', $data['nivel'])
                ->where('modalidad', $data['modalidad']);

            if ($esAdmin && $scope === 'catalogo') {
                $query->whereNull('created_by');
            } else {
                // Usuario normal, o admin con scope 'propias'.
                $query->where('created_by', $user->id);
            }

            $routineIds = $query->pluck('id');

            $asignacionesEliminadas = 0;
            if ($routineIds->isNotEmpty()) {
                $asignacionesEliminadas = UserRutina::whereIn('rutina_id', $routineIds)->delete();
            }

            $eliminadas = $query->delete();

            return [
                'eliminadas' => $eliminadas,
                'asignaciones_eliminadas' => $asignacionesEliminadas,
            ];
        });

        if ($esAdmin && $scope === 'catalogo') {
            \Log::warning('[rutinas] Borrado de catálogo', [
                'admin_id' => $user->id,
                'nivel' => $data['nivel'],
                'modalidad' => $data['modalidad'],
                'eliminadas' => $resultado['eliminadas'],
                'asignaciones_eliminadas' => $resultado['asignaciones_eliminadas'],
            ]);
        }

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Rutina eliminada correctamente.',
        ], $resultado));
    }

    /**
     * Sugiere rutinas al user basándose en su historial de entrenamiento.
     * Usa el service de reglas (no ML) que analiza:
     *   - Grupos musculares más trabajados
     *   - Frecuencia de entrenamiento
     *   - Nivel estimado
     *   - Popularidad de la rutina
     */
    public function sugeridas(Request $request, RutinasSugeridasService $service)
    {
        $user = $request->user();
        $topN = (int) $request->input('limit', 5);
        $topN = max(1, min(10, $topN));

        $sugeridas = $service->sugerirPara($user, $topN);

        $payload = $sugeridas->map(function ($item) {
            $rutina = $item['rutina'];

            return [
                'id' => $rutina->id,
                'nivel' => $rutina->nivel,
                'modalidad' => $rutina->modalidad,
                'ejercicios_count' => $rutina->getAttribute('ejercicios_count'),
                'grupos_cubiertos' => $rutina->getAttribute('grupos_cubiertos'),
                'score' => round($item['score'], 1),
                'razones' => $item['razones'],
            ];
        });

        return response()->json([
            'sugeridas' => $payload,
            'perfil' => $service->analizarPerfil($user),
        ]);
    }

    /**
     * Toggle favorita del user actual sobre una MODALIDAD (nivel + modalidad).
     * Si ya hay alguna rutina favorita de esa modalidad, las desmarca todas.
     * Si no, marca todas las rutinas de esa modalidad como favoritas.
     *
     * POST /api/rutinas/favorite
     *   body: { nivel: string, modalidad: string, rutina_id?: int (para
     *   resolver el grupo; opcional si ya conocés nivel+modalidad) }
     */
    public function toggleFavorite(Request $request)
    {
        $data = $request->validate([
            'nivel' => 'required|string|max:255',
            'modalidad' => 'required|string|max:255',
        ]);

        $userId = $request->user()->id;

        // Traer todas las rutinas de esta modalidad
        $rutinasGrupo = Rutina::where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->get();

        if ($rutinasGrupo->isEmpty()) {
            return response()->json(['error' => 'Rutina no encontrada'], 404);
        }

        $grupoIds = $rutinasGrupo->pluck('id')->all();

        // ¿Alguna ya es favorita del user?
        $existing = RutinaFavorita::where('user_id', $userId)
            ->whereIn('rutina_id', $grupoIds)
            ->exists();

        if ($existing) {
            RutinaFavorita::where('user_id', $userId)
                ->whereIn('rutina_id', $grupoIds)
                ->delete();

            return response()->json(['is_favorita' => false]);
        }

        // Crear favoritas para todas las rutinas del grupo
        $now = now();
        $rows = array_map(fn ($id) => [
            'user_id' => $userId,
            'rutina_id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $grupoIds);
        RutinaFavorita::insert($rows);

        return response()->json(['is_favorita' => true]);
    }

    /**
     * Devuelve las rutinas favoritas del user actual, agrupadas por modalidad.
     * GET /api/rutinas/favoritas
     */
    public function favoritas(Request $request)
    {
        $userId = $request->user()->id;

        $favoritasIds = RutinaFavorita::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->pluck('rutina_id');

        if ($favoritasIds->isEmpty()) {
            return response()->json([]);
        }

        $rutinas = Rutina::whereIn('id', $favoritasIds)
            ->with(['ejercicio', 'creador'])
            ->orderBy('orden')
            ->get();

        $this->attachFavoritaFlag($rutinas, $request->user());

        return response()->json($rutinas);
    }

    /**
     * Duplica todos los ejercicios de un día a otro día dentro de la misma rutina.
     * POST /api/rutinas/duplicar-dia
     */
    public function duplicarDia(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'dia_origen' => 'required|string',
            'dia_destino' => 'required|string',
        ]);

        $ejerciciosOrigen = Rutina::where('nivel', $data['nivel'])
            ->where('modalidad', $data['modalidad'])
            ->where('dia', $data['dia_origen'])
            ->where(function ($q) use ($user) {
                $q->whereNull('created_by')->orWhere('created_by', $user->id);
            })
            ->orderBy('orden')
            ->get();

        if ($ejerciciosOrigen->isEmpty()) {
            return response()->json(['error' => 'No se encontraron ejercicios en el día de origen'], 404);
        }

        $nuevos = [];
        foreach ($ejerciciosOrigen as $ej) {
            $nuevo = Rutina::create([
                'nivel' => $ej->nivel,
                'modalidad' => $ej->modalidad,
                'dia' => $data['dia_destino'],
                'ejercicio_nombre' => $ej->ejercicio_nombre,
                'ejercicio_id' => $ej->ejercicio_id,
                'series' => $ej->series,
                'reps_min' => $ej->reps_min,
                'reps_max' => $ej->reps_max,
                'descanso_min' => $ej->descanso_min,
                'orden' => $ej->orden,
                'superserie_grupo' => $ej->superserie_grupo,
                'notas' => $ej->notas,
                'prescripcion' => $ej->prescripcion ?: RutinaPrescripcionService::fromNotas($ej->notas),
                'created_by' => $user->id,
            ]);
            $nuevos[] = $nuevo;
        }

        return response()->json([
            'message' => 'Día duplicado con éxito',
            'count' => count($nuevos),
            'ejercicios' => $nuevos,
        ], 201);
    }

    /**
     * Reordena ejercicios en batch (drag & drop). Nivel 6.
     *
     * Body: { nivel, modalidad, dia, items: [{ id, orden }, ...] }
     * Todos los items deben pertenecer al mismo (nivel, modalidad, dia).
     *
     * Validamos que el user sea el creador de los ejercicios (o admin)
     * para evitar que un alumno reordene rutinas default del gimnasio.
     */
    public function reorder(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'nivel' => 'required|string',
            'modalidad' => 'required|string',
            'dia' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:rutinas,id',
            'items.*.orden' => 'required|integer|min:0',
        ]);

        $ids = collect($data['items'])->pluck('id')->all();

        $ejercicios = Rutina::whereIn('id', $ids)->get();

        // Verificar que todos los items son del mismo (nivel, modalidad, dia)
        // y que el user es el creador (o admin).
        foreach ($ejercicios as $ej) {
            if ($ej->nivel !== $data['nivel']
                || $ej->modalidad !== $data['modalidad']
                || $ej->dia !== $data['dia']) {
                return response()->json([
                    'error' => 'Los ejercicios no pertenecen al mismo día/rutina',
                ], 422);
            }
            if (! $user->hasRole(User::ROLE_ADMINISTRADOR)
                && $ej->created_by !== null
                && $ej->created_by !== $user->id) {
                return response()->json([
                    'error' => 'No podés reordenar ejercicios de otra rutina',
                ], 403);
            }
        }

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                Rutina::where('id', $item['id'])->update(['orden' => $item['orden']]);
            }
        });

        return response()->json(['ok' => true, 'count' => count($data['items'])]);
    }
}
