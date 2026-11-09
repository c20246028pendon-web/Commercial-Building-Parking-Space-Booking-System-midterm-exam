<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_ticket_id',
        'total_fee',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
        ];
    }

    public function parkingTicket(): BelongsTo
    {
        return $this->belongsTo(ParkingTicket::class);
    }
}
