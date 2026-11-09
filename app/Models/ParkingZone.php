<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function parkingSlots(): HasMany
    {
        return $this->hasMany(ParkingSlot::class);
    }
}
