<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SensorDataController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\SensorData;
use Illuminate\Http\Request;
use App\Models\SensorNotification;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Routes
Route::get('/', function () {
    $stats = [
        'devices' => SensorData::distinct('device_id')->count('device_id'),
        'alerts' => SensorData::where('status', true)
            ->where('created_at', '>', now()->subDay())
            ->count(),
        'latest' => SensorData::latest()->first()
    ];

    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'initialStats' => $stats
    ]);
})->name('home');

// Data API Routes (Web-accessible but could also be in api.php)
Route::prefix('data')->group(function () {
    // Get sensor data (for charts)
    Route::get('/sensor', function (Request $request) {
        $request->validate([
            'device_id' => 'sometimes|string'
        ]);

        $query = SensorData::query()->latest();
        
        if ($request->has('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        }
        
        return $query->take(30)->get();
    });

    // Post new sensor data (triggers real-time updates)
    Route::post('/sensor', [SensorDataController::class, 'store'])
        ->name('sensor-data.store');
    
    // Get statistics
    Route::get('/statistics', [DashboardController::class, 'getStatistics']);

});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return Inertia::render('Dashboard', [
            'initialDeviceId' => $request->query('device_id')
        ]);
        
    })->name('dashboard');

    Route::get('/notifications', function () {
        $notifications = SensorNotification::latest()->take(100)->get();
        return Inertia::render('Notifications', [
            'initialNotifications' => $notifications,
        ]);
    })->name('notifications');
});

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';