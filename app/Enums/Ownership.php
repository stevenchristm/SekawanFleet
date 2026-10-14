<?php

namespace App\Enums;

enum Ownership: string
{
    case Owned = 'owned';
    case Rented = 'rented';
}