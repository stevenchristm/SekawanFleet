<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 'vehicle_id', 'driver_id', 'created_by', 'requester_name',
        'purpose', 'destination', 'start_at', 'end_at', 'status', 'current_level',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class)->orderBy('level');
    }

    public function usage(): HasOne
    {
        return $this->hasOne(VehicleUsage::class);
    }

    public function currentApproval(): HasOne
    {
        return $this->hasOne(BookingApproval::class)->where('status', ApprovalStatus::Pending);
    }

    // Auto-generate kode pemesanan unik, misal: BK-202610-0001
    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            $prefix = 'BK-'.now()->format('Ym').'-';
            $last = static::where('code', 'like', $prefix.'%')->max('code');
            $b->code ??= $prefix.str_pad(((int) substr($last ?? '0', -4)) + 1, 4, '0', STR_PAD_LEFT);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('booking')
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Pemesanan {$this->code} dibuat",
                'updated' => "Pemesanan {$this->code} diperbarui",
                'deleted' => "Pemesanan {$this->code} dihapus",
                default => "Pemesanan {$this->code}: {$event}",
            });
    }
}
