<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Waiting = 'waiting';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
}