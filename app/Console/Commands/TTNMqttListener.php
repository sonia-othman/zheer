<?php

namespace App\Console\Commands;

use App\Events\SensorAlert;
use App\Events\SensorDataUpdated;
use App\Models\SensorData;
use App\Models\SensorNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class TTNMqttListener extends Command
{
    protected $signature = 'mqtt:listen';

    protected $description = 'Listen to TTN MQTT messages';

    // Simple state tracking
    protected $openDoors = []; // [device_id => opened_timestamp]

    protected $doorOpenAlertSent = []; // [device_id => true/false]

    public function handle()
    {
        $server = 'eu1.cloud.thethings.network';
        $port = 1883;
        $clientId = 'laravel-client-'.rand();
        $appId = 'zheer-platform';

        $username = 'zheer-platform@ttn';
        $password = 'NNSXS.ZYC3AZJSFFB4AGA77QG62MFQQD2VOM2HTUGWD7Y.VF33I5RIAGSSZ2S5YECEWSFTQSHHRPKU3C7IFU7BFPGZF6RYSVLQ';

        $settings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60);

        try {
            $mqtt = new MqttClient($server, $port, $clientId, '3.1.1');
            $mqtt->connect($settings, true);
            $this->info('✅ Connected to MQTT broker');

            $mqtt->subscribe("v3/{$appId}@ttn/devices/+/up", function ($topic, $message) {
                $this->info("\n📨 Message received on topic: $topic");
                $payload = json_decode($message, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('❌ JSON decode error: '.json_last_error_msg());

                    return;
                }

                $this->processPayload($payload);
            }, 0);

            $mqtt->registerLoopEventHandler(function (MqttClient $mqtt) {
                // Simple heartbeat
                static $lastHeartbeat = 0;
                if (time() - $lastHeartbeat > 10) {
                    $this->info('🐧 Connection alive');
                    $lastHeartbeat = time();
                }

                // Check doors every 20 seconds
                static $lastDoorCheck = 0;
                if (time() - $lastDoorCheck > 20) {
                    $this->checkDoorsOpenTooLong();
                    $lastDoorCheck = time();
                }
            });

            $this->info('👃 Listening for messages...');
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            Log::error('MQTT Listener Failed', ['error' => $e]);
        }
    }

    protected function checkDoorsOpenTooLong()
    {
        $now = Carbon::now();
        $this->info('🔍 Checking doors at '.$now->toDateTimeString());

        foreach ($this->openDoors as $deviceId => $openTime) {
            // Ensure openTime is a Carbon instance
            if (! ($openTime instanceof Carbon)) {
                $openTime = Carbon::parse($openTime);
                $this->openDoors[$deviceId] = $openTime; // Update with Carbon object
            }

            // Calculate minutes open - always positive by using proper Carbon methods
            $minutesOpen = $openTime->diffInMinutes($now);
            $this->info("📊 Door {$deviceId} has been open for {$minutesOpen} minutes");

            // Check if open for 1+ minute AND alert not sent yet
            if ($minutesOpen >= 1 && empty($this->doorOpenAlertSent[$deviceId])) {
                $this->info("🚨 ALERT: Door {$deviceId} open for {$minutesOpen} minutes!");

                $alertData = [
                    'device_id' => $deviceId,
                    'type' => 'danger',
                    'message' => 'Door has been open for more than a minute ⚠️', // Fallback message
                    'translation_key' => 'notifications.door_open_too_long',
                    'translation_params' => ['minutes' => $minutesOpen],
                    'timestamp' => $now,
                ];

                // Save to database
                SensorNotification::create([
                    'device_id' => $alertData['device_id'],
                    'type' => $alertData['type'],
                    'message' => $alertData['message'],
                    'translation_key' => $alertData['translation_key'],
                    'translation_params' => $alertData['translation_params'],
                    'timestamp' => $now,
                ]);

                // Broadcast event
                event(new SensorAlert([
                    'device_id' => $alertData['device_id'],
                    'type' => $alertData['type'],
                    'message' => $alertData['message'],
                    'translation_key' => $alertData['translation_key'],
                    'translation_params' => $alertData['translation_params'],
                    'timestamp' => $now->toDateTimeString(),
                ]));

                // Mark alert as sent to avoid duplicates
                $this->doorOpenAlertSent[$deviceId] = true;
            }
        }
    }

    protected function processPayload(array $payload)
    {
        try {
            $deviceId = $payload['end_device_ids']['device_id'] ?? 'unknown';
            $decoded = $payload['uplink_message']['decoded_payload'] ?? [];
            $this->info("📦 Processing data for device: $deviceId");

            $status = isset($decoded['status']) ? (bool) $decoded['status'] : false;
            $temperature = $decoded['temperatureBoard'] ?? null;
            $count = $decoded['count'] ?? null;
            $battery = $decoded['battery'] ?? null;

            // Save data to database
            $sensorData = SensorData::create([
                'device_id' => $deviceId,
                'status' => $status,
                'temperature' => $temperature,
                'battery' => $battery,
                'count' => $count,
                'raw_payload' => $payload,
            ]);

            // Broadcast sensor data update
            event(new SensorDataUpdated($sensorData));

            // Prepare notifications
            $messages = [];
            $now = Carbon::now();

            // Handle door status
            if ($status) { // TRUE = DOOR OPEN
                $this->info("🚪 Door $deviceId is OPEN");

                // If this is a new open event
                if (! isset($this->openDoors[$deviceId])) {
                    $this->info("✏️ Recording new open event for door $deviceId");
                    $messages[] = [
                        'type' => 'info',
                        'message' => 'Door opened 🚪',
                        'translation_key' => 'notifications.door_opened',
                        'translation_params' => [], // Explicitly set empty array
                    ];

                    // Record when door opened - ensure we store as Carbon instance
                    $this->openDoors[$deviceId] = Carbon::now();

                    // Reset alert status
                    $this->doorOpenAlertSent[$deviceId] = false;
                }

            } else { // FALSE = DOOR CLOSED
                $this->info("🚪 Door $deviceId is CLOSED");

                // If we were tracking this door as open
                if (isset($this->openDoors[$deviceId])) {
                    $this->info("✏️ Recording door close for $deviceId");
                    $messages[] = [
                        'type' => 'success',
                        'message' => 'Door closed ✅',
                        'translation_key' => 'notifications.door_closed',
                        'translation_params' => [], // Explicitly set empty array
                    ];

                    // Door is now closed, remove from tracking
                    unset($this->openDoors[$deviceId]);
                    unset($this->doorOpenAlertSent[$deviceId]);
                }
            }

            // Temperature checks
            if (! is_null($temperature)) {
                if ($temperature > 35) {
                    $messages[] = [
                        'type' => 'danger',
                        'message' => "Temperature is too high: {$temperature}°C 🔥", // Fallback message
                        'translation_key' => 'notifications.temp_too_high',
                        'translation_params' => ['temperature' => $temperature],
                    ];
                } elseif ($temperature < 5) {
                    $messages[] = [
                        'type' => 'danger',
                        'message' => "Temperature is too low: {$temperature}°C ❄️", // Fallback message
                        'translation_key' => 'notifications.temp_too_low',
                        'translation_params' => ['temperature' => $temperature],
                    ];
                }
            }

            // Battery checks
            if (is_null($battery)) {
                $messages[] = [
                    'type' => 'danger',
                    'message' => 'Lost connection to sensor ❌', // Fallback message
                    'translation_key' => 'notifications.connection_lost',
                ];
            } elseif ($battery < 2.5) {
                $messages[] = [
                    'type' => 'danger',
                    'message' => "Battery level critical: {$battery}V 🔋", // Fallback message
                    'translation_key' => 'notifications.battery_critical',
                    'translation_params' => ['battery' => $battery],
                ];
            } elseif ($battery < 2.9) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => "Battery level low: {$battery}V ⚠️", // Fallback message
                    'translation_key' => 'notifications.battery_low',
                    'translation_params' => ['battery' => $battery],
                ];
            }

            // Send all notifications
            foreach ($messages as $msg) {
                // Ensure we have all required keys
                $msg['translation_params'] = $msg['translation_params'] ?? [];

                // Save to database
                SensorNotification::create([
                    'device_id' => $deviceId,
                    'type' => $msg['type'],
                    'message' => $msg['message'],
                    'translation_key' => $msg['translation_key'] ?? null,
                    'translation_params' => $msg['translation_params'],
                    'timestamp' => $now,
                ]);

                // Broadcast event
                event(new SensorAlert([
                    'device_id' => $deviceId,
                    'type' => $msg['type'],
                    'message' => $msg['message'],
                    'translation_key' => $msg['translation_key'] ?? null,
                    'translation_params' => $msg['translation_params'],
                    'timestamp' => $now->toDateTimeString(),
                ]));
            }

            $this->info('✅ Sensor data processed successfully');

        } catch (\Exception $e) {
            $this->error('❌ Failed to process payload: '.$e->getMessage());
            Log::error('Sensor payload error', ['error' => $e->getMessage()]);
        }
    }

    protected function extractValue(array $payload, array $paths)
    {
        foreach ($paths as $path) {
            $keys = explode('.', $path);
            $value = $payload;

            foreach ($keys as $key) {
                if (! isset($value[$key])) {
                    continue 2;
                }
                $value = $value[$key];
            }

            if ($value !== null) {
                return is_numeric($value) ? (float) $value : $value;
            }
        }

        return null;
    }
}
