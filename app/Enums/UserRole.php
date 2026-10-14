<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Approver = 'approver';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Approver => 'Approver',
            self::Employee => 'Pegawai',
        };
    }
}
