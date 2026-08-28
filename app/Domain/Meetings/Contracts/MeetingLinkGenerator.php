<?php

namespace App\Domain\Meetings\Contracts;

use App\Domain\Appointments\Models\Appointment;

interface MeetingLinkGenerator
{
    public function createForAppointment(Appointment $appointment): string;
}
