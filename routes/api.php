<?php

// Route::get('/devices', [DashboardController::class, 'getDevicesApi']);
// Route::get('/devices/{deviceId}', [DashboardController::class, 'getDeviceDataApi']);
// Route::get('/statistics', [DashboardController::class, 'getStatistics']);
// Route::get('/sensor-data', [DashboardController::class, 'getSensorData']);
// Route::post('/sensor-data', [SensorDataController::class, 'store']);
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LanguageController;

// Sensor Data Routes
Route::get('/devices', [DashboardController::class, 'getDevicesApi']);
Route::get('/devices/{deviceId}', [DashboardController::class, 'getDeviceDataApi']);
Route::get('/sensor-data', [DashboardController::class, 'getSensorData']);
Route::post('/sensor-data', [SensorDataController::class, 'store']);

// Notification Routes
Route::get('/notifications', [NotificationController::class, 'loadMore']);
Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

// Language Routes
Route::get('/languages', function () {
    return response()->json([
        'available' => ['en', 'ar', 'ku'],
        'current' => app()->getLocale()
    ]);
});
Route::post('/language/{lang}', [LanguageController::class, 'switch']);

// Pusher Auth Route
Route::post('/broadcasting/auth', function () {
    return Broadcast::auth(request());
});
Route::get('/test-response', function () {
    return response()->json([
        'test' => 'This is a test',
        'data' => \App\Models\SensorData::first()
    ]);
});
Route::get('/devices/{deviceId}/history', [DashboardController::class, 'getDeviceHistory']);
