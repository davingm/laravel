<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOperatorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array($request->user()?->role, ['administrator', 'operator'], true),
            403,
            'Akses ditolak.'
        );

        return $next($request);
    }
}
