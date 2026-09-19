<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block deactivated accounts at the HTTP layer.
 *
 * ORDERING MATTERS: register this AFTER auth (e.g. ->middleware(['auth:sanctum',
 * 'active']) on the route). It reads $request->user(), which is only set once
 * an auth middleware has run — placed globally it would see null and wave
 * everyone through. Guests (null user) pass through here; auth itself 401s
 * them. AuthService also rejects inactive logins, so this is the second net
 * for tokens issued BEFORE the account was deactivated.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return response()->json([
                'message' => 'Account is inactive.',
            ], 403);
        }

        return $next($request);
    }
}
