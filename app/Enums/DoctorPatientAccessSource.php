<?php

namespace App\Enums;

enum DoctorPatientAccessSource: string
{
    case Appointment = 'appointment';
    case Patient = 'patient';
}
