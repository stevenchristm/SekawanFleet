<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSchedule extends Model
{
    /** Servis terjadwal dalam N hari ke depan dianggap "segera". */
    public const SOON_DAYS = 7;

    protected $fillable = [
        'vehicle_id', 'type', 'scheduled_date', 'odometer_due', 'description', 'workshop',
        'status', 'completed_date', 'odometer_at_service', 'cost', 'notes', 'created_by', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServiceType::class,
            'status' => ServiceStatus::class,
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeScheduled(Builder $q): Builder
    {
        return $q->where('status', ServiceStatus::Scheduled);
    }

    /** Terjadwal tapi tanggalnya sudah lewat. */
    public function scopeOverdue(Builder $q): Builder
    {
        return $q->scheduled()->where('scheduled_date', '<', today());
    }

    /** Terjadwal hari ini sampai SOON_DAYS hari ke depan. */
    public function scopeDueSoon(Builder $q): Builder
    {
        return $q->scheduled()->whereBetween('scheduled_date', [today(), today()->addDays(self::SOON_DAYS)]);
    }

    /**
     * Keadaan jatuh tempo untuk ditampilkan: overdue | soon | planned, null kalau sudah tidak terjadwal.
     */
    public function dueState(): ?string
    {
        if ($this->status !== ServiceStatus::Scheduled) {
            return null;
        }

        return match (true) {
            $this->scheduled_date->lt(today()) => 'overdue',
            $this->scheduled_date->lte(today()->addDays(self::SOON_DAYS)) => 'soon',
            default => 'planned',
        };
    }
}
