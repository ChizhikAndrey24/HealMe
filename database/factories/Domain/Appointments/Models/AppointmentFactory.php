<?php

namespace Database\Factories\Domain\Appointments\Models;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use App\Domain\Patients\Models\PatientProfile;
use App\Enums\AppointmentStatus;
use App\Enums\UrgencyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $doctor = Doctor::factory();
        $slot = DoctorAvailabilitySlot::factory();

        return [
            'patient_profile_id' => PatientProfile::factory(),
            'doctor_id' => $doctor,
            'doctor_availability_slot_id' => $slot,
            'symptoms' => fake()->sentence(),
            'chief_complaint' => fake()->words(3, true),
            'clinical_summary' => fake()->paragraph(),
            'urgency_level' => fake()->randomElement(UrgencyLevel::cases()),
            'status' => AppointmentStatus::Pending,
            'google_meet_url' => fake()->optional()->url(),
        ];
    }
}
