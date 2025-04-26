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
    
}
