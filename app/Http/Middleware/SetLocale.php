<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
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
        'direction' => session('direction', 'ltr')
    ]);
    
    return $next($request);
}
}