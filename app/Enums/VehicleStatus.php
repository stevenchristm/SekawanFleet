<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}