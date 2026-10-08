<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Merepresentasikan perangkat ESP32 beserta konfigurasi dan relasinya. */
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
        ];
    }

    /** Mendekripsi username MQTT saat atribut dibaca. */
    public function getMqttUsernameAttribute($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            if (! str_starts_with((string) $value, 'ey') && ! str_contains((string) $value, '{')) {
                return $value;
            }
            return null;
        }
    }

    /** Mengenkripsi username MQTT sebelum atribut disimpan. */
    public function setMqttUsernameAttribute($value): void
    {
        $this->attributes['mqtt_username'] = blank($value) ? null : encrypt($value);
    }

    /** Mendekripsi password MQTT saat atribut dibaca. */
    public function getMqttPasswordAttribute($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            if (! str_starts_with((string) $value, 'ey') && ! str_contains((string) $value, '{')) {
                return $value;
            }
            return null;
        }
    }

    /** Mengenkripsi password MQTT sebelum atribut disimpan. */
    public function setMqttPasswordAttribute($value): void
    {
        $this->attributes['mqtt_password'] = blank($value) ? null : encrypt($value);
    }

    /** Mengembalikan pemilik perangkat. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Mengembalikan kanal soket secara berurutan. */
    public function socketChannels(): HasMany
    {
        return $this->hasMany(SocketChannel::class)->orderBy('channel_number');
    }

    /** Mengembalikan ambang keselamatan perangkat. */
    public function threshold(): HasOne
    {
        return $this->hasOne(DeviceThreshold::class);
    }

    /** Mengembalikan riwayat pembacaan sensor lingkungan. */
    public function environmentalLogs(): HasMany
    {
        return $this->hasMany(EnvironmentalLog::class);
    }

    /** Mengembalikan log sensor terpadu perangkat. */
    public function sensorLogs(): HasMany
    {
        return $this->hasMany(SensorLog::class);
    }

    /** Mengembalikan snapshot sensor paling baru. */
    public function latestSensorLog(): HasOne
    {
        return $this->hasOne(SensorLog::class)->latestOfMany('recorded_at');
    }

    /** Mengembalikan alarm yang dihasilkan perangkat. */
    public function alerts(): HasMany
    {
        return $this->hasMany(DeviceAlert::class);
    }

    /** Mengembalikan aktivitas terbaru perangkat. */
    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }
}
