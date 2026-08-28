<?php

namespace App\Domain\Appointments\Data;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\AppointmentStatus;
use App\Enums\UrgencyLevel;

readonly class AppointmentWriteData
{
    public function __construct(
        public int $patientProfileId,
        public int $doctorId,
        public int $slotId,
        public AppointmentTriageInput $triage,
    ) {}

    public static function fromBooking(
        PatientProfile $patientProfile,
        Doctor $doctor,
        DoctorAvailabilitySlot $slot,
        AppointmentTriageInput $triage,
    ): self {
        return new self(
            patientProfileId: $patientProfile->id,
            doctorId: $doctor->id,
            slotId: $slot->id,
            triage: $triage,
        );
    }

    /**
     * @return array{
     *     patient_profile_id: int,
     *     doctor_id: int,
     *     doctor_availability_slot_id: int,
     *     symptoms: string,
     *     chief_complaint: string,
     *     clinical_summary: string,
     *     urgency_level: UrgencyLevel,
     *     status: AppointmentStatus
     * }
     */
    public function toCreateAttributes(): array
    {
        return [
            'patient_profile_id' => $this->patientProfileId,
            'doctor_id' => $this->doctorId,
            'doctor_availability_slot_id' => $this->slotId,
            'symptoms' => $this->triage->symptoms,
            'chief_complaint' => $this->triage->chiefComplaint,
            'clinical_summary' => $this->triage->clinicalSummary,
            'urgency_level' => $this->triage->urgencyLevel,
            'status' => AppointmentStatus::Pending,
        ];
    }
}
