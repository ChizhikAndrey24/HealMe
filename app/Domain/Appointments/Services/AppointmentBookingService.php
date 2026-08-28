<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Data\AppointmentWriteData;
use App\Domain\Appointments\Data\BookAppointmentCommand;
use App\Domain\Appointments\Exceptions\AppointmentBookingException;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Domain\Patients\Services\DoctorPatientAccessService;
use App\Domain\Patients\Services\PatientCardNoteService;
use App\Enums\HipaaAuditAction;
use Illuminate\Support\Facades\DB;

readonly class AppointmentBookingService
{
    public function __construct(
        private HipaaAuditLogger $auditLogger,
        private DoctorPatientAccessService $accessService,
        private PatientCardNoteService $cardNoteService,
    ) {}

    public function bookPendingAppointment(
        PatientProfile $patientProfile,
        BookAppointmentCommand $command,
    ): Appointment {
        $appointment = DB::transaction(function () use ($patientProfile, $command): Appointment {
            $doctor = Doctor::query()
                ->whereKey($command->doctorId)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->first();

            if ($doctor === null) {
                throw new AppointmentBookingException('Doctor is not available for booking.');
            }

            $slot = DoctorAvailabilitySlot::query()
                ->whereKey($command->slotId)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->first();

            if ($slot === null || $slot->is_booked) {
                throw new AppointmentBookingException('Selected slot is no longer available.');
            }

            $slot->update(['is_booked' => true]);

            $appointment = Appointment::query()->create(
                AppointmentWriteData::fromBooking(
                    patientProfile: $patientProfile,
                    doctor: $doctor,
                    slot: $slot,
                    triage: $command->triage,
                )->toCreateAttributes(),
            );

            $this->accessService->approveFromAppointment($appointment);
            $this->cardNoteService->addSystemNoteFromAppointment($appointment);

            return $appointment;
        });

        $this->auditLogger->record(
            action: HipaaAuditAction::AppointmentCreate,
            phiInvolved: true,
            resourceType: Appointment::class,
            resourceId: $appointment->id,
            metadata: [
                'doctor_id' => $appointment->doctor_id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'urgency_level' => $appointment->urgency_level->value,
                'status' => $appointment->status->value,
            ],
        );

        return $appointment;
    }
}
