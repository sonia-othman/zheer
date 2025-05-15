<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add translations to Blade views (for non-Inertia pages)
        View::composer('*', function ($view) {
            $locale = App::getLocale();
            $translations = [];
                
            // Load translations from different files
            $translationFiles = ['common', 'dashboard', 'home', 'notifications'];
            
            foreach ($translationFiles as $file) {
                if (File::exists(resource_path("lang/{$locale}/{$file}.php"))) {
                    $translations[$file] = include resource_path("lang/{$locale}/{$file}.php");
                }
            }
            
            $view->with('translations', $translations);
        });
    }
}