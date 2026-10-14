<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\BookingApproval;
use App\Models\User;

class BookingApprovalPolicy
{
    /**
     * Approver hanya boleh bertindak pada approval miliknya,
     * saat gilirannya (pending) dan booking masih menunggu persetujuan.
     */
    public function act(User $user, BookingApproval $approval): bool
    {
        return $user->isApprover()
            && $approval->approver_id === $user->id
            && $approval->status === ApprovalStatus::Pending
            && $approval->booking->status === BookingStatus::Pending;
    }
}
