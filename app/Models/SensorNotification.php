<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorNotification extends Model
{
    protected $fillable = ['device_id', 'type', 'message', 'timestamp'];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
