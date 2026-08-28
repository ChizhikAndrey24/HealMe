<?php

namespace App\Domain\Patients\Services;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Data\AccessiblePatientSummary;
use App\Domain\Patients\Data\DoctorPatientAccessSummary;
use App\Domain\Patients\Exceptions\PatientCardAccessException;
use App\Domain\Patients\Models\DoctorPatientAccess;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\DoctorPatientAccessSource;
use App\Enums\DoctorPatientAccessStatus;
use App\Enums\HipaaAuditAction;
use Illuminate\Support\Collection;

readonly class DoctorPatientAccessService
{
    public function __construct(
        private HipaaAuditLogger $auditLogger,
    ) {}

    public function doctorHasApprovedAccess(Doctor $doctor, PatientProfile $patientProfile): bool
    {
        return DoctorPatientAccess::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_profile_id', $patientProfile->id)
            ->where('status', DoctorPatientAccessStatus::Approved)
            ->exists();
    }

    public function approveFromAppointment(Appointment $appointment): DoctorPatientAccess
    {
        $access = DoctorPatientAccess::query()->updateOrCreate(
            [
                'patient_profile_id' => $appointment->patient_profile_id,
                'doctor_id' => $appointment->doctor_id,
            ],
            [
                'status' => DoctorPatientAccessStatus::Approved,
                'source' => DoctorPatientAccessSource::Appointment,
                'granted_via_appointment_id' => $appointment->id,
            ],
        );

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardAccessApprove,
            phiInvolved: true,
            resourceType: DoctorPatientAccess::class,
            resourceId: $access->id,
            metadata: [
                'source' => DoctorPatientAccessSource::Appointment->value,
                'doctor_id' => $appointment->doctor_id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'appointment_id' => $appointment->id,
            ],
        );

        return $access;
    }

    public function approveByPatient(PatientProfile $patientProfile, Doctor $doctor): DoctorPatientAccess
    {
        if (! $doctor->is_active || ! $doctor->is_verified) {
            throw new PatientCardAccessException('Doctor is not available for card access.');
        }

        $access = DoctorPatientAccess::query()->updateOrCreate(
            [
                'patient_profile_id' => $patientProfile->id,
                'doctor_id' => $doctor->id,
            ],
            [
                'status' => DoctorPatientAccessStatus::Approved,
                'source' => DoctorPatientAccessSource::Patient,
                'granted_via_appointment_id' => null,
            ],
        );

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardAccessApprove,
            phiInvolved: true,
            resourceType: DoctorPatientAccess::class,
            resourceId: $access->id,
            metadata: [
                'source' => DoctorPatientAccessSource::Patient->value,
                'doctor_id' => $doctor->id,
                'patient_profile_id' => $patientProfile->id,
            ],
        );

        return $access;
    }

    public function revokeByPatient(PatientProfile $patientProfile, Doctor $doctor): DoctorPatientAccess
    {
        $access = DoctorPatientAccess::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if ($access === null) {
            throw new PatientCardAccessException('No access grant found for this doctor.');
        }

        $access->update([
            'status' => DoctorPatientAccessStatus::Revoked,
            'source' => DoctorPatientAccessSource::Patient,
        ]);

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardAccessRevoke,
            phiInvolved: true,
            resourceType: DoctorPatientAccess::class,
            resourceId: $access->id,
            metadata: [
                'doctor_id' => $doctor->id,
                'patient_profile_id' => $patientProfile->id,
            ],
        );

        return $access->refresh();
    }

    /**
     * @return Collection<int, DoctorPatientAccessSummary>
     */
    public function listForPatient(PatientProfile $patientProfile): Collection
    {
        return DoctorPatientAccess::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->with('doctor.user')
            ->latest('updated_at')
            ->get()
            ->map(static fn (DoctorPatientAccess $access): DoctorPatientAccessSummary => DoctorPatientAccessSummary::fromModel($access))
            ->values();
    }

    /**
     * @return Collection<int, AccessiblePatientSummary>
     */
    public function listApprovedPatientsForDoctor(Doctor $doctor): Collection
    {
        return DoctorPatientAccess::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', DoctorPatientAccessStatus::Approved)
            ->with('patientProfile.user')
            ->latest('updated_at')
            ->get()
            ->map(static fn (DoctorPatientAccess $access): AccessiblePatientSummary => AccessiblePatientSummary::fromAccess($access))
            ->values();
    }

    /**
     * Doctors the patient can approve (verified/active), for manual grant UI.
     *
     * @return Collection<int, array{id: int, name: string|null, specialization: string}>
     */
    public function listApprovingCandidates(): Collection
    {
        return Doctor::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->with('user')
            ->orderBy('specialization')
            ->get()
            ->map(static fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => $doctor->user?->name,
                'specialization' => $doctor->specialization,
            ])
            ->values();
    }
}
