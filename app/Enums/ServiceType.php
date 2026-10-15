<?php

namespace App\Enums;

enum ServiceType: string
{
    case Routine = 'routine';
    case Repair = 'repair';
    case Inspection = 'inspection';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Servis Berkala',
            self::Repair => 'Perbaikan',
            self::Inspection => 'Inspeksi / KIR',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Routine => 'oil_barrel',
            self::Repair => 'build',
            self::Inspection => 'fact_check',
        };
    }
}
