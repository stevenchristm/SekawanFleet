<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;

class BookingPolicy
{
    /** Admin melihat semua; approver hanya booking yang menunjuk dirinya. */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $booking->approvals()->where('approver_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Hanya admin, dan hanya selama booking masih pending/approved. */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            && in_array($booking->status, BookingService::BLOCKING_STATUSES, true);
    }
}
