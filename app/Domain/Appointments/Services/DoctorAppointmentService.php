<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Data\BookedAppointmentSummary;
use App\Domain\Appointments\Exceptions\AppointmentBookingException;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Meetings\Contracts\MeetingLinkGenerator;
use App\Domain\Meetings\Exceptions\MeetingLinkGenerationException;
use App\Enums\AppointmentStatus;
use App\Enums\HipaaAuditAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class DoctorAppointmentService
{
    public function __construct(
        private MeetingLinkGenerator $meetingLinkGenerator,
        private HipaaAuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, BookedAppointmentSummary>
     */
    public function listForDoctor(Doctor $doctor, ?AppointmentStatus $status = null): Collection
    {
        return Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->when($status !== null, static fn ($query) => $query->where('status', $status))
            ->with(['doctor.user', 'availabilitySlot', 'patientProfile.user'])
            ->latest()
            ->get()
            ->map(static fn (Appointment $appointment): BookedAppointmentSummary => BookedAppointmentSummary::fromAppointment($appointment))
            ->values();
    }

    public function approve(Doctor $doctor, Appointment $appointment): Appointment
    {
        if ($appointment->doctor_id !== $doctor->id) {
            throw new AppointmentBookingException('Appointment does not belong to this doctor.');
        }

        if ($appointment->status !== AppointmentStatus::Pending) {
            throw new AppointmentBookingException('Only pending appointments can be approved.');
        }

        try {
            $meetUrl = $this->meetingLinkGenerator->createForAppointment($appointment);
        } catch (MeetingLinkGenerationException $exception) {
            throw new AppointmentBookingException($exception->getMessage(), previous: $exception);
        }

        $appointment = DB::transaction(function () use ($appointment, $meetUrl): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::Confirmed,
                'google_meet_url' => $meetUrl,
            ]);

            return $appointment->refresh();
        });

        $this->auditLogger->record(
            action: HipaaAuditAction::AppointmentConfirm,
            phiInvolved: true,
            resourceType: Appointment::class,
            resourceId: $appointment->id,
            metadata: [
                'doctor_id' => $doctor->id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'has_meet_url' => true,
            ],
        );

        return $appointment;
    }

    public function reject(Doctor $doctor, Appointment $appointment): Appointment
    {
        if ($appointment->doctor_id !== $doctor->id) {
            throw new AppointmentBookingException('Appointment does not belong to this doctor.');
        }

        if ($appointment->status !== AppointmentStatus::Pending) {
            throw new AppointmentBookingException('Only pending appointments can be rejected.');
        }

        $appointment = DB::transaction(function () use ($appointment): Appointment {
            if ($appointment->doctor_availability_slot_id !== null) {
                $appointment->availabilitySlot?->update(['is_booked' => false]);
            }

            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
            ]);

            return $appointment->refresh();
        });

        $this->auditLogger->record(
            action: HipaaAuditAction::AppointmentCancel,
            phiInvolved: true,
            resourceType: Appointment::class,
            resourceId: $appointment->id,
            metadata: [
                'doctor_id' => $doctor->id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'reason' => 'doctor_rejected',
            ],
        );

        return $appointment;
    }
}
