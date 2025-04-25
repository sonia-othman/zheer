<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class SensorAlert implements ShouldBroadcastNow
{
    use SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('sensor-notifications');
    }

    public function broadcastAs()
    {
        return 'SensorAlert';
    }

    public function broadcastWith()
    {
        return ['alert' => $this->data];
    }
}
