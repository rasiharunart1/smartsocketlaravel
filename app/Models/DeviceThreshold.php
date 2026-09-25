<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'max_voltage',
        'max_current',
        'max_temperature',
        'max_smoke_ppm',
        'kwh_rate',
        'log_interval',
        'device_interval',
    ];

    protected function casts(): array
    {
        return [
            'max_voltage' => 'decimal:2',
            'max_current' => 'decimal:2',
            'max_temperature' => 'decimal:2',
            'max_smoke_ppm' => 'decimal:2',
            'kwh_rate' => 'decimal:2',
            'log_interval' => 'integer',
            'device_interval' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
