<?php
namespace App\Http\Controllers;
use App\Models\SensorData;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStatistics(Request $request)
    {
        $deviceId = $request->query('device_id');

        $query = SensorData::query();

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        // Get distinct device IDs
        $devices = $query->select('device_id')->distinct()->pluck('device_id');

        // Get latest record per device using subquery
        $latestRecords = SensorData::whereIn('id', function ($sub) use ($devices) {
            $sub->selectRaw('MAX(id)')
                ->from('sensor_data')
                ->whereIn('device_id', $devices)
                ->groupBy('device_id');
        })->get();

        // Build result
        $deviceData = $latestRecords->map(function ($record) {
            return [
                'device_id' => $record->device_id,
                'status' => $record->status,
                'temperature' => $record->temperature,
                'battery' => $record->battery,
                'count' => $record->count,
                'created_at' => $record->created_at,
            ];
        });

        return response()->json([
            'devices' => $devices->count(),
            'alerts' => SensorData::where('status', true)
                                  ->where('created_at', '>', now()->subDay())
                                  ->count(),
            'devicesData' => $deviceData,
        ]);
    }
    public function getSensorData(Request $request)
{
    $deviceId = $request->query('device_id');
    $filter = $request->query('filter'); // daily, weekly, monthly

    $query = SensorData::query();

    if ($deviceId) {
        $query->where('device_id', $deviceId);
    }

    // Always get data from 17th April (or earlier if you want)
    $startDate = '2024-04-17 00:00:00';
    $query->where('created_at', '>=', $startDate);

    // Sort by date
    $sensorData = $query->orderBy('created_at')->get([
        'device_id', 'temperature', 'battery', 'count', 'created_at'
    ]);

    return response()->json($sensorData);
}

    
}
