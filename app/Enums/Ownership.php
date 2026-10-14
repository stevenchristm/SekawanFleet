<?php

namespace App\Enums;

enum Ownership: string
{
    case Owned = 'owned';
    case Rented = 'rented';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Milik Perusahaan',
            self::Rented => 'Sewa',
        };
    }
}
