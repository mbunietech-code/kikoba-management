<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps ordinary members out of the staff panel. Individual admin routes
 * still check their own permission; this guards the pages that have none
 * (dashboard, notifications) and sends members back to their portal.
 */
class EnsureStaff
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaff()) {
            return redirect()->route('member.home');
        }

        return $next($request);
    }
}
