<?php

namespace App\Enums;

enum LocationType: string
{
    case HeadOffice = 'head_office';
    case BranchOffice = 'branch_office';
    case Mine = 'mine';
}