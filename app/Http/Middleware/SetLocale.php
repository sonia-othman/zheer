<?php

namespace App\Http\Middleware;

use Closure;
use Inertia\Inertia;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        if ($locale = session('locale')) {
            app()->setLocale($locale);
        }

        // Share with Inertia
        Inertia::share([
            'locale' => app()->getLocale(),
            'direction' => session('direction', 'ltr'),
        ]);

        return $next($request);
    }
}
