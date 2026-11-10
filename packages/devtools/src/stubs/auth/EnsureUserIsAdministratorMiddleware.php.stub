<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministratorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'administrator', 403, 'Akses ditolak.');

        return $next($request);
    }
}
