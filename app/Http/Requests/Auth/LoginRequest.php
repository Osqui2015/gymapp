<?php

namespace App\Http\Requests\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Máximo de intentos de login por nick+IP.
     *
     * ANTES coexistían DOS limitadores distintos sobre la misma acción:
     *   - `LoginRequest::ensureIsNotRateLimited()` → 5 intentos (hardcodeado)
     *   - limiter `login` de AppServiceProvider → 10/min (middleware
     *     `throttle:login` en POST /login)
     * El más estricto ganaba y el otro era código muerto.
     *
     * AHORA hay una sola fuente de verdad. Se conserva el límite de 5
     * (el que efectivamente bloqueaba en producción y el más estricto
     * contra fuerza bruta): `LoginRequest::MAX_INTENTOS` y el limiter
     * `login` de `AppServiceProvider` usan esta MISMA constante.
     */
    public const MAX_INTENTOS = 5;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nick' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $nick = (string) $this->string('nick');

        // El chequeo de suspensión va DESPUÉS de validar la contraseña (ver
        // abajo). Antes se hacía acá y devolvía el estado de suspensión y el
        // `motivo_suspension` a cualquiera que conociera un nick, SIN
        // contraseña: eso permitía enumerar cuentas y leer datos privados
        // (motivos de suspensión con nombres y motivos).
        if (! Auth::attempt(['nick' => $nick, 'password' => (string) $this->string('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Intento fallido de autenticación. Logueamos con IP y user-agent
            // para detectar patrones de brute-force o credential stuffing.
            AuditLog::log(
                'login_failed',
                "Intento de login fallido para nick: {$nick}",
                null,
                null,
                null,
                null,
                [
                    'ip' => $this->ip(),
                    'user_agent' => $this->userAgent(),
                    'nick' => $nick,
                ]
            );

            throw ValidationException::withMessages([
                'nick' => trans('auth.failed'),
            ]);
        }

        // A partir de acá la credencial es VÁLIDA: ya se puede informar el
        // estado de la cuenta sin filtrar información a un atacante.
        $user = Auth::user();

        if ($user?->suspended) {
            // Mostramos el motivo de suspensión si está disponible. Esto evita
            // que el usuario tenga que contactar al admin a ciegas.
            $motivo = trim((string) $user->motivo_suspension);
            $message = $motivo !== ''
                ? 'Tu cuenta se encuentra suspendida: '.$motivo
                : 'Tu cuenta se encuentra suspendida.';

            // Log de intento sobre cuenta suspendida: es un evento de seguridad
            // relevante (alguien insiste en entrar a una cuenta bloqueada).
            AuditLog::log(
                'login_attempt_suspended',
                "Intento de login en cuenta suspendida: {$nick}",
                null,
                User::class,
                $user->id,
                null,
                ['ip' => $this->ip(), 'user_agent' => $this->userAgent()]
            );

            // Cerramos la sesión que recién se abrió: la cuenta está
            // suspendida, así que no debe quedar autenticada.
            Auth::logout();

            throw ValidationException::withMessages([
                'nick' => $message,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * ANTES: este método aplicaba su propio límite hardcodeado (5 intentos),
     * en paralelo con el limiter `login` de AppServiceProvider (10/min) que
     * ya se aplica en la ruta POST /login (`->middleware('throttle:login')`).
     * Dos limitadores distintos sobre la misma acción: el más estricto
     * ganaba y el otro era código muerto.
     *
     * AHORA: una sola fuente de verdad — el limiter `login`. Para cambiar el
     * límite se edita `AppServiceProvider::registerRateLimiters()`.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_INTENTOS)) {
            return;
        }

        event(new Lockout($this));

        // Log crítico: lockout por exceso de intentos. Es señal de ataque.
        AuditLog::log(
            'login_lockout',
            'Bloqueo por exceso de intentos: '.$this->throttleKey(),
            null,
            null,
            null,
            null,
            [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'available_in_seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'nick' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('nick')).'|'.$this->ip());
    }
}
