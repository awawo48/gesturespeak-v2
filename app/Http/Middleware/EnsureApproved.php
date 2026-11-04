<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks adult accounts that are not yet approved (e.g. newly registered teachers)
 * and sends them to the "waiting for approval" page.
 */
class EnsureApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && ! $user->isApproved()) {
            return redirect()->route('guardian.pending');
        }

        return $next($request);
    }
}

