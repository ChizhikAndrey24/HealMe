<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->roles()->exists()) {
                return;
            }

            $user->assignRole(UserRole::Patient);
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function doctor(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->roles()->detach();
            $user->assignRole(UserRole::Doctor);
        });
    }

    public function patient(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->roles()->detach();
            $user->assignRole(UserRole::Patient);
        });
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->roles()->detach();
            $user->assignRole(UserRole::SuperAdmin);
        });
    }
}
