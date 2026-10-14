<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $fillable = ['vehicle_id', 'filled_at', 'liters', 'cost', 'odometer'];

    protected function casts(): array
    {
        return [
            'filled_at' => 'date',
            'liters' => 'decimal:2',
            'cost' => 'decimal:2'
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}