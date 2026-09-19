<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Access log for every request: method, path, status, actor, IP, duration.
 *
 * Runs globally (appended in bootstrap/app.php). Timing wraps $next(), so it
 * covers the full stack including route middleware and controllers. The
 * user_id is read AFTER $next() returns — auth has run by then, so it's
 * accurate (reading it before $next() would always log null).
 *
 * Deliberately NOT logged: request bodies (may carry passwords/tokens) and
 * the /up health probe (high frequency, zero signal).
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // Health probes would drown the log — skip before doing any work.
        if ($request->is('up')) {
            return $next($request);
        }

        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::channel('api')->info('API request completed', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
