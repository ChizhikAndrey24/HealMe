<?php

namespace Database\Factories\Domain\Doctors\Models;

use App\Domain\Doctors\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'specialization' => fake()->randomElement([
                'Cardiology',
                'Dermatology',
                'General Practice',
                'Neurology',
                'Pediatrics',
            ]),
            'bio' => fake()->paragraph(),
            'years_of_experience' => fake()->numberBetween(2, 25),
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'is_active' => true,
            'is_verified' => true,
            'meeting_provider' => 'google_meet',
        ];
    }
}
