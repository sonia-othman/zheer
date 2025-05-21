<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Carbon\Carbon;
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

        $devices = $query->select('device_id')->distinct()->pluck('device_id');

        $latestRecords = SensorData::whereIn('id', function ($sub) use ($devices) {
            $sub->selectRaw('MAX(id)')
                ->from('sensor_data')
                ->whereIn('device_id', $devices)
                ->groupBy('device_id');
        })->get();

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

    public function getDashboardData(Request $request)
    {
        $deviceId = $request->query('device_id');

        return [
            'tempBatteryData' => $this->getSensorData(new Request([
                'device_id' => $deviceId,
                'filter' => 'daily',
            ])),
            'countData' => $this->getSensorData(new Request([
                'device_id' => $deviceId,
                'filter' => 'daily',
            ])),
            'latestData' => SensorData::when($deviceId, fn ($q) => $q->where('device_id', $deviceId))
                ->latest()
                ->first(),
        ];
    }

    public function getSensorData(Request $request)
    {
        $deviceId = $request->query('device_id');
        $filter = $request->query('filter', 'daily');
        $fullMonth = $request->query('full_month', false);

        $query = SensorData::when($deviceId, fn ($q) => $q->where('device_id', $deviceId));

        switch ($filter) {
            case 'weekly':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'monthly':
                if ($fullMonth) {
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                } else {
                    $query->where('created_at', '>=', now()->startOfMonth());
                }
                break;
            default:
                $query->where('created_at', '>=', now()->startOfDay());
        }

        if ($filter === 'weekly') {
            $data = $query->orderBy('created_at')->get();

            // Group by day of week (0=Sunday to 6=Saturday)
            $grouped = $data->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->dayOfWeek;
            });

            // Create complete week structure
            $weekDays = collect();
            for ($i = 0; $i < 7; $i++) {
                $dayData = $grouped->get($i, collect());
                $weekDays->push([
                    'device_id' => $deviceId,
                    'temperature' => $dayData->avg('temperature') ?? 0,
                    'battery' => $dayData->avg('battery') ?? 0,
                    'count' => $dayData->count(), // ← fixed line
                    'created_at' => $dayData->last()->created_at ?? now()->startOfWeek()->addDays($i),
                    'date_label' => Carbon::now()->startOfWeek()->addDays($i)->format('D'),
                ]);

            }

            return $weekDays;
        }

        if ($filter === 'monthly') {
            $data = $query->orderBy('created_at')->get();

            // Group by day of month (1 - 31)
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
                        'count' => $dayData->count(), // ✅ fixed: count how many times triggered
                        'created_at' => $dayData->last()->created_at ?? now()->setDay($day),
                        'date_label' => $day,
                    ]);
                }

                return $result;
            }

            return $grouped->map(function ($dayData, $day) use ($deviceId) {
                return [
                    'device_id' => $deviceId,
                    'temperature' => $dayData->avg('temperature') ?? 0,
                    'battery' => $dayData->avg('battery') ?? 0,
                    'count' => $dayData->count(), // ✅ fixed here as well
                    'created_at' => $dayData->last()->created_at,
                    'date_label' => $day,
                ];
            })->values();
        }

        // For daily view
        return $query->orderBy('created_at')->get([
            'device_id', 'temperature', 'battery', 'count', 'created_at',
        ]);
    }
}
