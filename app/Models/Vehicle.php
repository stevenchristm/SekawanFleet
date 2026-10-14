<?php

namespace App\Models;

use App\Enums\VehicleType;
use App\Enums\Ownership;
use App\Enums\VehicleStatus;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id', 'rental_company_id', 'plate_number', 'brand_model',
        'type', 'ownership', 'capacity', 'status'
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'ownership' => Ownership::class,
            'status' => VehicleStatus::class
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function rentalCompany(): BelongsTo
    {
        return $this->belongsTo(RentalCompany::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function serviceSchedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function usages(): HasManyThrough
    {
        return $this->hasManyThrough(VehicleUsage::class, Booking::class);
    }

    // Scope untuk mengecek ketersediaan kendaraan pada rentang waktu tertentu
    public function scopeAvailableBetween(Builder $q, $start, $end): Builder
    {
        return $q->where('status', VehicleStatus::Available)
            ->whereDoesntHave('bookings', fn ($b) => $b
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Approved])
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start));
    }
}