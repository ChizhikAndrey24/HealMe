<?php

namespace Tests\Feature;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\AppointmentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_approve_pending_appointment_and_receive_meet_link(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');

        $patientUser = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addHours(3),
            'ends_at' => now()->addHours(3)->addMinutes(30),
            'is_booked' => true,
        ]);

        $appointment = Appointment::factory()->create([
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $response = $this->actingAs($doctorUser)->postJson(
            "/api/v1/doctor/appointments/{$appointment->id}/approve",
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Confirmed->value)
            ->assertJsonPath('data.google_meet_url', 'https://meet.google.com/heal-'.str_pad((string) $appointment->id, 3, '0', STR_PAD_LEFT).'-test');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    }

    public function test_doctor_can_reject_pending_appointment_and_free_slot(): void
    {
        $patientUser = User::factory()->patient()->create();
        $patientProfile = PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctorUser = User::factory()->doctor()->create();
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'is_booked' => true,
        ]);

        $appointment = Appointment::factory()->create([
            'patient_profile_id' => $patientProfile->id,
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $response = $this->actingAs($doctorUser)->postJson(
            "/api/v1/doctor/appointments/{$appointment->id}/reject",
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value);

        $this->assertDatabaseHas('doctor_availability_slots', [
            'id' => $slot->id,
            'is_booked' => false,
        ]);
    }

    public function test_patient_can_browse_doctors_directory(): void
    {
        $patientUser = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctor = Doctor::factory()->create([
            'specialization' => 'Neurology',
            'bio' => 'Headache specialist',
        ]);

        $response = $this->actingAs($patientUser)->getJson('/api/v1/doctors');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $doctor->id)
            ->assertJsonPath('data.0.specialization', 'Neurology')
            ->assertJsonPath('data.0.bio', 'Headache specialist')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonStructure(['filters' => ['specializations']]);
    }

    public function test_patient_can_filter_and_paginate_doctors_directory(): void
    {
        $patientUser = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patientUser->id]);

        $alphaUser = User::factory()->doctor()->create(['name' => 'Alpha Neuro']);
        $betaUser = User::factory()->doctor()->create(['name' => 'Beta Heart']);

        Doctor::factory()->create([
            'user_id' => $alphaUser->id,
            'specialization' => 'Neurology',
        ]);
        Doctor::factory()->create([
            'user_id' => $betaUser->id,
            'specialization' => 'Cardiology',
        ]);

        $byName = $this->actingAs($patientUser)->getJson('/api/v1/doctors?name=Alpha');
        $byName
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Neuro');

        $bySpecialization = $this->actingAs($patientUser)->getJson('/api/v1/doctors?specialization=Cardiology');
        $bySpecialization
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.specialization', 'Cardiology');

        $page = $this->actingAs($patientUser)->getJson('/api/v1/doctors?per_page=1&page=1');
        $page
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_patient_can_book_from_doctors_page_with_symptoms_only(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');

        $patientUser = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patientUser->id]);
        $doctor = Doctor::factory()->create();
        $slot = DoctorAvailabilitySlot::factory()->create([
            'doctor_id' => $doctor->id,
            'starts_at' => now()->addHours(4),
            'ends_at' => now()->addHours(4)->addMinutes(30),
        ]);

        $response = $this->actingAs($patientUser)->postJson('/api/v1/appointments', [
            'doctor_id' => $doctor->id,
            'doctor_availability_slot_id' => $slot->id,
            'symptoms' => 'Persistent migraines with light sensitivity for four days.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', AppointmentStatus::Pending->value)
            ->assertJsonPath('data.google_meet_url', null);
    }
}
