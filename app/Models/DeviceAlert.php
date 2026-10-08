<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Menyimpan alarm keselamatan yang diterima dari perangkat. */
class DeviceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'socket_channel_id',
        'alert_type',
        'trigger_value',
        'threshold_value',
        'action_taken',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'trigger_value' => 'decimal:2',
            'threshold_value' => 'decimal:2',
            'is_resolved' => 'boolean',
        ];
    }

    /** Mengembalikan perangkat sumber alarm. */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /** Mengembalikan kanal soket terkait, bila ada. */
    public function socketChannel(): BelongsTo
    {
        return $this->belongsTo(SocketChannel::class);
    }
}
