<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'event_type',
        'title',
        'description',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
