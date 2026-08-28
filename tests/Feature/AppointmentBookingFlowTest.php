<?php

namespace Tests\Feature;

use App\Domain\AI\Contracts\TriageExtractor;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeTriageExtractor;
use Tests\TestCase;

class AppointmentBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_create_a_pending_appointment(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');

        $user = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create([
            'specialization' => 'Cardiology',
            'rating' => 4.8,
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

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.specialization', 'Cardiology');

        $this->assertDatabaseHas('appointments', [
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
        ]);

        $this->assertDatabaseHas('doctor_availability_slots', [
            'id' => $slot->id,
            'is_booked' => true,
        ]);
    }

    public function test_booking_endpoint_prevents_double_booking(): void
    {
        $user = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create();
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'is_booked' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'symptoms' => 'Severe chest pain and dizziness for two days.',
            'chief_complaint' => 'Chest pain',
            'clinical_summary' => 'Patient reports chest pain and dizziness.',
            'urgency_level' => 'High',
        ]);

        $response
            ->assertConflict()
            ->assertJsonPath('message', 'Selected slot is no longer available.');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_patient_can_complete_intake_matching_and_booking_flow(): void
    {
        app()->bind(TriageExtractor::class, fn () => new FakeTriageExtractor(['Cardiology']));

        $user = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $user->id]);
        $doctor = Doctor::factory()->create([
            'specialization' => 'Cardiology',
            'rating' => 4.7,
        ]);
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
        ]);

        $triageResponse = $this->actingAs($user)->postJson('/api/v1/triage/analyze', [
            'symptoms' => 'Severe chest pain and dizziness for two days.',
        ]);

        $triageResponse
            ->assertOk()
            ->assertJsonPath('data.urgency_level', 'High');

        $matchResponse = $this->actingAs($user)->postJson('/api/v1/doctors/match', [
            'symptoms' => 'Severe chest pain and dizziness for two days.',
            'limit' => 3,
        ]);

        $matchResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $doctor->id)
            ->assertJsonPath('data.0.available_slots.0.id', $slot->id);

        $bookingResponse = $this->actingAs($user)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'symptoms' => 'Severe chest pain and dizziness for two days.',
            'chief_complaint' => 'Chest pain',
            'clinical_summary' => 'Patient reports chest pain and dizziness.',
            'urgency_level' => 'High',
        ]);

        $bookingResponse->assertCreated();
    }

    public function test_patient_can_list_their_bookings(): void
    {
        $user = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $user->id]);
        $otherPatient = PatientProfile::factory()->create();

        $doctor = Doctor::factory()->create([
            'specialization' => 'Neurology',
        ]);
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'is_booked' => true,
        ]);

        Appointment::factory()->create([
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'chief_complaint' => 'Migraine',
            'urgency_level' => 'Medium',
        ]);

        Appointment::factory()->create([
            'patient_profile_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'chief_complaint' => 'Someone else',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/appointments');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.chief_complaint', 'Migraine')
            ->assertJsonPath('data.0.specialization', 'Neurology')
            ->assertJsonPath('data.0.status', 'pending');
    }
}
