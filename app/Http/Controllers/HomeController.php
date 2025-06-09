<?php
// HomeController.php - DEBUG VERSION
namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Home', [
            'initialStats' => null
        ]);
    }

    public function getStats()
    {
        try {
            \Log::info('=== Starting getStats method ===');
            
            // Step 1: Check if we can access the model at all
            \Log::info('Step 1: Testing basic model access...');
            $tableName = (new SensorData)->getTable();
            \Log::info('Table name: ' . $tableName);
            
            // Step 2: Check if table has data
            $totalCount = SensorData::count();
            \Log::info('Step 2: Total records in table: ' . $totalCount);
            
            if ($totalCount == 0) {
                return response()->json([
                    'message' => 'No data found in sensor_data table',
                    'devices' => 0,
                    'alerts' => 0,
                    'devicesData' => []
                ]);
            }
            
            // Step 3: Try to get latest record per device with simpler query first
            \Log::info('Step 3: Trying simpler approach...');
            
            // Get all unique device IDs
            $deviceIds = SensorData::distinct()->pluck('device_id');
            \Log::info('Found device IDs: ' . $deviceIds->toJson());
            
            // Get latest data for each device using a simpler approach
            $latestDeviceData = collect();
            foreach ($deviceIds as $deviceId) {
                $latest = SensorData::where('device_id', $deviceId)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($latest) {
                    $latestDeviceData->push($latest);
                }
            }
            
            \Log::info('Step 3: Simple query worked, got ' . $latestDeviceData->count() . ' records');
            
            // Step 4: Try the original complex query
            \Log::info('Step 4: Trying original complex query...');
            
            try {
                $complexQuery = SensorData::select('*')
                    ->whereIn('id', function ($query) {
                        $query->selectRaw('MAX(id)')
                            ->from('sensor_data')  // Remove the alias to be safe
                            ->groupBy('device_id');
                    });
                    
                \Log::info('Complex query SQL: ' . $complexQuery->toSql());
                $complexResult = $complexQuery->get();
                \Log::info('Step 4: Complex query worked, got ' . $complexResult->count() . ' records');
                
                // Use complex result if it worked
                $latestDeviceData = $complexResult;
                
            } catch (\Exception $e) {
                \Log::error('Complex query failed: ' . $e->getMessage());
                \Log::info('Using simple query result instead');
                // Keep using the simple query result
            }
            
            // Step 5: Calculate alerts
            \Log::info('Step 5: Calculating alerts...');
            $alerts = SensorData::where('status', true)
                ->where('created_at', '>', now()->subDay())
                ->count();
            \Log::info('Alerts count: ' . $alerts);
            
            // Step 6: Format response
            \Log::info('Step 6: Formatting response...');
            $response = [
                'devices' => $latestDeviceData->count(),
                'alerts' => $alerts,
                'devicesData' => $latestDeviceData->map(function ($record) {
                    return [
                        'device_id' => $record->device_id,
                        'status' => $record->status,
                        'temperature' => $record->temperature,
                        'battery' => $record->battery,
                        'count' => $record->count,
                        'created_at' => $record->created_at,
                    ];
                }),
            ];
            
            \Log::info('=== getStats completed successfully ===');
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::error('=== ERROR in getStats ===');
            \Log::error('Error message: ' . $e->getMessage());
            \Log::error('Error line: ' . $e->getLine());
            \Log::error('Error file: ' . $e->getFile());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}