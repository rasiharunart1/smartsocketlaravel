<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'voltage_1',
        'current_1',
        'power_1',
        'energy_1',
        'frequency_1',
        'power_factor_1',
        'voltage_2',
        'current_2',
        'power_2',
        'energy_2',
        'frequency_2',
        'power_factor_2',
        'temperature',
        'smoke_ppm',
        'recorded_at',
    ];

    protected $casts = [
        'voltage_1' => 'float',
        'current_1' => 'float',
        'power_1' => 'float',
        'energy_1' => 'float',
        'frequency_1' => 'float',
        'power_factor_1' => 'float',
        'voltage_2' => 'float',
        'current_2' => 'float',
        'power_2' => 'float',
        'energy_2' => 'float',
        'frequency_2' => 'float',
        'power_factor_2' => 'float',
        'temperature' => 'float',
        'smoke_ppm' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get total power of both sockets (Watt)
     */
    public function getTotalPowerAttribute(): float
    {
        return round(($this->power_1 ?? 0) + ($this->power_2 ?? 0), 2);
    }

    /**
     * Get total energy of both sockets (kWh)
     */
    public function getTotalEnergyAttribute(): float
    {
        return round(($this->energy_1 ?? 0) + ($this->energy_2 ?? 0), 3);
    }
}
