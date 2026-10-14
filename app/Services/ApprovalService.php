<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function approve(BookingApproval $approval, User $user, ?string $note = null): BookingApproval
    {
        return $this->act($approval, $user, true, $note);
    }

    public function reject(BookingApproval $approval, User $user, string $note): BookingApproval
    {
        return $this->act($approval, $user, false, $note);
    }

    /**
     * Proses satu level persetujuan secara atomik.
     *
     * Urutan kunci sama dengan BookingService::cancel (booking dulu, lalu approval),
     * sehingga approve dan cancel yang bersamaan tidak saling deadlock dan
     * selalu melihat status terbaru.
     */
    private function act(BookingApproval $approval, User $user, bool $approve, ?string $note): BookingApproval
    {
        return DB::transaction(function () use ($approval, $user, $approve, $note) {
            $booking = Booking::whereKey($approval->booking_id)->lockForUpdate()->firstOrFail();
            $approval = BookingApproval::whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->approver_id !== $user->id) {
                throw new AuthorizationException('Anda bukan approver untuk level ini.');
            }

            // Cek ulang setelah lock: bisa saja sudah diproses / dibatalkan di request lain.
            if ($approval->status !== ApprovalStatus::Pending
                || $booking->status !== BookingStatus::Pending
                || $booking->current_level !== $approval->level) {
                throw ValidationException::withMessages([
                    'approval' => 'Persetujuan ini sudah diproses atau belum giliran Anda.',
                ]);
            }

            $approval->update([
                'status' => $approve ? ApprovalStatus::Approved : ApprovalStatus::Rejected,
                'note' => filled($note) ? $note : null,
                'acted_at' => now(),
            ]);

            if (! $approve) {
                // Satu level menolak = seluruh pemesanan ditolak, level berikutnya dilewati.
                $booking->approvals()
                    ->where('level', '>', $approval->level)
                    ->update(['status' => ApprovalStatus::Skipped]);

                $booking->update(['status' => BookingStatus::Rejected]);
            } elseif ($next = $booking->approvals()->where('level', $approval->level + 1)->first()) {
                $next->update(['status' => ApprovalStatus::Pending]);
                $booking->update(['current_level' => $next->level]);
            } else {
                $booking->update(['status' => BookingStatus::Approved]);
            }

            activity('approval')
                ->performedOn($booking)
                ->causedBy($user)
                ->withProperties([
                    'level' => $approval->level,
                    'decision' => $approval->status->value,
                    'note' => $approval->note,
                    'booking_status' => $booking->status->value,
                ])
                ->log(($approve ? 'Menyetujui' : 'Menolak')." pemesanan {$booking->code} (level {$approval->level})");

            return $approval;
        });
    }
}
