<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = ['location_id', 'name', 'phone', 'license_number', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Scope untuk mengecek ketersediaan driver pada rentang waktu tertentu
    public function scopeAvailableBetween(Builder $q, $start, $end): Builder
    {
        return $q->where('is_active', true)
            ->whereDoesntHave('bookings', fn ($b) => $b
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Approved])
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start));
    }
}