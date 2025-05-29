<?php
// HomeController.php - OPTIMIZED VERSION
namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // SOLUTION 1: Render page immediately with minimal data
        return Inertia::render('Home', [
            'initialStats' => null // Load data via AJAX after page renders
        ]);
    }

    // SOLUTION 2: Move heavy queries to separate API endpoint
    public function getStats()
    {
        // Add database indexing and query optimization
        $latestDeviceData = SensorData::select('sensor_data.*')
            ->whereIn('sensor_data.id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('sensor_data as s2')
                    ->groupBy('device_id');
            })
            ->get(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

        return response()->json([
            'devices' => $latestDeviceData->count(),
            'alerts' => SensorData::where('status', true)
                ->where('created_at', '>', now()->subDay())
                ->count(),
            'devicesData' => $latestDeviceData->map(fn($record) => [
                'device_id' => $record->device_id,
                'status' => $record->status,
                'temperature' => $record->temperature,
                'battery' => $record->battery,
                'count' => $record->count,
                'created_at' => $record->created_at,
            ])
        ]);
    }
}
