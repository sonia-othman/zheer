<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
   public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'translations' => [
            'common' => __('common'),
            'dashboard' => __('dashboard'),
            'home' => __('home'),
            'notifications' => __('notifications')
        ],
         'locale' => function () {
                return app()->getLocale();
            },
            'translations' => function () {
                // Get the current locale
                $locale = app()->getLocale();
                
                // Load all the translation files for the current locale
                $translations = [];
                $path = resource_path("lang/{$locale}");
                
                if (is_dir($path)) {
                    $files = scandir($path);
                    foreach ($files as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                            $key = pathinfo($file, PATHINFO_FILENAME);
                            $translations[$key] = require "{$path}/{$file}";
                        }
                    }
                }
                
                return $translations;
            },
            'available_locales' => config('app.available_locales', ['en']),

    ]);
}
}
