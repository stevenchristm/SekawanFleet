<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleUsage extends Model
{
    protected $fillable = ['booking_id', 'start_odometer', 'end_odometer', 'fuel_liters', 'notes', 'returned_at'];

    protected function casts(): array
    {
        return [
            'fuel_liters' => 'decimal:2',
            'returned_at' => 'datetime'
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getDistanceAttribute(): ?int
    {
        return $this->end_odometer ? $this->end_odometer - $this->start_odometer : null;
    }
}