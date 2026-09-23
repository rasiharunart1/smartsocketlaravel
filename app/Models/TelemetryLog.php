<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'socket_channel_id',
        'voltage',
        'current',
        'power',
        'energy',
        'frequency',
        'power_factor',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'voltage' => 'decimal:2',
            'current' => 'decimal:3',
            'power' => 'decimal:2',
            'energy' => 'decimal:3',
            'frequency' => 'decimal:2',
            'power_factor' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function socketChannel(): BelongsTo
    {
        return $this->belongsTo(SocketChannel::class);
    }
}
