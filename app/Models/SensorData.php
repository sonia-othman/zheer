<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $fillable = [
        'device_id',
        'raw_payload',
        'temperature',
        'count',
        'battery',
        'status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'count' => 'integer',
        'status' => 'boolean',
        'temperature' => 'float',
        'battery' => 'float',
    ];
}
