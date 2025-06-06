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
        return array_merge(parent::share($request),
            [
                'locale' => fn () => app()->getLocale(),
                'direction' => 'ltr',
                'translations' => fn () => [
                    'common' => trans('common'),
                    'dashboard' => trans('dashboard'),
                    'home' => trans('home'),
                    'notifications' => trans('notifications'),
                ],
                'flash' => [
                    'message' => fn () => $request->session()->get('message'),
                    'success' => fn () => $request->session()->get('success'),
                    'error' => fn () => $request->session()->get('error'),
                ],
            ]);
    }
}
