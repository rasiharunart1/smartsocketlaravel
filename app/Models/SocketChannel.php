<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Merepresentasikan satu kanal relay dan PZEM pada perangkat. */
class SocketChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'channel_number',
        'name',
        'pzem_identifier',
        'is_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'channel_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Mengembalikan perangkat pemilik kanal. */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Mengembalikan log telemetri historis kanal. */
    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(TelemetryLog::class);
    }

    /** Mengembalikan telemetri paling baru dari kanal. */
    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(TelemetryLog::class)->latestOfMany('recorded_at');
    }
}
