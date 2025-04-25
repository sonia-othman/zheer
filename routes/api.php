<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// routes/api.php
Route::get('/data/sensor', function (Request $request) {
    $request->validate([
        'device_id' => 'sometimes|string'
    ]);

    $query = SensorData::query()->latest();
    
    if ($request->has('device_id')) {
        $query->where('device_id', $request->input('device_id'));
    }
    
    return $query->take(30)->get();
});