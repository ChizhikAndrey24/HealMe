<?php

namespace App\Infrastructure\Meetings;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Meetings\Contracts\MeetingLinkGenerator;

readonly class FakeMeetingLinkGenerator implements MeetingLinkGenerator
{
    public function createForAppointment(Appointment $appointment): string
    {
        return 'https://meet.google.com/heal-'.str_pad((string) $appointment->id, 3, '0', STR_PAD_LEFT).'-test';
    }
}
