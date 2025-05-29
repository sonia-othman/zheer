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

            // FIXED: Return empty array if no device ID provided
            if (!$deviceId) {
                return response()->json([]);
            }

            $query = SensorData::where('device_id', $deviceId);

            // OPTIMIZED: Add limits to prevent loading too much data
            switch ($filter) {
                case 'weekly':
                    $query->where('created_at', '>=', now()->startOfWeek())
                        ->limit(1000); // Prevent excessive data loading
                    break;
                case 'monthly':
                    if ($fullMonth) {
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->limit(2000);
                    } else {
                        $query->where('created_at', '>=', now()->startOfMonth())
                            ->limit(1000);
                    }
                    break;
                default:
                    $query->where('created_at', '>=', now()->startOfDay())
                        ->limit(500); // Limit daily data
            }

            // In getSensorData method, modify the weekly and monthly cases:

            if ($filter === 'weekly') {
                $data = $query->orderBy('created_at')->get();

                $grouped = $data->groupBy(function ($item) {
                    return Carbon::parse($item->created_at)->dayOfWeek;
                });

                $weekDays = collect();
                for ($i = 0; $i < 7; $i++) {
                    $dayData = $grouped->get($i, collect());
                    $weekDays->push([
                        'device_id' => $deviceId,
                        'temperature' => $dayData->avg('temperature') ?? 0,
                        'battery' => $dayData->avg('battery') ?? 0,
                        'count' => $dayData->count(), // CHANGE: Use count() instead of sum()
                        'daily_count' => $dayData->count(), // ADD: Daily count
                        'created_at' => $dayData->last()->created_at ?? now()->startOfWeek()->addDays($i),
                        'date_label' => Carbon::now()->startOfWeek()->addDays($i)->format('D')
                    ]);
                }

                return response()->json($weekDays);
            }

            if ($filter === 'monthly') {
                $data = $query->orderBy('created_at')->get();

                $grouped = $data->groupBy(function ($item) {
                    return Carbon::parse($item->created_at)->day;
                });

                if ($fullMonth) {
                    $result = collect();
                    $daysInMonth = now()->daysInMonth;

                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $dayData = $grouped->get($day, collect());
                        $result->push([
                            'device_id' => $deviceId,
                            'temperature' => $dayData->avg('temperature') ?? 0,
                            'battery' => $dayData->avg('battery') ?? 0,
                            'count' => $dayData->count(), // CHANGE: Use count() instead of sum()
                            'daily_count' => $dayData->count(), // ADD: Daily count
                            'created_at' => $dayData->last()->created_at ?? now()->setDay($day),
                            'date_label' => $day
                        ]);
                    }

                    return response()->json($result);
                }

                $monthlyData = $grouped->map(function ($dayData, $day) use ($deviceId) {
                    return [
                        'device_id' => $deviceId,
                        'temperature' => $dayData->avg('temperature') ?? 0,
                        'battery' => $dayData->avg('battery') ?? 0,
                        'count' => $dayData->count(), // CHANGE: Use count() instead of sum()
                        'daily_count' => $dayData->count(), // ADD: Daily count
                        'created_at' => $dayData->last()->created_at,
                        'date_label' => $day
                    ];
                })->values();

                return response()->json($monthlyData);
            }

            // For daily view - OPTIMIZED: Select only needed columns
            if ($filter === 'daily') {
                $data = $query->orderBy('created_at')->get(['device_id', 'temperature', 'battery', 'count', 'created_at']);

                $grouped = $data->groupBy(function ($item) {
                    return Carbon::parse($item->created_at)->hour;
                });

                $hours = collect();
                for ($i = 0; $i < 24; $i++) {
                    $hourData = $grouped->get($i, collect());
                    $hours->push([
                        'device_id' => $deviceId,
                        'temperature' => $hourData->avg('temperature') ?? 0,
                        'battery' => $hourData->avg('battery') ?? 0,
                        'count' => $hourData->count(), // Number of records in this hour
                        'created_at' => $hourData->last()->created_at ?? now()->startOfDay()->addHours($i),
                        'date_label' => sprintf('%02d:00', $i)
                    ]);
                }

                return response()->json($hours);
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard sensor data error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load sensor data'], 500);
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
