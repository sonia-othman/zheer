<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the locale is set in the session
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        }

        // Optional: Detect browser language or use default
        if (!Session::has('locale')) {
            $locale = $request->getPreferredLanguage(['en', 'ku', 'ar']);
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }
}