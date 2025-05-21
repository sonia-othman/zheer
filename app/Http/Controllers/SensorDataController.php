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
}
