<?php

namespace Database\Factories\Domain\Patients\Models;

use App\Domain\Patients\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    protected $model = PatientProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'date_of_birth' => fake()->dateTimeBetween('-75 years', '-18 years'),
            'medical_history' => fake()->optional()->paragraph(),
            'telegram_chat_id' => fake()->optional()->numerify('##########'),
        ];
    }
}
