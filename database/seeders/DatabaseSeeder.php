<?php

namespace Database\Seeders;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Services\DoctorAvailabilitySlotGenerator;
use App\Domain\Patients\Models\PatientProfile;
use App\Domain\Users\Data\DoctorOnboardingDefaults;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@healme.test',
        ]);

        $admin->forceFill([
            'password' => 'password',
        ])->save();

        $doctorUser = User::factory()->doctor()->create([
            'name' => 'Demo Doctor',
            'email' => 'doctor@healme.test',
        ]);

        $doctorUser->forceFill([
            'password' => 'password',
        ])->save();

        $doctor = Doctor::query()->firstOrCreate(
            ['user_id' => $doctorUser->id],
            array_merge(
                (new DoctorOnboardingDefaults)->toCreateAttributes(),
                [
                    'specialization' => 'General Practice',
                    'bio' => 'Demo doctor account for local sign-in and appointment approvals.',
                    'years_of_experience' => 12,
                    'rating' => 4.8,
                    'is_active' => true,
                    'is_verified' => true,
                ],
            ),
        );

        app(DoctorAvailabilitySlotGenerator::class)->generateForDoctor($doctor);

        User::factory()
            ->count(10)
            ->patient()
            ->create()
            ->each(fn (User $user) => PatientProfile::factory()->create(['user_id' => $user->id]));

        $this->call(DoctorSeeder::class);
    }
}
