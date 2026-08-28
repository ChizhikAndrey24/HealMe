<?php

namespace App\Enums;

enum DoctorPatientAccessStatus: string
{
    case Approved = 'approved';
    case Revoked = 'revoked';
}
