<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $module)
    {
        abort_unless(Features::enabled($module), 404);

        return $next($request);
    }
}
