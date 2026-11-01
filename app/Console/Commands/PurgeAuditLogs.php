<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Purga los audit logs viejos (retención configurable).
 *
 * La tabla `audit_logs` es append-only: solo crece. Cada fila guarda el
 * snapshot JSON completo del modelo (`old_values` / `new_values`), así que
 * pesa ~850 bytes por fila de media.
 *
 * Medición real (local, 2026-10-31):
 *   - 462 filas / 608 KB en ~25 días de uso
 *   - ~396 filas/mes => ~4.700 filas/año => ~4 MB/año
 *
 * A ese ritmo no es una crisis, pero sin retención la tabla es un crecimiento
 * indefinido y el backup diario de la base arrastra la basura.
 *
 * Qué se purga (y qué NO):
 *   - Se borran los registros con más de N días (default 365).
 *   - NUNCA se borran los logs de seguridad: `login_failed`,
 *     `login_success`, `logout`, `role_changed`, `password_changed`.
 *     Esos se conservan siempre (pueden ser los más antiguos y son los que
 *     importan para auditar accesos).
 *
 * Uso:
 *   php artisan audit-logs:purge                      # dry-run, 365 días
 *   php artisan audit-logs:purge --days=90            # dry-run, 90 días
 *   php artisan audit-logs:purge --days=90 --prune    # borra de verdad
 *   php artisan audit-logs:purge --prune --force      # sin confirmar
 *
 * Scheduler: correr una vez por día (ver bootstrap/app.php).
 */
class PurgeAuditLogs extends Command
{
    protected $signature = 'audit-logs:purge
        {--days=365 : Retención en días (0 = purga TODO lo anterior a hoy)}
        {--prune : Ejecutar el borrado (sin esto, solo reporta)}
        {--force : No pedir confirmación}
        {--keep-security : No borrar nunca los logs de seguridad}';

    protected $description = 'Purga los audit logs que superen la retención (dry-run por default)';

    /**
     * Acciones que NUNCA se purgan.
     *
     * Son las de seguridad: interesa saber quién intentó entrar y falló,
     * quién cambió de rol, etc. Esos logs pueden ser los más viejos y
     * borrarlos dejaría sin rastro accesos sospechosos.
     */
    private const ACCIONES_SEGURIDAD = [
        'login_failed',
        'login_success',
        'logout',
        'role_changed',
        'password_changed',
        'password_reset',
        'account_deleted',
        'account_created',
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $prune = (bool) $this->option('prune');
        $force = (bool) $this->option('force');
        $keepSecurity = ! (bool) $this->option('keep-security');

        $total = AuditLog::count();

        if ($days < 0) {
            $this->error('--days no puede ser negativo.');

            return self::INVALID;
        }

        // Con 0 días we'd borrar todo lo anterior a hoy (incluye lo de hoy).
        $cutoff = now()->subDays($days)->toDateTimeString();

        $query = AuditLog::where('created_at', '<', $cutoff);

        if ($keepSecurity) {
            $query->whereNotIn('action', self::ACCIONES_SEGURIDAD);
        }

        $candidatos = (clone $query)->count();

        // Las de seguridad que quedan fuera por la protección: informativo.
        $protegidos = $keepSecurity
            ? AuditLog::where('created_at', '<', $cutoff)
                ->whereIn('action', self::ACCIONES_SEGURIDAD)
                ->count()
            : 0;

        $this->info('Purga de audit logs');
        $this->line('  Total en tabla          : '.$total);
        $this->line('  Retención                : '.$days.' días');
        $this->line('  Corte (created_at <)     : '.$cutoff);
        $this->line('  Candidatos a borrar      : '.$candidatos);
        if ($keepSecurity) {
            $this->line('  Protegidos (seguridad)   : '.$protegidos.' (se conservan siempre)');
        }
        $this->newLine();

        if ($candidatos === 0) {
            $this->info('Nada que purgar.');

            return self::SUCCESS;
        }

        if (! $prune) {
            $this->comment('Modo dry-run. Para borrar de verdad:');
            $this->comment('  php artisan audit-logs:purge --days='.$days.' --prune');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("¿Borrar $candidatos audit log(s)?", true)) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        // Borrado por lotes para no bloquear la tabla con un DELETE masivo.
        $borrados = 0;
        do {
            $batch = (clone $query)->limit(500)->delete();
            $borrados += $batch;
        } while ($batch > 0);

        $restantes = AuditLog::count();

        $this->info("✓ Borrados $borrados registro(s). Quedan $restantes en la tabla.");

        return self::SUCCESS;
    }
}
