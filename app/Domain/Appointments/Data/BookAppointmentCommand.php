<?php

namespace App\Domain\Appointments\Data;

use App\Http\Requests\BookAppointmentRequest;

readonly class BookAppointmentCommand
{
    public function __construct(
        public int $doctorId,
        public int $slotId,
        public AppointmentTriageInput $triage,
    ) {}

    public static function fromRequest(BookAppointmentRequest $request): self
    {
        return new self(
            doctorId: $request->integer('doctor_id'),
            slotId: $request->integer('doctor_availability_slot_id'),
            triage: AppointmentTriageInput::fromRequest($request),
        );
    }
}
