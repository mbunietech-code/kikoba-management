<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareOrganization
{
    public function handle(Request $request, Closure $next)
    {
        $org = Organization::query()->orderBy('created_at')->first()
            ?? new Organization(['name' => 'Benja Kikoba', 'currency' => 'TZS']);

        $features = Features::forOrg($org->id);

        app()->instance('kikoba.currency', $org->currency ?: 'TZS');
        app()->instance('kikoba.org', $org);
        app()->instance('kikoba.features', $features);

        View::share('org', $org);
        View::share('currentUser', $request->user());
        View::share('features', $features);

        return $next($request);
    }
}
