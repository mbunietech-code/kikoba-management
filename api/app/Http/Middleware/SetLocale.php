<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale', 'en'));
        if (! in_array($locale, ['en', 'sw'], true)) {
            $locale = 'en';
        }
        app()->setLocale($locale);

        return $next($request);
    }
}
