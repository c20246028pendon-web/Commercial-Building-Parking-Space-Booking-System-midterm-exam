<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParkingTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'parking_slot_id',
        'entry_time',
        'exit_time',
        'total_hours',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'total_hours' => 'integer',
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function parkingSlot(): BelongsTo
    {
        return $this->belongsTo(ParkingSlot::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
