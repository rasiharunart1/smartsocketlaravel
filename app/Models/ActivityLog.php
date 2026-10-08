<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Menyimpan jejak aktivitas pengguna atau sistem pada perangkat. */
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'event_type',
        'title',
        'description',
    ];

    /** Mengembalikan perangkat yang terkait dengan aktivitas. */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
