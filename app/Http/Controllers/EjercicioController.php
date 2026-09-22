<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ejercicio;
use App\Models\EjercicioFavorito;
use App\Models\Historial;
use App\Models\Musculo;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    public function index(Request $request)
    {
        // Las rutas /ejercicios, /ejercicios/grupos-musculares y
        // /ejercicios/equipamientos son públicas (sin middleware de auth),
        // por lo que no se invoca authorize() — la policy viewAny/view es
        // return true y los tests existentes llaman sin autenticar.

        $userId = $request->user()?->id;

        $query = Ejercicio::query()
            // Cargar músculos para que el body map en /ejercicios pueda
            // iluminar las partes trabajadas al seleccionar un ejercicio.
            // Select acotado para no inflar el response.
            ->with(['musculos:id,slug,nombre_es']);

        if ($request->has('busqueda') && $request->busqueda) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', '%'.$busqueda.'%')
                    ->orWhere('equipamiento', 'like', '%'.$busqueda.'%');
            });
        }

        if ($request->has('grupo_muscular') && $request->grupo_muscular) {
            $query->where('grupo_muscular', $request->grupo_muscular);
        }

        if ($request->has('equipamiento') && $request->equipamiento) {
            $query->where('equipamiento', $request->equipamiento);
        }

        if ($request->has('dificultad') && $request->dificultad) {
            $query->where('dificultad', $request->dificultad);
        }

        // Filtro por músculo (usado cuando el usuario hace click en una
        // parte del body map). Filtra por la pivot ejercicio_musculos.
        if ($request->has('musculo_slug') && $request->musculo_slug) {
            $slug = $request->musculo_slug;
            $query->whereHas('musculos', function ($q) use ($slug) {
                $q->where('musculos.slug', $slug);
            });
        }

        // Si hay user autenticado, agregamos last_trained_at + is_favorite
        // con subqueries (evita N+1). Para rutas públicas devuelve null/false.
        if ($userId) {
            $lastTrainedSub = \DB::table('historials')
                ->select('ejercicio_id', \DB::raw('MAX(fecha) as last_trained_at'))
                ->where('user_id', $userId)
                ->where('completado', true)
                ->whereNotNull('ejercicio_id')
                ->groupBy('ejercicio_id');

            $favoriteSub = \DB::table('ejercicio_favoritos')
                ->select('ejercicio_id')
                ->where('user_id', $userId);

            $query->addSelect([
                'ejercicios.*',
                'last_trained_at' => \DB::table('historials')
                    ->select('fecha')
                    ->whereColumn('ejercicio_id', 'ejercicios.id')
                    ->where('user_id', $userId)
                    ->where('completado', true)
                    ->orderByDesc('fecha')
                    ->limit(1),
                'is_favorite' => \DB::table('ejercicio_favoritos')
                    ->selectRaw('1')
                    ->whereColumn('ejercicio_id', 'ejercicios.id')
                    ->where('user_id', $userId)
                    ->limit(1),
            ]);
        }

        if ($request->input('orden') === 'desc') {
            $query->orderByDesc('nombre');
        } elseif ($request->input('orden') === 'asc') {
            $query->orderBy('nombre', 'asc');
        }

        $ejercicios = $query->paginate(20);

        // Convertir is_favorite a boolean (Laravel lo deja como null/1)
        if ($userId) {
            $ejercicios->getCollection()->transform(function ($ej) {
                $ej->is_favorite = (bool) $ej->is_favorite;

                return $ej;
            });
        }

        return response()->json($ejercicios);
    }

    public function gruposMusculares()
    {
        $grupos = Ejercicio::whereNotNull('grupo_muscular')
            ->where('grupo_muscular', '!=', '')
            ->distinct()
            ->orderBy('grupo_muscular')
            ->pluck('grupo_muscular');

        return response()->json($grupos);
    }

    public function equipamientos()
    {
        $equipamientos = Ejercicio::whereNotNull('equipamiento')
            ->where('equipamiento', '!=', '')
            ->distinct()
            ->orderBy('equipamiento')
            ->pluck('equipamiento');

        return response()->json($equipamientos);
    }

    /**
     * Catálogo de músculos canónicos (slug → nombre_es).
     * Usado por la página de Ejercicios para los tooltips del body map.
     * Es estático y seguro de cachear.
     */
    public function musculos()
    {
        $musculos = Musculo::orderBy('orden')
            ->get(['id', 'slug', 'nombre_es', 'nombre_en', 'body_part', 'orden'])
            ->map(fn ($m) => [
                'slug' => $m->slug,
                'nombre_es' => $m->nombre_es,
                'nombre_en' => $m->nombre_en,
                'body_part' => $m->body_part,
                'orden' => (int) $m->orden,
            ]);

        return response()->json($musculos);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ejercicio::class);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'equipamiento' => 'required|string|max:255',
            'url_img' => 'nullable|string',
            'url_video' => 'nullable|string',
            'visibilidad' => 'boolean',
            'grupo_muscular' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'dificultad' => 'nullable|string|in:principiante,intermedio,avanzado',
        ]);

        $ejercicio = Ejercicio::create($data);

        AuditLog::forModel($ejercicio, 'created', null, $data);

        return response()->json($ejercicio, 201);
    }

    public function destroy($id)
    {
        $ejercicio = Ejercicio::findOrFail($id);

        $this->authorize('delete', $ejercicio);

        $ejercicioData = $ejercicio->toArray();
        $ejercicio->delete();

        AuditLog::log('deleted', "Eliminó ejercicio {$ejercicioData['nombre']}", auth()->id(), Ejercicio::class, $id, $ejercicioData, null);

        return response()->json(['message' => 'Eliminado']);
    }

    /**
     * Toggle favorito del user actual sobre un ejercicio.
     * POST /api/ejercicios/{id}/favorite → si no existe lo crea, si existe lo borra.
     */
    public function toggleFavorite(Request $request, $id)
    {
        $ejercicio = Ejercicio::findOrFail($id);
        $userId = $request->user()->id;

        $favorito = EjercicioFavorito::where('user_id', $userId)
            ->where('ejercicio_id', $ejercicio->id)
            ->first();

        if ($favorito) {
            $favorito->delete();

            return response()->json(['is_favorite' => false]);
        }

        EjercicioFavorito::create([
            'user_id' => $userId,
            'ejercicio_id' => $ejercicio->id,
        ]);

        return response()->json(['is_favorite' => true]);
    }

    /**
     * Quick log: crea un historial rapido para HOY con 1 set vacio
     * (peso=0, reps=0, completado=true). Sirve para que el ejercicio
     * aparezca como 'Hoy' en la lista y se sume al body map, sin tener
     * que pasar por el flujo completo de series del dashboard.
     *
     * El user despues puede ir al dashboard y editar los sets reales.
     */
    public function quickLog(Request $request, $id)
    {
        $ejercicio = Ejercicio::findOrFail($id);
        $userId = $request->user()->id;

        // Mapear día de la semana al string corto que usa la tabla
        $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $hoy = $dias[now()->dayOfWeekIso - 1]; // 1=lunes, 7=domingo

        $historial = Historial::create([
            'user_id' => $userId,
            'ejercicio_id' => $ejercicio->id,
            'ejercicio_nombre' => $ejercicio->nombre,
            'rutina_nombre' => 'Quick log',
            'dia' => $hoy,
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '0',
            'reps_max' => '0',
            'reps_realizadas' => 0,
            'peso' => 0,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        return response()->json([
            'ok' => true,
            'historial_id' => $historial->id,
        ]);
    }

    // =====================================================================
    // === Endpoints del catálogo VisualGym (exercises-dataset) =============
    // =====================================================================
    //
    // Estos endpoints exponen los 1.324 ejercicios del dataset VisualGym.
    // Son read-only y públicos (no requieren auth), igual que /ejercicios.
    //
    //   GET /api/visualgym/facets
    //     Devuelve listas únicas para popular dropdowns de filtros:
    //       { body_parts: [...], targets: [...], equipamientos: [...] }
    //
    //   GET /api/visualgym/exercises
    //     ?page=1
    //     &per_page=24           (default 24, max 100)
    //     &busqueda=press        (search en nombre, target, equipamiento, grupo_muscular)
    //     &body_part=chest       (filtro exacto)
    //     &target=biceps         (filtro exacto)
    //     &equipamiento=dumbbell (filtro exacto, en inglés)
    //     &lang=es               (idioma de instrucciones aplanadas en el detalle)
    //
    //   GET /api/visualgym/exercises/{externalId}
    //     Devuelve un ejercicio por su external_id (ej "0025")
    //
    //   GET /api/visualgym/facets
    //     Devuelve listas únicas para popular dropdowns de filtros:
    //       { body_parts: [...], targets: [...], equipamientos: [...] }

    /**
     * Catálogo paginado de ejercicios VisualGym.
     */
    public function catalogVisualGym(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        $userId = $request->user()?->id;

        $query = Ejercicio::query()
            ->fromVisualGym()
            ->search($request->input('busqueda'))
            ->byBodyPart($request->input('body_part'))
            ->byTarget($request->input('target'))
            ->byEquipment($request->input('equipamiento'))
            ->orderBy('nombre');

        // Si hay user autenticado, agregamos is_favorite con subquery
        // (evita N+1; el toggleFavorite endpoint sigue funcionando).
        if ($userId) {
            $query->addSelect([
                'ejercicios.*',
                'is_favorite' => \DB::table('ejercicio_favoritos')
                    ->selectRaw('1')
                    ->whereColumn('ejercicio_id', 'ejercicios.id')
                    ->where('user_id', $userId)
                    ->limit(1),
            ]);
        }

        $page = $query->paginate($perPage);

        // Traducir campos EN → ES y normalizar is_favorite a bool.
        $page->getCollection()->transform(function ($ej) use ($userId) {
            $ej->body_part_es = \App\Support\VisualGymI18n::translate('body_part', $ej->body_part);
            $ej->target_es = \App\Support\VisualGymI18n::translate('target', $ej->target);
            $ej->equipamiento_es = \App\Support\VisualGymI18n::translate('equipamiento', $ej->equipamiento);
            if ($userId) {
                $ej->is_favorite = (bool) $ej->is_favorite;
            }
            return $ej;
        });

        return response()->json($page);
    }

    /**
     * Detalle de un ejercicio VisualGym por external_id.
     * Aplana las instrucciones al idioma pedido (default 'es') en un
     * campo top-level `instruction` para no obligar al front a navegar
     * el JSON de 10 idiomas.
     */
    public function showVisualGym(Request $request, string $externalId)
    {
        $lang = $request->input('lang', 'es');

        $ej = Ejercicio::query()
            ->fromVisualGym()
            ->where('external_id', $externalId)
            ->first();

        if (! $ej) {
            return response()->json(['message' => 'Ejercicio no encontrado'], 404);
        }

        $data = $ej->toArray();
        // Atributos computados del modelo:
        $data['image_url'] = $ej->image_url;
        $data['gif_url_full'] = $ej->gif_url;
        // Traducción ES:
        $data['body_part_es'] = \App\Support\VisualGymI18n::translate('body_part', $ej->body_part);
        $data['target_es'] = \App\Support\VisualGymI18n::translate('target', $ej->target);
        $data['equipamiento_es'] = \App\Support\VisualGymI18n::translate('equipamiento', $ej->equipamiento);
        // is_favorite si hay user:
        if ($request->user()) {
            $data['is_favorite'] = $ej->favoritos()
                ->where('users.id', $request->user()->id)
                ->exists();
        } else {
            $data['is_favorite'] = false;
        }
        // Instrucción aplanada al idioma pedido:
        $data['instruction'] = $ej->getInstruction($lang);
        $data['instruction_steps'] = $ej->instruction_steps[$lang]
            ?? $ej->instruction_steps['en']
            ?? $ej->instruction_steps['es']
            ?? [];
        $data['lang'] = $lang;
        // Idiomas disponibles:
        $data['available_languages'] = is_array($ej->instructions)
            ? array_keys($ej->instructions)
            : [];

        return response()->json($data);
    }

    /**
     * Facetas para los filtros del catálogo: listas únicas de
     * body_part, target y equipamiento dentro del dataset VisualGym,
     * junto con el mapping EN → ES para popular dropdowns en español.
     */
    public function facetsVisualGym()
    {
        $bodyParts = Ejercicio::query()
            ->fromVisualGym()
            ->whereNotNull('body_part')
            ->where('body_part', '!=', '')
            ->distinct()
            ->orderBy('body_part')
            ->pluck('body_part');

        $targets = Ejercicio::query()
            ->fromVisualGym()
            ->whereNotNull('target')
            ->where('target', '!=', '')
            ->distinct()
            ->orderBy('target')
            ->pluck('target');

        $equipamientos = Ejercicio::query()
            ->fromVisualGym()
            ->whereNotNull('equipamiento')
            ->where('equipamiento', '!=', '')
            ->distinct()
            ->orderBy('equipamiento')
            ->pluck('equipamiento');

        // Estructura nueva: cada facet es array de {value, label_es}.
        // value = el string inglés (es el que se manda de vuelta como filtro).
        // label_es = el nombre legible en español para el dropdown.
        $map = function (string $field, $values) {
            $out = [];
            foreach ($values as $v) {
                $out[] = [
                    'value' => $v,
                    'label_es' => \App\Support\VisualGymI18n::translate($field, $v),
                ];
            }
            return $out;
        };

        return response()->json([
            'body_parts' => $map('body_part', $bodyParts),
            'targets' => $map('target', $targets),
            'equipamientos' => $map('equipamiento', $equipamientos),
        ]);
    }

    /**
     * Devuelve info visual (image_url / gif_url) de un ejercicio por nombre.
     *
     * Se usa desde el modal de entrenamiento activo para mostrar la animación
     * del ejercicio actual mientras el usuario hace las series.
     *
     * Estrategia de matching (en orden):
     *   1. Match EXACTO VisualGym (source=visualgym) — caso ideal
     *   2. Match EXACTO en legacy (sin imagen ni GIF)
     *   3. Alias manual (NAME_ALIASES): legacy normalizado → VisualGym exacto
     *   4. Alias parcial: substring progresivo del query en NAME_ALIASES
     *   5. Fuzzy match con sinónimos como último recurso
     */
    public function ejercicioMediaByName(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return response()->json(['message' => 'Falta el parámetro name'], 422);
        }

        $normalized = self::normalize($name);

        // 1) Match exacto en VisualGym (caso ideal)
        $ej = Ejercicio::query()
            ->fromVisualGym()
            ->where('nombre', $name)
            ->first();
        if ($ej) {
            return $this->mediaResponse($ej);
        }

        // 2) Alias manual o alias parcial → VisualGym exacto
        // (Va ANTES del legacy para que un alias "X => visualgym" gane sobre
        // un legacy "X" que tenga nombre idéntico.)
        $visualGymMatch = $this->matchByAliases($normalized);
        if ($visualGymMatch) {
            return $this->mediaResponse($visualGymMatch);
        }

        // 3) Match exacto en legacy (sin media de VisualGym)
        $legacy = Ejercicio::query()
            ->where('nombre', $name)
            ->first();
        if ($legacy) {
            return $this->mediaResponse($legacy);
        }

        // 4) Fuzzy match como último recurso
        $fuzzy = $this->fuzzyMatchVisualGymByWords($name);
        if ($fuzzy) {
            return $this->mediaResponse($fuzzy);
        }

        return response()->json(['message' => 'Ejercicio no encontrado'], 404);
    }

    /**
     * Busca en NAME_ALIASES primero exacto, luego substrings progresivos
     * del query (del más largo al más corto).
     *
     * Estrategia:
     *  1. Match normalizado: lowercase, sin acentos, sin signos.
     *     Esto es lo principal — los aliases ya están normalizados.
     *  2. Match parcial: substrings progresivos del más largo al más corto.
     */
    private function matchByAliases(string $normalized): ?Ejercicio
    {
        // 1) Match normalizado
        if (isset(self::NAME_ALIASES[$normalized])) {
            return $this->findVisualGymByName(self::NAME_ALIASES[$normalized]);
        }

        // 2) Parcial: substrings progresivos del más largo al más corto.
        // "press de banca plano pesado" → probar primero
        //   "press de banca plano pesado" (no)
        //   "press de banca plano"     (no)
        //   "press de banca"           (SÍ!)
        $words = explode(' ', $normalized);
        for ($len = count($words); $len >= 2; $len--) {
            $candidate = implode(' ', array_slice($words, 0, $len));
            if (isset(self::NAME_ALIASES[$candidate])) {
                return $this->findVisualGymByName(self::NAME_ALIASES[$candidate]);
            }
        }

        return null;
    }

    /**
     * Busca un VisualGym por nombre exacto.
     */
    private function findVisualGymByName(string $name): ?Ejercicio
    {
        return Ejercicio::query()
            ->fromVisualGym()
            ->where('nombre', $name)
            ->first();
    }

    /**
     * Matching difuso puro por intersección de palabras (con sinónimos).
     * Se usa solo como fallback cuando no hay alias manual.
     */
    private function fuzzyMatchVisualGymByWords(string $name): ?Ejercicio
    {
        $queryWordsOriginal = self::significantWords(self::normalize($name));
        $queryWords = self::expandSynonyms($queryWordsOriginal);
        if (count($queryWords) === 0) {
            return null;
        }

        $equipmentFilter = self::extractEquipmentFilter($queryWordsOriginal);

        $query = Ejercicio::query()->fromVisualGym();
        if ($equipmentFilter !== null) {
            $query->where('equipamiento', $equipmentFilter);
        }
        $candidates = $query->get(['id', 'nombre', 'image_path', 'gif_path', 'source', 'equipamiento']);

        if ($candidates->isEmpty()) {
            $candidates = Ejercicio::query()
                ->fromVisualGym()
                ->get(['id', 'nombre', 'image_path', 'gif_path', 'source', 'equipamiento']);
        }

        $best = null;
        $bestScore = 0.0;
        $bestLen = PHP_INT_MAX;

        foreach ($candidates as $c) {
            $candWords = self::expandSynonyms(self::significantWords(self::normalize($c->nombre)));
            $intersection = array_intersect($queryWords, $candWords);
            $score = count($intersection) / max(count($queryWords), count($candWords));
            $len = mb_strlen($c->nombre);

            if (
                $score > $bestScore
                || (abs($score - $bestScore) < 0.001 && $score > 0 && $len < $bestLen)
            ) {
                $bestScore = $score;
                $bestLen = $len;
                $best = $c;
            }
        }

        $minScore = count($queryWordsOriginal) === 1 ? 0.25 : 0.30;

        return $bestScore >= $minScore ? $best : null;
    }

    /**
     * Normaliza un string: lowercase, sin acentos, espacios colapsados.
     * Preserva letras, números, espacios, '/' (separador) y '()' (paréntesis
     * descriptivos como "Buenos días (Good Mornings)") — los aliases del
     * usuario los incluyen textualmente.
     */
    public static function normalize(string $s): string
    {
        $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
        $s = preg_replace('/[^a-z0-9\s\/()]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    /**
     * Palabras significativas de un nombre normalizado.
     * Saca stopwords (ES + EN) y palabras de <=2 chars.
     */
    public static function significantWords(string $normalized): array
    {
        $stopwords = [
            'de', 'el', 'la', 'los', 'las', 'con', 'sin', 'para', 'por', 'un', 'una',
            'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'at', 'del',
        ];
        $words = explode(' ', $normalized);
        $significant = array_filter(
            $words,
            fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, $stopwords, true),
        );

        return array_values($significant);
    }

    /**
     * Sinónimos ES↔EN para vocabulario de fitness. Permite que "banca" matchee
     * con "bench", "sentadilla" con "squat", etc.
     */
    private const SYNONYMS = [
        'banca' => ['bench'],
        'sentadilla' => ['squat'],
        'peso' => ['weight', 'dead'],
        'muerto' => ['dead', 'deadlift'],
        'remo' => ['row'],
        'dominada' => ['pull', 'chin'],
        'dominadas' => ['pull', 'chin'],
        'jalon' => ['pulldown', 'pull'],
        'extension' => ['ext'],
        'elevacion' => ['raise', 'fly', 'lift'],
        'fondo' => ['dip'],
        'fondos' => ['dip', 'dips'],
        'abdominal' => ['crunch', 'sit'],
        'abdominales' => ['crunch', 'sit'],
        'mancuerna' => ['dumbbell'],
        'mancuernas' => ['dumbbell'],
        'barra' => ['barbell', 'bar'],
        'maquina' => ['machine'],
        'polea' => ['cable'],
        'banda' => ['band'],
        'isquios' => ['hamstring'],
        'isquiotibial' => ['hamstring'],
        'isquiotibiales' => ['hamstring'],
        'femoral' => ['hamstring'],
        'femorales' => ['hamstring'],
        'cuadri' => ['quad'],
        'cuadriceps' => ['quad'],
        'gluteo' => ['glute'],
        'gluteos' => ['glute'],
        'gemelo' => ['calf'],
        'gemelos' => ['calf'],
        'pantorrilla' => ['calf'],
        'pantorrillas' => ['calf'],
        'hombro' => ['shoulder', 'delt'],
        'hombros' => ['shoulder', 'delt'],
        'deltoides' => ['delt'],
        'pecho' => ['chest', 'pec'],
        'pectoral' => ['chest', 'pec'],
        'pectorales' => ['chest', 'pec'],
        'espalda' => ['back', 'lat'],
        'dorsal' => ['lat', 'back'],
        'dorsales' => ['lat', 'back'],
        'pierna' => ['leg'],
        'piernas' => ['leg'],
        'brazo' => ['arm'],
        'brazos' => ['arm'],
        'biceps' => ['bicep'],
        'triceps' => ['tricep'],
        'antebrazo' => ['forearm'],
        'antebrazos' => ['forearm'],
        'abdomen' => ['ab', 'abs'],
        'cintura' => ['waist'],
        'inclinado' => ['incline'],
        'inclinada' => ['incline'],
        'declinado' => ['decline'],
        'declinada' => ['decline'],
        'plano' => ['flat'],
        'plana' => ['flat'],
        'cargado' => ['loaded'],
        'pesado' => ['heavy'],
        'pesada' => ['heavy'],
        'agachamiento' => ['squat'],
        'zancada' => ['lunge'],
        'zancadas' => ['lunge'],
        'bulgara' => ['bulgarian'],
        'bulgares' => ['bulgarian'],
        'hack' => ['hack'],
        'flexion' => ['press'],
        'pullover' => ['pullover'],
        'patada' => ['kickback'],
        'patadas' => ['kickback'],
        'tiron' => ['pull'],
        'encogimiento' => ['shrug'],
        'encogimientos' => ['shrug'],
    ];

    /**
     * Expande una lista de palabras con sus sinónimos (ES↔EN).
     */
    public static function expandSynonyms(array $words): array
    {
        $expanded = $words;
        foreach ($words as $w) {
            if (isset(self::SYNONYMS[$w])) {
                $expanded = array_merge($expanded, self::SYNONYMS[$w]);
            }
            foreach (self::SYNONYMS as $key => $syns) {
                if (in_array($w, $syns, true)) {
                    $expanded[] = $key;
                    $expanded = array_merge($expanded, $syns);
                }
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * Si el query menciona un equipamiento, devuelve su equivalente en
     * VisualGym. Devuelve null si no se puede inferir.
     */
    private static function extractEquipmentFilter(array $queryWords): ?string
    {
        $eqMap = [
            'barra' => 'barbell',
            'mancuerna' => 'dumbbell',
            'mancuernas' => 'dumbbell',
            'maquina' => 'leverage machine',
            'polea' => 'cable',
            'banda' => 'band',
            'kettlebell' => 'kettlebell',
            'pesas' => 'dumbbell',
            'cuerpo' => 'body weight',
        ];

        foreach ($queryWords as $w) {
            if (isset($eqMap[$w])) {
                return $eqMap[$w];
            }
        }

        if (in_array('peso', $queryWords, true) && in_array('corporal', $queryWords, true)) {
            return 'body weight';
        }

        return null;
    }

    /**
     * Mapa manual de aliases legacy ES → VisualGym EN.
     *
     * Keys: nombre del ejercicio legacy normalizado (lowercase, sin acentos)
     * Values: nombre VisualGym exacto
     */
private const NAME_ALIASES = [

        'abduccion de cadera en polea' => 'cable standing hip extension',

        'aperturas / cruces en polea alta' => 'cable upper chest crossovers',

        'aperturas / cruces en polea baja' => 'cable low fly',

        'aperturas / cruces en polea media' => 'cable standing fly',

        'aperturas declinadas' => 'dumbbell decline fly',

        'aperturas inclinadas' => 'dumbbell incline fly',

        'aperturas planas (flyes)' => 'dumbbell fly',

        'bicicleta de spinning' => 'stationary bike walk',

        'bicicleta estatica' => 'stationary bike run v. 3',

        'box jumps' => 'box jump down with one leg stabilization',

        'buenos dias (good mornings)' => 'barbell good morning',

        'burpees' => 'burpee',

        'cinta de correr (treadmill)' => 'walking on incline treadmill',

        'clean and jerk' => 'barbell clean and press',

        'cruces de poleas' => 'cable cross-over variation',

        'crunch abdominal' => 'crunch floor',

        'crunch abdominal con polea' => 'cable kneeling crunch',

        'crunch abdominal de rodillas' => 'cable kneeling crunch',

        'crunch bicicleta' => 'air bike',

        'crunch con polea alta' => 'cable kneeling crunch',

        'crunch en maquina con peso' => 'lever seated crunch',

        'curl arana en banco inclinado' => 'dumbbell prone incline curl',

        'curl arana en banco inclinado (spider curl)' => 'ez barbell spider curl',

        'curl concentrado' => 'dumbbell concentration curl',

        'curl de biceps' => 'dumbbell biceps curl',

        'curl de biceps a una mano en polea' => 'cable one arm curl',

        'curl de biceps agarre inverso' => 'barbell reverse curl',

        'curl de biceps alterno' => 'dumbbell alternate biceps curl',

        'curl de biceps con barra z' => 'ez barbell curl',

        'curl de biceps doble en polea alta' => 'cable overhead curl',

        'curl de biceps en banco inclinado' => 'dumbbell incline biceps curl',

        'curl de biceps en polea baja' => 'cable curl',

        'curl de biceps estricto en pared' => 'barbell curl',

        'curl de biceps martillo con cuerda' => 'cable hammer curl (with rope)',

        'curl de biceps martillo cruzado' => 'dumbbell cross body hammer curl',

        'curl de biceps pesado' => 'barbell curl',

        'curl de biceps predicador' => 'ez barbell close grip preacher curl',

        'curl de isquiotibiales' => 'lever lying leg curl',

        'curl de isquiotibiales acostado' => 'lever lying leg curl',

        'curl de isquiotibiales sentado' => 'lever seated leg curl',

        'curl de isquiotibiales unilateral' => 'lever lying two-one leg curl',

        'curl de muneca en pronacion' => 'barbell palms down wrist curl over a bench',

        'curl de muneca en supinacion' => 'barbell palms up wrist curl over a bench',

        'curl femoral de pie unilateral' => 'lever kneeling leg curl',

        'curl martillo' => 'dumbbell hammer curl',

        'curl martillo para biceps' => 'dumbbell hammer curl',

        'curl predicador' => 'barbell preacher curl',

        'curl zottman' => 'dumbbell zottman curl',

        'dominadas' => 'pull-up',

        'dominadas lastradas' => 'weighted pull-up',

        'dominadas o jalon pesado' => 'pull-up',

        'dragon flags' => 'flag',

        'dragon flags o plancha lastrada' => 'weighted front plank',

        'elevacion de gemelos de pie pesada' => 'barbell standing calf raise',

        'elevacion de gemelos en prensa' => 'lever calf press',

        'elevacion de gemelos sentado' => 'lever seated calf raise',

        'elevacion de piernas acostado' => 'lying leg raise flat bench',

        'elevacion de piernas colgado' => 'hanging leg raise',

        'elevacion de piernas con giro' => 'hanging oblique knee raise',

        'elevacion de rodillas colgado' => 'hanging leg hip raise',

        'elevacion de talones a una pierna' => 'dumbbell single leg calf raise',

        'elevacion de talones de pie' => 'bodyweight standing calf raise',

        'elevacion de talones en prensa' => 'sled calf press on leg press',

        'elevacion de talones para pantorrillas' => 'lever seated calf raise',

        'elevacion de talones sentado' => 'lever seated calf raise',

        'elevaciones frontales' => 'barbell front raise',

        'elevaciones frontales alternas' => 'dumbbell seated alternate front raise',

        'elevaciones frontales con cuerda entre piernas' => 'cable front raise',

        'elevaciones frontales en polea' => 'cable front shoulder raise',

        'elevaciones laterales' => 'dumbbell lateral raise',

        'elevaciones laterales + drop set' => 'dumbbell lateral raise',

        'elevaciones laterales con polea' => 'cable lateral raise',

        'elevaciones laterales en polea' => 'cable one arm lateral raise',

        'elevaciones laterales pesadas' => 'dumbbell lateral raise',

        'elevaciones laterales sentado' => 'dumbbell seated lateral raise',

        'eliptica' => 'walk elliptical cross trainer',

        'encogimientos de hombros en polea' => 'cable shrug',

        'encogimientos de hombros para trapecio' => 'dumbbell shrug',

        'encogimientos de hombros por detras' => 'smith back shrug',

        'encogimientos de hombros rotativos' => 'dumbbell shrug',

        'escaladora' => 'walking on stepmill',

        'extension de cuadriceps' => 'lever leg extension',

        'extension de triceps' => 'cable pushdown',

        'extension de triceps a una mano (agarre supino)' => 'cable one arm tricep pushdown',

        'extension de triceps a una mano invertida' => 'cable one arm tricep pushdown',

        'extension de triceps con barra recta' => 'cable pushdown',

        'extension de triceps con cuerda' => 'cable pushdown (with rope attachment)',

        'extension de triceps copa a dos manos' => 'dumbbell standing bent over two arm triceps extension',

        'extension de triceps en el suelo (skullcrusher)' => 'barbell lying triceps extension skull crusher',

        'extension de triceps sobre la cabeza' => 'cable high pulley overhead tricep extension',

        'extension de triceps sobre la cabeza a una mano' => 'dumbbell one arm triceps extension (on bench)',

        'extension de triceps unilateral cruzada' => 'cable standing one arm triceps extension',

        'extensiones de cuadriceps pesadas' => 'lever leg extension',

        'extensiones de triceps sobre la cabeza' => 'cable overhead triceps extension (rope attachment)',

        'extensiones lumbares' => 'hyperextension',

        'extensiones lumbares a una pierna' => 'bench hip extension',

        'extensiones lumbares con disco' => 'weighted hyperextension (on stability ball)',

        'extensiones lumbares lastradas' => 'weighted hyperextension (on stability ball)',

        'face pull en polea' => 'cable rear delt row (with rope)',

        'face pulls' => 'cable rear delt row (with rope)',

        'flexion lateral del tronco' => 'dumbbell side bend',

        'flexiones de brazos' => 'push-up',

        'flexiones diamante' => 'diamond push-up',

        'fondos en paralelas' => 'chest dip',

        'fondos en paralelas lastrados' => 'weighted triceps dip on high parallel bars',

        'good mornings' => 'barbell good morning',

        'guillotine press (press al cuello)' => 'barbell guillotine bench press',

        'hip thrust' => 'barbell glute bridge',

        'hip thrust a una pierna' => 'single leg bridge with outstretched leg',

        'hip thrust con pausa' => 'barbell glute bridge',

        'hip thrust o puente de gluteo' => 'glute bridge two legs on bench (male)',

        'hip thrust pesado' => 'barbell glute bridge',

        'jalon al pecho' => 'cable pulldown',

        'jalon al pecho a una mano' => 'cable one arm pulldown',

        'jalon al pecho agarre estrecho' => 'cable pulldown (pro lat bar)',

        'jalon al pecho agarre supino' => 'cable underhand pulldown',

        'jalon al pecho unilateral' => 'lever one arm lateral wide pulldown',

        'kettlebell swing' => 'kettlebell swing',

        'landmine press a una mano' => 'landmine lateral raise',

        'landmine squat' => 'landmine 180',

        'lenador (woodchopper) de abajo a arriba' => 'cable twist (up-down)',

        'lenador (woodchopper) de arriba a abajo' => 'cable twist',

        'maquina de abductores' => 'lever seated hip abduction',

        'maquina de aductores' => 'lever seated hip adduction',

        'maquina de dominadas asistidas' => 'assisted pull-up',

        'maquina de elevacion de cadera' => 'lever hip extension v. 2',
        'maquina de remo con apoyo al pecho' => 'lever seated row',

        'muscle-up en anillas' => 'kipping muscle up',

        'pajaros (deltoides posterior) en polea cruzada' => 'cable cross-over revers fly',

        'pajaros sentado' => 'dumbbell seated bent arm lateral raise',

        'paseo del granjero' => 'farmers walk',

        'paseo del granjero unilateral' => 'dumbbell single arm overhead carry',

        'patada de gluteo en polea' => 'cable standing hip extension',

        'patada de triceps en polea baja' => 'cable kickback',

        'patada de triceps simultanea' => 'dumbbell seated bent over alternate kickback',

        'pec deck' => 'lever seated fly',

        'peso muerto' => 'barbell deadlift',

        'peso muerto con deficit' => 'barbell deadlift',

        'peso muerto con mancuernas' => 'dumbbell deadlift',

        'peso muerto con piernas rigidas' => 'barbell stiff leg good morning',

        'peso muerto pesado' => 'barbell deadlift',

        'peso muerto rumano' => 'barbell romanian deadlift',

        'peso muerto rumano a una pierna' => 'dumbbell single leg deadlift',

        'peso muerto rumano agarre snatch' => 'barbell romanian deadlift',

        'peso muerto rumano pesado' => 'barbell romanian deadlift',

        'peso muerto sumo' => 'barbell sumo deadlift',

        'peso muerto sumo o convencional' => 'barbell sumo deadlift',

        'peso muerto tradicional' => 'barbell deadlift',

        'peso muerto tradicional o sumo' => 'barbell deadlift',

        'pies a la barra' => 'hanging pike',

        'pistol squat' => 'single leg squat (pistol) male',

        'plancha abdominal' => 'front plank with twist',

        'plancha con disco en espalda' => 'weighted front plank',

        'plancha con lastre progresivo' => 'weighted front plank',

        'plancha lastrada' => 'weighted front plank',

        'plancha lateral' => 'side bridge v. 2',

        'plancha lateral con rotacion' => 'side bridge v. 2',

        'prensa de piernas' => 'sled 45� leg press (side pov)',

        'prensa de piernas 45 grados' => 'sled 45� leg press (side pov)',

        'prensa de piernas unilateral' => 'sled 45 degrees one leg press',

        'prensa unilateral pesada' => 'sled 45 degrees one leg press',

        'press arnold' => 'dumbbell arnold press',

        'press de banca' => 'barbell bench press',

        'press de banca agarre cerrado' => 'barbell close-grip bench press',

        'press de banca agarre inverso' => 'barbell reverse close-grip bench press',

        'press de banca con pausa' => 'barbell bench press',

        'press de banca declinado' => 'barbell decline bench press',

        'press de banca en el suelo (floor press)' => 'barbell one arm floor press',

        'press de banca inclinado' => 'barbell incline bench press',

        'press de banca inclinado con mancuernas' => 'dumbbell incline bench press',

        'press de banca pesado' => 'barbell bench press',

        'press de banca plano pesado' => 'barbell bench press',

        'press de hombros' => 'dumbbell seated shoulder press',

        'press de hombros a una mano' => 'dumbbell one arm shoulder press',

        'press de hombros sentado' => 'dumbbell seated shoulder press',

        'press de pecho de pie con poleas' => 'cable standing up straight crossovers',

        'press de pecho declinado' => 'dumbbell decline bench press',

        'press de pecho en maquina' => 'lever chest press',

        'press de pecho inclinado' => 'dumbbell incline bench press',

        'press de pecho plano agarre neutro' => 'dumbbell neutral grip bench press',

        'press frances' => 'barbell lying triceps extension',

        'press frances declinado' => 'barbell decline close grip to skull press',

        'press frances en banco declinado' => 'ez barbell decline triceps extension',

        'press frances para triceps' => 'barbell lying back of the head tricep extension',

        'press frances sentado' => 'barbell seated overhead triceps extension',

        'press inclinado' => 'dumbbell incline bench press',

        'press militar' => 'barbell seated overhead press',

        'press militar de pie' => 'barbell standing close grip military press',

        'press militar en maquina smith' => 'smith shoulder press',

        'press militar estricto' => 'barbell standing wide military press',

        'press militar para hombros' => 'dumbbell standing overhead press',

        'press militar sentado' => 'barbell seated behind head military press',

        'press tate' => 'dumbbell tate press',

        'puente de gluteo isometrica' => 'glute bridge two legs on bench (male)',

        'pull-over pesado' => 'dumbbell pullover',

        'pull-through (tiron entre piernas)' => 'cable pull through (with rope)',

        'pullover con brazo recto en polea' => 'cable straight arm pulldown',

        'pullover con mancuerna' => 'dumbbell pullover',

        'pullover en polea alta' => 'cable incline pushdown',

        'push press' => 'dumbbell push press',

        'remo a una mano en polea' => 'cable one arm bent over row',

        'remo al menton agarre ancho' => 'barbell wide-grip upright row',

        'remo al menton con barra' => 'barbell upright row',

        'remo con barra inclinado' => 'barbell bent over row',

        'remo con mancuerna a una mano' => 'dumbbell one arm bent-over row',

        'remo con mancuernas en banco inclinado' => 'dumbbell incline row',

        'remo en maquina convergente' => 'lever seated row',

        'remo en polea baja' => 'cable low seated row',

        'remo en punta' => 'lever t bar row',

        'remo en t (t-bar row) libre' => 'lever bent over row',

        'remo ergometro (rowing machine)' => '',

        'remo pendlay' => 'barbell pendlay row',

        'remo pendlay agarre supino' => 'barbell reverse grip bent over row',

        'remo pendlay pesado' => 'barbell pendlay row',

        'remo renegado (renegade row)' => 'kettlebell alternating renegade row',

        'remo sentado' => 'cable seated row',

        'remo sentado agarre ancho' => 'cable seated wide-grip row',

        'remo sentado agarre estrecho' => 'cable straight back seated row',

        'remo yates (agarre supino)' => 'barbell reverse grip bent over row',

        'rollouts abdominales' => 'barbell rollerout',

        'rueda abdominal' => 'wheel rollerout',

        'rueda abdominal desde los pies' => 'standing wheel rollerout',

        'saltos a la soga (comba)' => 'jump rope',

        'sentadilla' => 'barbell full squat',

        'sentadilla bulgara' => 'dumbbell single leg split squat',

        'sentadilla bulgara lastrada' => 'barbell single leg split squat',

        'sentadilla con barra' => 'barbell full squat',

        'sentadilla con barra libre' => 'barbell full squat',

        'sentadilla con pausa' => 'barbell full squat',

        'sentadilla en maquina smith' => 'smith squat',

        'sentadilla frontal' => 'barbell front squat',

        'sentadilla frontal o hack' => 'sled hack squat',

        'sentadilla goblet' => 'dumbbell goblet squat',

        'sentadilla hack o pendulo' => 'sled hack squat',

        'sentadilla libre pesada' => 'barbell full squat',

        'sentadilla sumo con mancuerna' => 'dumbbell sumo pull through',

        'sentadilla trasera pesada' => 'barbell full squat (back pov)',

        'sentadilla zercher' => 'barbell zercher squat',

        'skierg' => 'ski ergometer',

        'snatch' => 'barbell one arm snatch',

        'step-ups (subidas al cajon)' => 'dumbbell step-up',

        'superman en colchoneta' => 'hyperextension',

        'swing con mancuerna' => 'dumbbell clean',

        'thruster con mancuernas' => 'barbell thruster',

        'vuelos laterales para hombros' => 'dumbbell lateral raise',

        'vuelos para hombro posterior' => 'dumbbell rear delt raise',

        'vuelos posteriores' => 'lever seated reverse fly',

        'zancadas con mancuernas' => 'dumbbell lunge',

        'zancadas estaticas' => 'split squats',

        'zancadas laterales' => 'barbell lateral lunge',

        'zancadas o lunges caminando' => 'walking lunge',

        'zancadas traseras' => 'barbell rear lunge',

    ];



    /**
     * Empaqueta la respuesta estándar para este endpoint.
     */
    private function mediaResponse(Ejercicio $ej): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'id' => $ej->id,
            'nombre' => $ej->nombre,
            'image_url' => $ej->image_url,
            'gif_url' => $ej->gif_url,
            'source' => $ej->source,
            'match_type' => $ej->source === 'visualgym' ? 'visualgym' : 'legacy',
        ]);
    }
}
