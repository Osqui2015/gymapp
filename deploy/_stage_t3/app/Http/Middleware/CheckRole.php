<?php

namespace App\Http\Middleware;

/**
 * @deprecated since 2026-08-16. Use {@see EnsureUserHasRole}
 *             (registered under the `role` alias in bootstrap/app.php).
 *             This class is NOT wired anywhere in routes/ and is kept only
 *             to avoid breaking external imports that might reference it.
 *             It can be removed in the next major cleanup pass.
 */
class CheckRole
{
    public function __construct()
    {
        trigger_error(
            'App\Http\Middleware\CheckRole is deprecated; use EnsureUserHasRole (alias: role).',
            E_USER_DEPRECATED
        );
    }
}
