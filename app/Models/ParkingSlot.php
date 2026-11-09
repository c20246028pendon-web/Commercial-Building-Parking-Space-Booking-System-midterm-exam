<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSlot extends Model
{
    use HasFactory;

    public const STATUS_VACANT = 'Vacant';

    public const STATUS_OCCUPIED = 'Occupied';

    protected $fillable = [
        'parking_zone_id',
        'slot_code',
        'status',
    ];

    public function parkingZone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class);
    }

    public function parkingTickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }
}
