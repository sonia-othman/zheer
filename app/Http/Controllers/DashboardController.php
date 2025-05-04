<?php
namespace App\Http\Controllers;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
    $filter = $request->query('filter', 'daily'); // default to daily
    $fullMonth = $request->query('full_month', false); // for monthly view

    $query = SensorData::query();

    if ($deviceId) {
        $query->where('device_id', $deviceId);
    }

    // Set date range based on filter
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
        default: // daily
            $query->where('created_at', '>=', now()->startOfDay());
            break;
    }

    // For monthly view, group by day and count triggers
    if ($filter === 'monthly') {
        $data = $query->selectRaw('
                DATE(created_at) as date,
                AVG(temperature) as temperature,
                AVG(battery) as battery,
                COUNT(*) as count,
                MAX(created_at) as created_at
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days with zero counts
        if ($fullMonth) {
            $daysInMonth = now()->daysInMonth;
            $result = collect();
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = now()->setDay($day)->format('Y-m-d');
                $record = $data->firstWhere('date', $date);
                
                $result->push([
                    'device_id' => $deviceId,
                    'temperature' => $record ? $record->temperature : 0,
                    'battery' => $record ? $record->battery : 0,
                    'count' => $record ? $record->count : 0,
                    'created_at' => $record ? $record->created_at : now()->setDay($day)->toDateTimeString(),
                    'date_label' => $day // For chart labeling
                ]);
            }
            
            return $result;
        }
        
        return $data->map(function ($item) use ($deviceId) {
            return [
                'device_id' => $deviceId,
                'temperature' => $item->temperature,
                'battery' => $item->battery,
                'count' => $item->count,
                'created_at' => $item->created_at,
                'date_label' => Carbon::parse($item->date)->day // Day of month
            ];
        });
    }

    // For weekly view, group by day of week
    if ($filter === 'weekly') {
        return $query->selectRaw('
                DAYOFWEEK(created_at) as day_of_week,
                AVG(temperature) as temperature,
                AVG(battery) as battery,
                COUNT(*) as count,
                MAX(created_at) as created_at
            ')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($item) use ($deviceId) {
                return [
                    'device_id' => $deviceId,
                    'temperature' => $item->temperature,
                    'battery' => $item->battery,
                    'count' => $item->count,
                    'created_at' => $item->created_at,
                    'day_label' => Carbon::parse($item->created_at)->dayName // Day name
                ];
            });
    }

    // For daily view, return all records (grouping happens in frontend)
    return $query->orderBy('created_at')->get([
        'device_id', 'temperature', 'battery', 'count', 'created_at'
    ]);
}
}
