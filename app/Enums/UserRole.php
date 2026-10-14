<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Approver = 'approver';
    case Employee = 'employee';
}