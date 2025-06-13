<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Broadcast;

Route::get('/home/get-stats', [HomeController::class, 'getStats']);
Route::get('/devices', [DashboardController::class, 'getDevicesApi']);
Route::get('/devices/{deviceId}', [DashboardController::class, 'getDeviceDataApi']);
Route::get('/sensor-data', [DashboardController::class, 'getSensorData']);
Route::post('/sensor-data', [SensorDataController::class, 'store']);
Route::get('/daily-time-data', [DashboardController::class, 'getDailyTimeData']);
Route::post('/language/{lang}', [LanguageController::class, 'switch']);
Route::get('/devices/{deviceId}/history', [DashboardController::class, 'getDeviceHistory']);
Route::get('/notifications/load-more', [NotificationController::class, 'loadMore']);

Route::post('/broadcasting/auth', function () {
    return Broadcast::auth(request());
});
