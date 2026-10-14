<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSchedule extends Model
{
    protected $fillable = ['vehicle_id', 'scheduled_date', 'service_type', 'status', 'cost'];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'cost' => 'decimal:2'
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', 'scheduled')
                 ->whereBetween('scheduled_date', [today(), today()->addDays(14)]);
    }
}