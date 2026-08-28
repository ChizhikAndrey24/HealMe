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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMeetLinkGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.meet_driver' => 'google',
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.meet_refresh_token' => 'app-meet-refresh-token',
        ]);
        Cache::flush();
    }

    public function test_approve_creates_open_google_meet_with_app_token(): void
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

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'app-access-token',
                'expires_in' => 3600,
            ], 200),
            'meet.googleapis.com/v2/spaces' => Http::response([
                'meetingUri' => 'https://meet.google.com/abc-defg-hij',
            ], 200),
        ]);

        $response = $this->actingAs($doctorUser)->postJson(
            "/api/v1/doctor/appointments/{$appointment->id}/approve",
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', AppointmentStatus::Confirmed->value)
            ->assertJsonPath('data.google_meet_url', 'https://meet.google.com/abc-defg-hij');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://meet.googleapis.com/v2/spaces'
                && $request->hasHeader('Authorization', 'Bearer app-access-token')
                && ($request['config']['accessType'] ?? null) === 'OPEN';
        });
    }

    public function test_approve_fails_when_app_meet_token_missing(): void
    {
        config(['services.google.meet_refresh_token' => null]);

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
            "/api/v1/doctor/appointments/{$appointment->id}/approve",
        );

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Google Meet is not configured. Set GOOGLE_MEET_REFRESH_TOKEN in the environment.',
            );
    }
}
