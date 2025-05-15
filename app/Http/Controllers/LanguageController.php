<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
 public function switchLang($locale)
{
    // Validate locale
    if (!in_array($locale, config('app.available_locales', ['en', 'ar', 'ku']))) {
        return redirect()->back()->with('error', __('Language not supported'));
    }

    // Set application locale
    app()->setLocale($locale);
    
    // Store in session
    session()->put([
        'locale' => $locale,
        'direction' => in_array($locale, ['ar', 'ku']) ? 'rtl' : 'ltr'
    ]);

    // Return proper Inertia response
    return Inertia::location(back()->getTargetUrl());
}
}