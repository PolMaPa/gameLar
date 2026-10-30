<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso únicamente a usuarios con rol de administrador.
 *
 * Se usa siempre después del middleware "auth": un visitante sin sesión
 * se redirige al login y un usuario normal recibe un 403.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Esta sección requiere permisos de administración.');

        return $next($request);
    }
}
