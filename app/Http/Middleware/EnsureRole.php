<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request only when the authenticated user has one of the given roles.
     *
     * Usage in routes: ->middleware('role:admin') or ->middleware('role:admin,approver')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->value, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
