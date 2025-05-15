<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
  public function switchLang($locale)
{
    if (in_array($locale, ['en', 'ar', 'ku'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
}

}