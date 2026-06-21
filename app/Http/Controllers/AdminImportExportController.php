<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminImportExportController extends Controller
{
    public function exportUsers()
    {
        $users = User::select(['id', 'name', 'nick', 'email', 'role', 'telefono', 'trainer_id', 'suspended', 'created_at'])
            ->orderBy('name')
            ->get();

        $csv = $this->arrayToCsv([
            ['ID', 'Nombre', 'Nick', 'Email', 'Rol', 'Teléfono', 'Trainer ID', 'Suspendido', 'Fecha Registro'],
            ...$users->map(fn($u) => [
                $u->id,
                $u->name,
                $u->nick,
                $u->email,
                $u->role,
                $u->telefono,
                $u->trainer_id,
                $u->suspended ? 'Sí' : 'No',
                $u->created_at->format('Y-m-d'),
            ])->toArray()
        ]);

        return response()->streamDownload(
            () => print($csv),
            'usuarios_export_' . now()->format('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function exportEjercicios()
    {
        $ejercicios = Ejercicio::select(['id', 'nombre', 'grupo_muscular', 'equipamiento', 'visibilidad', 'descripcion'])
            ->orderBy('grupo_muscular')
            ->orderBy('nombre')
            ->get();

        $csv = $this->arrayToCsv([
            ['ID', 'Nombre', 'Grupo Muscular', 'Equipamiento', 'Visible', 'Descripción'],
            ...$ejercicios->map(fn($e) => [
                $e->id,
                $e->nombre,
                $e->grupo_muscular,
                $e->equipamiento,
                $e->visibilidad ? 'Sí' : 'No',
                $e->descripcion,
            ])->toArray()
        ]);

        return response()->streamDownload(
            () => print($csv),
            'ejercicios_export_' . now()->format('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function importUsers(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getRealPath(), 'r');
        
        $headers = fgetcsv($handle);
        $rowCount = 0;
        $errors = [];
        $created = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;
                
                // Skip empty rows
                if (empty(array_filter($row))) continue;

                try {
                    $data = $this->mapRowToUsers($headers, $row);
                    
                    if (empty($data['name']) || empty($data['email'])) {
                        $errors[] = "Fila {$rowCount}: Nombre y email son requeridos";
                        continue;
                    }

                    // Generar nick si no existe
                    if (empty($data['nick'])) {
                        $data['nick'] = strtolower(str_replace(' ', '.', $data['name'])) . $rowCount;
                    }

                    // Generar password aleatorio seguro (12 chars) - debe resetearse en primer login
                    $data['password'] = bcrypt(\Illuminate\Support\Str::random(12));

                    User::create($data);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Fila {$rowCount}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage(),
                'errors' => $errors,
            ], 422);
        }

        AuditLog::log('import', "Importó {$created} usuarios desde CSV", auth()->id());

        return response()->json([
            'success' => true,
            'created' => $created,
            'processed' => $rowCount,
            'errors' => $errors,
        ]);
    }

    public function importEjercicios(Request $request)
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getRealPath(), 'r');
        
        $headers = fgetcsv($handle);
        $rowCount = 0;
        $errors = [];
        $created = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;
                
                if (empty(array_filter($row))) continue;

                try {
                    $data = $this->mapRowToEjercicios($headers, $row);
                    
                    if (empty($data['nombre']) || empty($data['grupo_muscular'])) {
                        $errors[] = "Fila {$rowCount}: Nombre y grupo muscular son requeridos";
                        continue;
                    }

                    Ejercicio::create($data);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Fila {$rowCount}: " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage(),
                'errors' => $errors,
            ], 422);
        }

        AuditLog::log('import', "Importó {$created} ejercicios desde CSV", auth()->id());

        return response()->json([
            'success' => true,
            'created' => $created,
            'processed' => $rowCount,
            'errors' => $errors,
        ]);
    }

    private function mapRowToUsers(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $key = strtolower(trim($header));
            $value = trim($row[$i] ?? '');
            
            if (empty($value)) continue;

            switch ($key) {
                case 'nombre':
                case 'name':
                    $data['name'] = $value;
                    break;
                case 'nick':
                    $data['nick'] = $value;
                    break;
                case 'email':
                    $data['email'] = $value;
                    break;
                case 'rol':
                case 'role':
                    $data['role'] = in_array(strtolower($value), ['admin', 'administrador']) ? 'administrador' : 
                                   (in_array(strtolower($value), ['trainer', 'entrenador']) ? 'trainer' : 
                                   (in_array(strtolower($value), ['alumno', 'student']) ? 'alumno' : 'comun'));
                    break;
                case 'teléfono':
                case 'telefono':
                    $data['telefono'] = $value;
                    break;
                case 'trainer id':
                case 'trainer_id':
                    $data['trainer_id'] = is_numeric($value) ? (int)$value : null;
                    break;
            }
        }

        return $data;
    }

    private function mapRowToEjercicios(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $key = strtolower(trim($header));
            $value = trim($row[$i] ?? '');
            
            if (empty($value)) continue;

            switch ($key) {
                case 'nombre':
                    $data['nombre'] = $value;
                    break;
                case 'grupo muscular':
                case 'grupo_muscular':
                    $data['grupo_muscular'] = $value;
                    break;
                case 'equipamiento':
                    $data['equipamiento'] = $value;
                    break;
                case 'visible':
                    $data['visibilidad'] = in_array(strtolower($value), ['sí', 'si', 'yes', '1', 'true']);
                    break;
                case 'descripción':
                case 'descripcion':
                    $data['descripcion'] = $value;
                    break;
            }
        }

        return $data;
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}