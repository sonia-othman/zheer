<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Optimization Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the translation optimization
    |
    */

    // If set to true, translations will be cached on language switch
    'cache_translations' => true,

    // Cache lifetime in minutes
    'cache_time' => 60,

    // List of available locales
    'available_locales' => [
        'en' => 'English',
        'ku' => 'کوردی',
        'ar' => 'العربية',
    ],

    // Whether to lazy load translations
    'lazy_load' => true,

    // Namespaces to eager load (even when lazy loading is enabled)
    'eager_load_namespaces' => [
        'auth',
        'pagination',
        'validation',
    ],
];
