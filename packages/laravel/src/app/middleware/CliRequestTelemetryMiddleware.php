<?php

declare(strict_types=1);

namespace Davingm\LaravelRuntime\App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

final class CliRequestTelemetryMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);

        if (! app()->environment('local') || $request->is('__laravel/cli/telemetry')) {
            return $response;
        }

        $telemetryFile = base_path('.laravel/cache/cli-telemetry-url');
        if (! is_file($telemetryFile)) {
            return $response;
        }

        $telemetryUrl = trim((string) file_get_contents($telemetryFile));
        if ($telemetryUrl === '') {
            return $response;
        }

        $milliseconds = (hrtime(true) - $startedAt) / 1_000_000;
        try {
            Http::timeout(0.5)->post($telemetryUrl, [
                'type' => 'server-request',
                'id' => bin2hex(random_bytes(8)),
                'method' => $request->method(),
                'path' => $request->getPathInfo(),
                'status' => $response->getStatusCode(),
                'milliseconds' => $milliseconds,
            ]);
        } catch (\Throwable) {
            // Request reporting must never interrupt the application response.
        }

        return $response;
    }
}
