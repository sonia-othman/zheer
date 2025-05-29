<?php
// web.php - Fixed routes to match frontend calls

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorDataController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// FAST: Page renders immediately
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// API endpoints for loading data after page render
Route::prefix('api')->group(function () {
    // Home page data
    Route::get('/home/stats', [HomeController::class, 'getStats']);

    // Notifications data
    Route::get('/notifications/load-more', [NotificationController::class, 'loadMore']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

    // Dashboard data - FIXED: Match the frontend API calls
    Route::get('/dashboard/sensor', [DashboardController::class, 'getSensorData']);
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    Route::get('/dashboard/latest', [DashboardController::class, 'getLatestData']);

    // Device API endpoints
    Route::get('/devices', [DashboardController::class, 'getDevicesApi']);
    Route::get('/devices/{deviceId}', [DashboardController::class, 'getDeviceDataApi']);
});

// FIXED: Match frontend calls - Frontend calls /data/sensor and /data/latest
Route::prefix('data')->group(function () {
    // Sensor data submission endpoint
    Route::post('/sensor', [SensorDataController::class, 'store']);

    // ADDED: Frontend data fetching endpoints to match Vue component calls
    Route::get('/sensor', [DashboardController::class, 'getSensorData']);
    Route::get('/latest', [DashboardController::class, 'getLatestData']);
    Route::get('/statistics', [DashboardController::class, 'getStatistics']);
});
