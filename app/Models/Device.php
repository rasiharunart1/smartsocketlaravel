<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uid',
        'name',
        'status',
        'ip_address',
        'mac_address',
        'wifi_rssi',
        'firmware_version',
        'last_seen_at',
        'mqtt_host',
        'mqtt_port',
        'mqtt_tls',
        'mqtt_username',
        'mqtt_password',
        'mqtt_client_id',
    ];

    protected $hidden = [
        'mqtt_username',
        'mqtt_password',
    ];

    protected function casts(): array
    {
        return [
            'wifi_rssi' => 'integer',
            'last_seen_at' => 'datetime',
            'mqtt_port' => 'integer',
            'mqtt_tls' => 'boolean',
            'mqtt_username' => 'encrypted',
            'mqtt_password' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socketChannels(): HasMany
    {
        return $this->hasMany(SocketChannel::class)->orderBy('channel_number');
    }

    public function threshold(): HasOne
    {
        return $this->hasOne(DeviceThreshold::class);
    }

    public function environmentalLogs(): HasMany
    {
        return $this->hasMany(EnvironmentalLog::class);
    }

    public function sensorLogs(): HasMany
    {
        return $this->hasMany(SensorLog::class);
    }

    public function latestSensorLog(): HasOne
    {
        return $this->hasOne(SensorLog::class)->latestOfMany('recorded_at');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(DeviceAlert::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }
}
