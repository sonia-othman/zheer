<?php

namespace App\Http\Controllers;

use App\Events\SensorDataUpdated;
use App\Models\SensorData;
use Illuminate\Http\Request;

class SensorDataController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'status' => 'required|boolean',
            'temperature' => 'required|numeric',
            'battery' => 'required|numeric',
            'count' => 'required|integer',
            'raw_payload' => 'sometimes|array',
        ]);

        $sensorData = SensorData::create($validated);

        // Broadcast the update
        event(new SensorDataUpdated($sensorData))->toOthers();

        return response()->json([
            'message' => 'Data saved successfully',
            'data' => $sensorData,
        ]);
    }
    public function getSensorData(Request $request)
    {
        // Get optional query parameters
        $deviceId = $request->query('device_id');
        $limit = $request->query('limit', 100); // Default to 100 records

        // Build the query
        $query = SensorData::latest();

        // Filter by device_id if provided
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        // Get the data
        $data = $query->take($limit)->get();

        // Make sure all the data is properly formatted
        $formattedData = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'device_id' => $item->device_id,
                'temperature' => (float) $item->temperature, // Ensure temperature is a float
                'count' => (int) $item->count, // Ensure count is an integer
                'battery' => (float) $item->battery, // Ensure battery is a float
                'status' => (bool) $item->status, // Ensure status is a boolean
                'created_at' => $item->created_at->toIso8601String(), // Format timestamp
                'updated_at' => $item->updated_at->toIso8601String(),
            ];
        });

        // Return as JSON
        return response()->json([
            'data' => $formattedData,
            'count' => $formattedData->count(),
        ]);
    }
    public function getStats()
{
    $latestDeviceData = \App\Models\SensorData::select('sensor_data.*')
        ->whereIn('sensor_data.id', function ($query) {
            $query->selectRaw('MAX(id)')
                  ->from('sensor_data as s2')
                  ->groupBy('device_id');
        })
        ->get(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

    return response()->json([
        'devices' => $latestDeviceData->count(),
        'alerts' => \App\Models\SensorData::where('status', true)
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
