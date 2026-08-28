<?php

namespace Database\Factories\Domain\Doctors\Models;

use App\Domain\Doctors\Models\Doctor;
use App\Domain\Doctors\Models\DoctorAvailabilitySlot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorAvailabilitySlot>
 */
class DoctorAvailabilitySlotFactory extends Factory
{
    protected $model = DoctorAvailabilitySlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::now()
            ->addDays(fake()->numberBetween(0, 364))
            ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'doctor_id' => Doctor::factory(),
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'is_booked' => false,
        ];
    }
}
