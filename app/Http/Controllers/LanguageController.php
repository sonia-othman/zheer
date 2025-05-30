<?php

namespace App\Http\Controllers;

class LanguageController extends Controller
{
    public function switch($lang)
    {
        if (! in_array($lang, ['en', 'ar', 'ku'])) {
            abort(400, 'Unsupported language');
        }

        app()->setLocale($lang);
        session()->put('locale', $lang);

        return back()->with([
            'isRtl' => in_array($lang, ['ar', 'ku', 'en']), // Only true for RTL languages
        ]);
    }
}
