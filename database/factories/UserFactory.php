<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        // Proper full birthdate
        $birthdate = fake()->dateTimeBetween('-99 years', '-13 years')->format('Y-m-d');

        // Random status
        $status = fake()->randomElement(['active', 'inactive']);

        return [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'display_name' => $firstName,
            'birthdate' => $birthdate,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'status' => $status,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
