<?php

namespace Tests\Feature;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\DoctorPatientAccess;
use App\Domain\Patients\Models\PatientCardNote;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\DoctorPatientAccessStatus;
use App\Enums\PatientCardNoteAuthor;
use App\Enums\PatientCardNoteSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PatientCardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_auto_approves_doctor_and_writes_gemini_system_note(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');

        $user = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create([
            'specialization' => 'Cardiology',
        ]);
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addHours(6),
            'ends_at' => now()->addHours(6)->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'symptoms' => 'Severe chest pain and dizziness for two days.',
            'chief_complaint' => 'Chest pain',
            'clinical_summary' => 'Patient reports chest pain and dizziness.',
            'urgency_level' => 'High',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('doctor_patient_accesses', [
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'status' => DoctorPatientAccessStatus::Approved->value,
        ]);

        $note = PatientCardNote::query()
            ->where('patient_profile_id', $patientProfile->id)
            ->first();

        $this->assertNotNull($note);
        $this->assertSame(PatientCardNoteAuthor::System, $note->author_type);
        $this->assertSame(PatientCardNoteSource::Gemini, $note->source);
        $this->assertStringContainsString('Chest pain', $note->body);
    }

    public function test_patient_can_read_notes_and_revoke_doctor_access(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        DoctorPatientAccess::query()->create([
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'status' => DoctorPatientAccessStatus::Approved,
            'source' => 'appointment',
        ]);

        PatientCardNote::query()->create([
            'patient_profile_id' => $patientProfile->id,
            'author_type' => PatientCardNoteAuthor::System,
            'source' => PatientCardNoteSource::Gemini,
            'body' => 'Automated triage note.',
        ]);

        $notesResponse = $this->actingAs($patientUser)->getJson('/api/v1/patient-card/notes');
        $notesResponse
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Automated triage note.');

        $revokeResponse = $this->actingAs($patientUser)->postJson('/api/v1/patient-card/access/revoke', [
            'doctor_id' => $doctor->id,
        ]);
        $revokeResponse
            ->assertOk()
            ->assertJsonPath('data.status', DoctorPatientAccessStatus::Revoked->value);

        $blocked = $this->actingAs($doctorUser)->getJson("/api/v1/doctor/patients/{$patientProfile->id}/notes");
        $blocked->assertForbidden();
    }

    public function test_approved_doctor_can_write_and_read_notes(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        DoctorPatientAccess::query()->create([
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'status' => DoctorPatientAccessStatus::Approved,
            'source' => 'patient',
        ]);

        $create = $this->actingAs($doctorUser)->postJson("/api/v1/doctor/patients/{$patientProfile->id}/notes", [
            'body' => 'Follow-up: improve hydration and rest.',
        ]);

        $create
            ->assertCreated()
            ->assertJsonPath('data.author_type', PatientCardNoteAuthor::Doctor->value)
            ->assertJsonPath('data.body', 'Follow-up: improve hydration and rest.');

        $list = $this->actingAs($doctorUser)->getJson("/api/v1/doctor/patients/{$patientProfile->id}/notes");
        $list->assertOk()->assertJsonCount(1, 'data');

        $patients = $this->actingAs($doctorUser)->getJson('/api/v1/doctor/patients');
        $patients
            ->assertOk()
            ->assertJsonPath('data.0.patient_profile_id', $patientProfile->id);
    }

    public function test_patient_can_manually_approve_doctor_access(): void
    {
        $patientUser = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($patientUser)->postJson('/api/v1/patient-card/access/approve', [
            'doctor_id' => $doctor->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', DoctorPatientAccessStatus::Approved->value)
            ->assertJsonPath('data.doctor_id', $doctor->id);
    }
}
