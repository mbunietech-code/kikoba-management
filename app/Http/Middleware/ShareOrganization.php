<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $org = Organization::query()->orderBy('created_at')->first()
            ?? new Organization(['name' => 'Benja Kikoba', 'currency' => 'TZS']);

        app()->instance('kikoba.currency', $org->currency ?: 'TZS');
        app()->instance('kikoba.org', $org);

        View::share('org', $org);
        View::share('currentUser', $request->user());

        return $next($request);
    }
}
