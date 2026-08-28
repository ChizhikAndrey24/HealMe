<?php

namespace App\Domain\Patients\Services;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Patients\Data\PatientCardNoteSummary;
use App\Domain\Patients\Exceptions\PatientCardAccessException;
use App\Domain\Patients\Models\PatientCardNote;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\HipaaAuditAction;
use App\Enums\PatientCardNoteAuthor;
use App\Enums\PatientCardNoteSource;
use Illuminate\Support\Collection;

readonly class PatientCardNoteService
{
    public function __construct(
        private DoctorPatientAccessService $accessService,
        private HipaaAuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, PatientCardNoteSummary>
     */
    public function listForPatient(PatientProfile $patientProfile): Collection
    {
        $notes = $this->queryNotes($patientProfile);

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardView,
            phiInvolved: true,
            resourceType: PatientProfile::class,
            resourceId: $patientProfile->id,
            metadata: [
                'viewer' => 'patient',
                'result_count' => $notes->count(),
            ],
        );

        return $notes;
    }

    /**
     * @return Collection<int, PatientCardNoteSummary>
     */
    public function listForDoctor(Doctor $doctor, PatientProfile $patientProfile): Collection
    {
        $this->assertDoctorAccess($doctor, $patientProfile);

        $notes = $this->queryNotes($patientProfile);

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardView,
            phiInvolved: true,
            resourceType: PatientProfile::class,
            resourceId: $patientProfile->id,
            metadata: [
                'viewer' => 'doctor',
                'doctor_id' => $doctor->id,
                'result_count' => $notes->count(),
            ],
        );

        return $notes;
    }

    public function addDoctorNote(Doctor $doctor, PatientProfile $patientProfile, string $body): PatientCardNote
    {
        $this->assertDoctorAccess($doctor, $patientProfile);

        $note = PatientCardNote::query()->create([
            'patient_profile_id' => $patientProfile->id,
            'author_type' => PatientCardNoteAuthor::Doctor,
            'author_doctor_id' => $doctor->id,
            'source' => PatientCardNoteSource::Manual,
            'appointment_id' => null,
            'body' => trim($body),
        ]);

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardNoteCreate,
            phiInvolved: true,
            resourceType: PatientCardNote::class,
            resourceId: $note->id,
            metadata: [
                'author_type' => PatientCardNoteAuthor::Doctor->value,
                'source' => PatientCardNoteSource::Manual->value,
                'doctor_id' => $doctor->id,
                'patient_profile_id' => $patientProfile->id,
                'body_length' => mb_strlen($note->body),
            ],
        );

        return $note;
    }

    public function addSystemNoteFromAppointment(Appointment $appointment): PatientCardNote
    {
        $body = $this->formatGeminiNoteBody($appointment);

        $note = PatientCardNote::query()->create([
            'patient_profile_id' => $appointment->patient_profile_id,
            'author_type' => PatientCardNoteAuthor::System,
            'author_doctor_id' => null,
            'source' => PatientCardNoteSource::Gemini,
            'appointment_id' => $appointment->id,
            'body' => $body,
        ]);

        $this->auditLogger->record(
            action: HipaaAuditAction::PatientCardNoteCreate,
            phiInvolved: true,
            resourceType: PatientCardNote::class,
            resourceId: $note->id,
            metadata: [
                'author_type' => PatientCardNoteAuthor::System->value,
                'source' => PatientCardNoteSource::Gemini->value,
                'appointment_id' => $appointment->id,
                'patient_profile_id' => $appointment->patient_profile_id,
                'body_length' => mb_strlen($body),
            ],
        );

        return $note;
    }

    private function assertDoctorAccess(Doctor $doctor, PatientProfile $patientProfile): void
    {
        if (! $this->accessService->doctorHasApprovedAccess($doctor, $patientProfile)) {
            throw new PatientCardAccessException('Doctor does not have approved access to this patient card.');
        }
    }

    /**
     * @return Collection<int, PatientCardNoteSummary>
     */
    private function queryNotes(PatientProfile $patientProfile): Collection
    {
        return PatientCardNote::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->with('authorDoctor.user')
            ->latest()
            ->get()
            ->map(static fn (PatientCardNote $note): PatientCardNoteSummary => PatientCardNoteSummary::fromModel($note))
            ->values();
    }

    private function formatGeminiNoteBody(Appointment $appointment): string
    {
        $lines = [
            'Automated intake note from Gemini triage.',
            'Urgency: '.$appointment->urgency_level->value,
        ];

        if (is_string($appointment->chief_complaint) && $appointment->chief_complaint !== '') {
            $lines[] = 'Chief complaint: '.$appointment->chief_complaint;
        }

        if (is_string($appointment->clinical_summary) && $appointment->clinical_summary !== '') {
            $lines[] = 'Clinical summary: '.$appointment->clinical_summary;
        }

        if (is_string($appointment->symptoms) && $appointment->symptoms !== '') {
            $lines[] = 'Patient-reported symptoms: '.$appointment->symptoms;
        }

        return implode("\n", $lines);
    }
}
