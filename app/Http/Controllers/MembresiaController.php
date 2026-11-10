<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembresiaRequest;
use App\Http\Requests\UpdateMembresiaRequest;
use App\Models\AuditLog;
use App\Models\Membresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembresiaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Membresia::class);

        $query = Membresia::with('user:id,name,nick,email');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('user', function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('nick', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $membresias = $query->orderBy('fecha_vencimiento')->paginate(15);

        // Estadísticas
        $stats = [
            'total' => Membresia::count(),
            'activas' => Membresia::where('estado', 'activo')->count(),
            'por_vencer' => Membresia::where('estado', 'por_vencer')->count(),
            'vencidas' => Membresia::where('estado', 'vencido')->count(),
        ];

        return response()->json([
            'membresias' => $membresias,
            'stats' => $stats,
        ]);
    }

    public function store(StoreMembresiaRequest $request)
    {
        $validated = $request->validated();

        $membresia = Membresia::create($validated);
        $membresia->update(['estado' => 'activo', 'ultimo_pago' => now()]);

        AuditLog::forModel($membresia, 'created', null, $validated);

        return response()->json($membresia->load('user:id,name,nick'), 201);
    }

    public function update(UpdateMembresiaRequest $request, Membresia $membresia)
    {
        $oldValues = $membresia->toArray();

        $validated = $request->validated();

        $membresia->update($validated);

        AuditLog::forModel($membresia, 'updated', $oldValues, $validated);

        return response()->json($membresia);
    }

    public function renew(Membresia $membresia)
    {
        $this->authorize('renew', $membresia);

        $oldValues = $membresia->toArray();

        // La renovación arranca desde `max(hoy, fecha_vencimiento)`: si la
        // membresía todavía no venció, se RESPETA el tiempo pagado y la
        // nueva membresía arranca en la fecha de vencimiento. Antes se
        // calculaba `now()->addMonth()` siempre, así que renovar antes de
        // vencer regalaba los días que quedaban.
        $dias = match ($membresia->tipo_plan) {
            'mensual' => 1,
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
            default => 1,
        };

        $inicio = Carbon::now();

        $vencimiento = $membresia->fecha_vencimiento;
        if ($vencimiento !== null && $vencimiento->greaterThan($inicio)) {
            $inicio = $vencimiento->copy();
        }

        $nuevaFecha = $inicio->copy()->addMonths($dias);

        $membresia->update([
            'fecha_inicio' => $inicio,
            'fecha_vencimiento' => $nuevaFecha,
            'estado' => 'activo',
            'ultimo_pago' => now(),
        ]);

        AuditLog::forModel($membresia, 'renewed', $oldValues, $membresia->toArray());

        return response()->json($membresia);
    }

    /**
     * Usuarios sin membresía, paginados (Fase 0.7).
     *
     * Antes devolvía TODOS los usuarios sin membresía con un `get()` sin
     * límite: con cientos de alumnos la respuesta crecía sin control y el
     * navegador tenía que renderizar la lista entera.
     *
     * El shape de la respuesta mantiene `data` como la lista (para no
     * romper el frontend) y agrega la metadata de paginación.
     */
    public function usuariosSinMembresia(Request $request)
    {
        $this->authorize('viewAny', Membresia::class);

        $usuariosConMembresia = Membresia::pluck('user_id');

        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $usuarios = User::whereNotIn('id', $usuariosConMembresia)
            ->where('role', '!=', User::ROLE_ADMINISTRADOR)
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'nick', 'email', 'role', 'telefono']);

        return response()->json([
            // `data` para compatibilidad con el frontend actual.
            'data' => $usuarios->items(),
            // Metadata de paginación (mismo formato que Laravel).
            'current_page' => $usuarios->currentPage(),
            'last_page' => $usuarios->lastPage(),
            'per_page' => $usuarios->perPage(),
            'total' => $usuarios->total(),
            'from' => $usuarios->firstItem(),
            'to' => $usuarios->lastItem(),
        ]);
    }

    /**
     * Membresías por vencer o vencidas, paginadas (Fase 0.7).
     */
    public function porVencer(Request $request)
    {
        $this->authorize('viewAny', Membresia::class);

        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $membresias = Membresia::with('user:id,name,nick,email')
            ->whereIn('estado', ['por_vencer', 'vencido'])
            ->where('fecha_vencimiento', '>=', now()->subDays(30)->toDateString())
            ->orderBy('fecha_vencimiento')
            ->paginate($perPage);

        return response()->json([
            'data' => $membresias->items(),
            'current_page' => $membresias->currentPage(),
            'last_page' => $membresias->lastPage(),
            'per_page' => $membresias->perPage(),
            'total' => $membresias->total(),
            'from' => $membresias->firstItem(),
            'to' => $membresias->lastItem(),
        ]);
    }
}
