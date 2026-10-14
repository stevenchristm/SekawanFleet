<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Waiting = 'waiting';   // level sebelumnya belum selesai
    case Pending = 'pending';   // giliran approver ini
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';   // dilewati karena ditolak/dibatalkan di level lain

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu Level Sebelumnya',
            self::Pending => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Skipped => 'Dilewati',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting, self::Skipped => 'gray',
            self::Pending => 'yellow',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
