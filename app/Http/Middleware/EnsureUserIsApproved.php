<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A freshly registered account is `pending` and may only see the waiting page.
 * This guards every route of the app itself, so an unapproved user cannot
 * reach the dashboard or any lore by typing a URL — not merely by having the
 * links hidden.
 */
class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            return redirect()->route('pending');
        }

        return $next($request);
    }
}
