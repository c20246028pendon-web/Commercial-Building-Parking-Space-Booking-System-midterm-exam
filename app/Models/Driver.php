<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vehicle_plate',
    ];

    public function parkingTickets(): HasMany
    {
        return $this->hasMany(ParkingTicket::class);
    }
}
