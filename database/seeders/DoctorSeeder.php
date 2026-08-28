<?php

namespace Database\Seeders;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Services\DoctorAvailabilitySlotGenerator;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(DoctorAvailabilitySlotGenerator::class);

        Doctor::factory()
            ->count(500)
            ->create()
            ->each(function (Doctor $doctor) use ($generator): void {
                $generator->generateForDoctor($doctor);
            });
    }
}
