<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name');

        // Filtros
        if ($request->filled('accion')) {
            $query->where('action', $request->accion);
        }

        if ($request->filled('usuario')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->usuario}%");
            });
        }

        if ($request->filled('modelo')) {
            $query->where('model_type', 'like', "%{$request->modelo}%");
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25);

        // Acciones únicas para filtros
        $acciones = AuditLog::distinct()->pluck('action')->sort();

        return response()->json([
            'logs' => $logs,
            'acciones' => $acciones,
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog->load('user:id,name,email'));
    }

    public function getModelHistory(Request $request, string $modelType, int $modelId)
    {
        $logs = AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($logs);
    }
}