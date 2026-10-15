<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Maintenance => 'Perawatan',
            self::Inactive => 'Tidak Aktif',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Maintenance => 'yellow',
            self::Inactive => 'gray',
        };
    }
}
