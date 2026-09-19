<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Access logging scoped to the api group (not global): the middleware
        // is named LogApiRequest, so it should only ever see API traffic.
        // Prepended to run first — timing then covers throttle/auth/services.
        // NOTE: user_id is still accurate because it is read AFTER $next()
        // returns, by which point auth has run inside the stack.
        $middleware->api(prepend: [
            LogApiRequest::class,
        ]);

        // Short alias for route use — MUST come after auth, e.g.:
        // Route::middleware(['auth:sanctum', 'active'])->...
        // Placed globally it would see no user (auth hasn't run) and wave
        // everyone through, so there is deliberately no append() for it.
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients always get JSON errors (validation 422s, 404s, 500s),
        // never a Blade error page — matched on path, not Accept header, so
        // even header-less clients get parseable responses.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
