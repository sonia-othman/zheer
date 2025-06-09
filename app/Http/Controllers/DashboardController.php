<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // FIXED: Add the index method to handle dashboard routing
    public function index(Request $request)
    {
        $unregistered = $request->query('unregistered');

        // If it's an unregistered sensor, show the error page
        if ($unregistered) {
            return Inertia::render('Errors/SensorError', [
                'sensorType' => $unregistered
            ]);
        }

        // Otherwise, show the normal dashboard
        return Inertia::render('Dashboard', [
            'initialDeviceId' => $request->query('device_id'),
            'translations' => __('dashboard')
        ]);
    }

    public function getStatistics(Request $request)
    {
        try {
            $deviceId = $request->query('device_id');
            $query = SensorData::query();

            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }

            // OPTIMIZED: Use a single query instead of multiple queries
            $devices = SensorData::select('device_id')->distinct()->pluck('device_id');

            // OPTIMIZED: Get latest records more efficiently
            $latestRecords = SensorData::select('sensor_data.*')
                ->whereIn('sensor_data.id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('sensor_data as s2')
                        ->groupBy('device_id');
                })
                ->get(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

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
        } catch (\Exception $e) {
            \Log::error('Dashboard statistics error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load statistics'], 500);
        }
    }

public function getSensorData(Request $request)
{
    try {
        $deviceId = $request->query('device_id');
        $filter = $request->query('filter', 'daily');
        $fullMonth = $request->query('full_month', false);

        if (!$deviceId) {
            return response()->json([]);
        }

        $query = SensorData::where('device_id', $deviceId);

        switch ($filter) {
            case 'weekly':
                $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
                $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                break;

            case 'monthly':
                if ($fullMonth) {
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                          ->limit(2000);
                } else {
                    $query->where('created_at', '>=', now()->startOfMonth())->limit(1000);
                }
                break;

            default:
                $query->where('created_at', '>=', now()->startOfDay())->limit(500);
        }

        $data = $query->orderBy('created_at')
                      ->get(['device_id', 'temperature', 'battery', 'count', 'created_at']);

        if ($filter === 'weekly' || $filter === 'monthly') {
            $timezone = config('app.timezone');

            $grouped = $data->groupBy(function ($item) use ($timezone) {
                return Carbon::parse($item->created_at)->setTimezone($timezone)->toDateString();
            });

            $data = $grouped->map(function ($items, $label) use ($filter, $timezone) {
                $date = Carbon::parse($label)->setTimezone($timezone);
                return [
                    'device_id' => $items->first()->device_id,
                    'temperature' => round($items->avg('temperature'), 2),
                    'battery' => round($items->avg('battery'), 2),
                    'count' => $items->count(), // ✅ Show number of events
                    'created_at' => $items->first()->created_at,
                    'date_label' => $filter === 'weekly'
                        ? $date->format('D')
                        : $date->day
                ];
            })->values();
        }

        $formattedData = $data->map(function ($item) use ($filter) {
            return [
                'device_id' => $item['device_id'],
                'temperature' => $item['temperature'],
                'battery' => $item['battery'],
                'count' => $item['count'],
                'created_at' => $item['created_at'],
                'date_label' => $item['date_label'] ?? (
                    $filter === 'weekly'
                        ? Carbon::parse($item['created_at'])->format('D')
                        : ($filter === 'monthly'
                            ? Carbon::parse($item['created_at'])->day
                            : Carbon::parse($item['created_at'])->format('H:i'))
                ),
            ];
        });

        return response()->json($formattedData);

    } catch (\Exception $e) {
        \Log::error('Dashboard sensor data error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load sensor data'], 500);
    }
}

public function getDailyTimeData(Request $request)
{
    try {
        $deviceId = $request->query('device_id');
        
        if (!$deviceId) {
            return response()->json([]);
        }

        $data = SensorData::where('device_id', $deviceId)
            ->where('created_at', '>=', now()->startOfDay())
            ->orderBy('created_at')
            ->get(['created_at']);

        return response()->json($data->map(function ($item) {
            return [
                'created_at' => $item->created_at,
                'date_label' => $item->created_at->format('H:i')
            ];
        }));
    } catch (\Exception $e) {
        \Log::error('Daily time data error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load time data'], 500);
    }
}
    // OPTIMIZED: Add a lightweight method for initial latest data only
    public function getLatestData(Request $request)
    {
        try {
            $deviceId = $request->query('device_id');

            // FIXED: Return null if no device ID provided
            if (!$deviceId) {
                return response()->json(null);
            }

            $latestData = SensorData::where('device_id', $deviceId)
                ->latest()
                ->first(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

            return response()->json($latestData);
        } catch (\Exception $e) {
            \Log::error('Dashboard latest data error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load latest data'], 500);
        }
    }

    public function getDevicesApi()
    {
        try {
            $devices = SensorData::select('device_id')
                ->distinct()
                ->pluck('device_id');

            // Return as direct array (not wrapped)
            return $devices->toArray();
        } catch (\Exception $e) {
            \Log::error('Dashboard devices error: ' . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    public function getDeviceDataApi($deviceId)
    {
        try {
            $latest = SensorData::where('device_id', $deviceId)
                ->latest()
                ->first(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

            $request = new Request([
                'device_id' => $deviceId,
                'filter' => 'daily'
            ]);

            $historyResponse = $this->getSensorData($request);
            $history = json_decode($historyResponse->getContent(), true);

            return response()->json([
                'latest' => $latest,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard device data error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load device data'], 500);
        }
    }
    public function getDeviceHistory($deviceId)
    {
        try {
            $history = SensorData::where('device_id', $deviceId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get(['device_id', 'status', 'temperature', 'battery', 'count', 'created_at']);

            return response()->json($history);
        } catch (\Exception $e) {
            \Log::error('Dashboard device history error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load history'], 500);
        }
    }
}
