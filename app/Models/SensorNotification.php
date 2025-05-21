<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorNotification extends Model
{
    protected $fillable = ['device_id', 'type', 'message', 'timestamp', 'translation_key',
        'translation_params', ];

    protected $casts = [
        'translation_params' => 'array',
        'timestamp' => 'datetime',
    ];
}
