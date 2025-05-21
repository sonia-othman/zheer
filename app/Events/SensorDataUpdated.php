<?php

namespace App\Events;

use App\Models\SensorData;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class SensorDataUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public SensorData $sensorData;

    public function __construct(SensorData $sensorData)
    {
        $this->sensorData = $sensorData;
    }

    public function broadcastOn()
    {
        return new Channel('sensor-data');
    }

    public function broadcastAs()
    {
        return 'SensorDataUpdated';
    }

    public function broadcastWith()
    {
        return [
            'sensorData' => $this->sensorData->toArray(),
        ];
    }
}
