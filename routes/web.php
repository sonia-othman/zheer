<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorDataController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\SensorData;
use Illuminate\Http\Request;

Route::get('/', function () {
    return Inertia::render('Home', [
        'initialStats' => [
            'devices' => SensorData::distinct('device_id')->count('device_id'),
            'alerts' => SensorData::where('status', true)
                ->where('created_at', '>', now()->subDay())
                ->count(),
            'devicesData' => SensorData::latest()
                ->get()
                ->unique('device_id')
                ->map(fn($record) => [
                    'device_id' => $record->device_id,
                    'status' => $record->status,
                    'temperature' => $record->temperature,
                    'battery' => $record->battery,
                    'count' => $record->count,
                    'created_at' => $record->created_at,
                ])
                ->values()
        ]
    ]);
})->name('home');

Route::prefix('data')->group(function () {
    Route::get('/sensor', [DashboardController::class, 'getSensorData']);
    Route::post('/sensor', [SensorDataController::class, 'store']);
    Route::get('/statistics', [DashboardController::class, 'getStatistics']);
});


Route::get('/notifications', function () {
    return Inertia::render('Notifications', [
        'initialNotifications' => \App\Models\SensorNotification::latest()
            ->take(100)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'device_id' => $n->device_id,
                'type' => $n->type,
                'message' => $n->message,
                'timestamp' => $n->created_at
            ])
    ]);
})->name('notifications');

Route::get('/dashboard', function (Request $request) {
    return Inertia::render('Dashboard', [
        'initialDeviceId' => $request->query('device_id'),
        'initialData' => app(DashboardController::class)->getDashboardData($request),
        'translations' => __('dashboard') // Gets all dashboard.* translations
    ]);
})->name('dashboard');

Route::get('language/{locale}', [App\Http\Controllers\LanguageController::class, 'switchLang'])->name('language.switch');
