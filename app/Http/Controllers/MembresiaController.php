<?php

namespace App\Http\Controllers;

use App\Models\Membresia;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MembresiaController extends Controller
{
    public function index(Request $request)
    {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo_plan' => 'required|in:mensual,trimestral,semestral,anual',
            'precio' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'metodo_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        $membresia = Membresia::create($validated);
        $membresia->update(['estado' => 'activo', 'ultimo_pago' => now()]);

        AuditLog::forModel($membresia, 'created', null, $validated);

        return response()->json($membresia->load('user:id,name,nick'), 201);
    }

    public function update(Request $request, Membresia $membresia)
    {
        $oldValues = $membresia->toArray();

        $validated = $request->validate([
            'tipo_plan' => 'sometimes|in:mensual,trimestral,semestral,anual',
            'precio' => 'sometimes|numeric|min:0',
            'fecha_inicio' => 'sometimes|date',
            'fecha_vencimiento' => 'sometimes|date|after:fecha_inicio',
            'estado' => 'sometimes|in:activo,por_vencer,vencido,cancelado',
            'metodo_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ]);

        $membresia->update($validated);

        AuditLog::forModel($membresia, 'updated', $oldValues, $validated);

        return response()->json($membresia);
    }

    public function renew(Membresia $membresia)
    {
        $oldValues = $membresia->toArray();

        $nuevaFecha = match($membresia->tipo_plan) {
            'mensual' => now()->addMonth(),
            'trimestral' => now()->addMonths(3),
            'semestral' => now()->addMonths(6),
            'anual' => now()->addYear(),
            default => now()->addMonth(),
        };

        $membresia->update([
            'fecha_inicio' => now(),
            'fecha_vencimiento' => $nuevaFecha,
            'estado' => 'activo',
            'ultimo_pago' => now(),
        ]);

        AuditLog::forModel($membresia, 'renewed', $oldValues, $membresia->toArray());

        return response()->json($membresia);
    }

    public function usuariosSinMembresia()
    {
        $usuariosConMembresia = Membresia::pluck('user_id');
        
        $usuarios = User::whereNotIn('id', $usuariosConMembresia)
            ->where('role', '!=', 'administrador')
            ->orderBy('name')
            ->get(['id', 'name', 'nick', 'email']);

        return response()->json($usuarios);
    }

    public function porVencer()
    {
        $membresias = Membresia::with('user:id,name,nick,email')
            ->whereIn('estado', ['por_vencer', 'vencido'])
            ->where('fecha_vencimiento', '>=', now()->subDays(30)->toDateString())
            ->orderBy('fecha_vencimiento')
            ->get();

        return response()->json($membresias);
    }
}