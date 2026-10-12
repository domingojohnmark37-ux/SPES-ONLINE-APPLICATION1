<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            return $next($request);
        } finally {
            $durationMs = (microtime(true) - $startedAt) * 1000;
            if ($durationMs >= 3000) {
                Log::warning('Slow HTTP request.', [
                    'route' => $request->route()?->getName() ?? $request->path(),
                    'duration_ms' => round($durationMs, 2),
                    'memory_bytes' => memory_get_peak_usage(true),
                ]);
            }
        }
    }
}
