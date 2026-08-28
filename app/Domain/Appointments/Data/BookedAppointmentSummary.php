<?php

namespace App\Domain\Appointments\Data;

use App\Domain\Appointments\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Enums\UrgencyLevel;

readonly class BookedAppointmentSummary
{
    public function __construct(
        public int $id,
        public AppointmentStatus $status,
        public ?string $doctorName,
        public string $specialization,
        public ?string $patientName,
        public ?string $slotStartsAt,
        public ?string $chiefComplaint,
        public UrgencyLevel $urgencyLevel,
        public ?string $clinicalSummary,
        public ?string $googleMeetUrl,
    ) {}

    public static function fromAppointment(Appointment $appointment): self
    {
        $appointment->loadMissing(['doctor.user', 'availabilitySlot', 'patientProfile.user']);

        return new self(
            id: $appointment->id,
            status: $appointment->status,
            doctorName: $appointment->doctor->user?->name,
            specialization: $appointment->doctor->specialization,
            patientName: $appointment->patientProfile->user?->name,
            slotStartsAt: $appointment->availabilitySlot?->starts_at?->toIso8601String(),
            chiefComplaint: $appointment->chief_complaint,
            urgencyLevel: $appointment->urgency_level,
            clinicalSummary: $appointment->clinical_summary,
            googleMeetUrl: $appointment->google_meet_url,
        );
    }

    /**
     * @return array{
     *     id: int,
     *     status: string,
     *     doctor: string|null,
     *     specialization: string,
     *     patient: string|null,
     *     slot: string|null,
     *     chief_complaint: string|null,
     *     urgency_level: string,
     *     clinical_summary: string|null,
     *     google_meet_url: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'doctor' => $this->doctorName,
            'specialization' => $this->specialization,
            'patient' => $this->patientName,
            'slot' => $this->slotStartsAt,
            'chief_complaint' => $this->chiefComplaint,
            'urgency_level' => $this->urgencyLevel->value,
            'clinical_summary' => $this->clinicalSummary,
            'google_meet_url' => $this->googleMeetUrl,
        ];
    }
}
