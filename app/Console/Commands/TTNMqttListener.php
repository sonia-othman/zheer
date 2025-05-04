<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\SensorData;
use PhpMqtt\Client\Protocol\MqttVersion;
use Illuminate\Support\Facades\Log;

class TTNMqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen to TTN MQTT messages';

    // 🧫 Track open time
    protected $doorOpenTimes = []; // [device_id => opened_at]

    public function handle()
    {
        $server = 'eu1.cloud.thethings.network';
        $port = 1883;
        $clientId = 'laravel-client-' . rand();
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
            $this->info("✅ Connected to MQTT broker");

            $mqtt->subscribe("v3/{$appId}@ttn/devices/+/up", function ($topic, $message) {
                $this->info("\n📨 Message received on topic: $topic");
                $payload = json_decode($message, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("❌ JSON decode error: " . json_last_error_msg());
                    return;
                }

                $this->processPayload($payload);
            }, 0);

            $mqtt->registerLoopEventHandler(function (MqttClient $mqtt) {
                static $last = 0;
                if (time() - $last > 10) {
                    $this->info("🐧 Connection alive");
                    $last = time();
                }
            });

            $this->info("👃 Listening for messages...");
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("MQTT Listener Failed", ['error' => $e]);
        }
    }
    protected function processPayload(array $payload)
    {
        try {
            $deviceId = $payload['end_device_ids']['device_id'] ?? 'unknown';
            $decoded = $payload['uplink_message']['decoded_payload'] ?? [];
    
            $status = isset($decoded['status']) ? (bool)$decoded['status'] : false;
            $temperature = $decoded['temperatureBoard'] ?? null;
            $count = $decoded['count'] ?? null;
            $battery = $decoded['battery'] ?? null;
    
            $sensorData = SensorData::create([
                'device_id' => $deviceId,
                'status' => $status,
                'temperature' => $temperature,
                'battery' => $battery,
                'count' => $count,
                'raw_payload' => $payload,
            ]);
    
            // 🔥 FIRE SensorDataUpdated here!
            event(new \App\Events\SensorDataUpdated($sensorData));
    
            // Notifications logic for alert page (optional)
            $messages = [];
            $now = now();
    
            if ($status) {
                // door open more than 5 minutes
            } else {
                $messages[] = ['type' => 'success', 'message' => '✅ Door closed'];
            }
    
            if (!is_null($temperature)) {
                if ($temperature > 35) {
                    $messages[] = ['type' => 'danger', 'message' => "🔥 High temperature: {$temperature}°C"];
                } elseif ($temperature < 5) {
                    $messages[] = ['type' => 'danger', 'message' => "❄️ Low temperature: {$temperature}°C"];
                }
            }
    
            if (is_null($battery)) {
                $messages[] = ['type' => 'danger', 'message' => "❌ Battery disconnected"];
            } elseif ($battery < 2.5) {
                $messages[] = ['type' => 'danger', 'message' => "🔋 Critical battery low: {$battery}V"];
            } elseif ($battery < 2.9) {
                $messages[] = ['type' => 'warning', 'message' => "⚠️ Battery low: {$battery}V"];
            }
    
            foreach ($messages as $msg) {
                \App\Models\SensorNotification::create([
                    'device_id' => $deviceId,
                    'type' => $msg['type'],
                    'message' => $msg['message'],
                    'timestamp' => $now
                ]);
    
                event(new \App\Events\SensorAlert([
                    'device_id' => $deviceId,
                    'type' => $msg['type'],
                    'message' => $msg['message'],
                    'timestamp' => $now->toDateTimeString()
                ]));
            }
    
            $this->info("✅ Sensor data processed and events broadcasted.");
    
        } catch (\Exception $e) {
            $this->error("❌ Failed to process payload: " . $e->getMessage());
            \Log::error('Sensor payload error', ['error' => $e->getMessage()]);
        }
    }
    
    protected function extractValue(array $payload, array $paths)
    {
        foreach ($paths as $path) {
            $keys = explode('.', $path);
            $value = $payload;

            foreach ($keys as $key) {
                if (!isset($value[$key])) {
                    continue 2;
                }
                $value = $value[$key];
            }

            if ($value !== null) {
                return is_numeric($value) ? (float)$value : $value;
            }
        }
        return null;
    }
}