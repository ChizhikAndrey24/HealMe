<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Doctor = 'doctor';
    case Patient = 'patient';
}
