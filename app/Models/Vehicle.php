<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\Ownership;
use App\Enums\ServiceStatus;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('fleet')
            ->setDescriptionForEvent(fn (string $event) => "Kendaraan {$this->plate_number} ".match ($event) {
                'created' => 'ditambahkan', 'updated' => 'diperbarui', 'deleted' => 'dihapus', default => $event,
            });
    }

    protected $fillable = [
        'location_id', 'rental_company_id', 'plate_number', 'brand_model',
        'type', 'ownership', 'capacity', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'ownership' => Ownership::class,
            'status' => VehicleStatus::class,
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

    public function usages(): HasManyThrough
    {
        return $this->hasManyThrough(VehicleUsage::class, Booking::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    /** Servis terjadwal terdekat (termasuk yang sudah lewat tanggal tapi belum dikerjakan). */
    public function nextService(): HasOne
    {
        return $this->hasOne(ServiceSchedule::class)->ofMany(
            ['scheduled_date' => 'min', 'id' => 'min'],
            fn ($q) => $q->where('status', ServiceStatus::Scheduled),
        );
    }

    /** Servis terakhir yang sudah selesai dikerjakan. */
    public function lastService(): HasOne
    {
        return $this->hasOne(ServiceSchedule::class)->ofMany(
            ['completed_date' => 'max', 'id' => 'max'],
            fn ($q) => $q->where('status', ServiceStatus::Completed),
        );
    }

    // Scope untuk mengecek ketersediaan kendaraan pada rentang waktu tertentu
    public function scopeAvailableBetween(Builder $q, $start, $end): Builder
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        return $q->where('status', VehicleStatus::Available)
            ->whereDoesntHave('bookings', fn ($b) => $b
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Approved])
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start))
            // Hari servis terjadwal juga membuat kendaraan tidak bisa dipesan
            ->whereDoesntHave('services', fn ($s) => $s
                ->where('status', ServiceStatus::Scheduled)
                ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()]));
    }
}
