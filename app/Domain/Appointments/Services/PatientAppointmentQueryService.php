<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Data\BookedAppointmentSummary;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\HipaaAuditAction;
use Illuminate\Support\Collection;

readonly class PatientAppointmentQueryService
{
    public function __construct(
        private HipaaAuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, BookedAppointmentSummary>
     */
    public function listForPatient(PatientProfile $patientProfile): Collection
    {
        $bookings = Appointment::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->with(['doctor.user', 'availabilitySlot'])
            ->latest()
            ->get()
            ->map(static fn (Appointment $appointment): BookedAppointmentSummary => BookedAppointmentSummary::fromAppointment($appointment))
            ->values();

        $this->auditLogger->record(
            action: HipaaAuditAction::AppointmentList,
            phiInvolved: true,
            resourceType: PatientProfile::class,
            resourceId: $patientProfile->id,
            metadata: [
                'result_count' => $bookings->count(),
            ],
        );

        return $bookings;
    }
}
