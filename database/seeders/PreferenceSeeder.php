<?php

namespace Database\Seeders;

use App\Models\Main\Preference;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        foreach (range(1, 1000) as $userId) {

            // Random single date for created_at
            $createdAt = Carbon::create(
                2025, // or randomize the year if needed
                $faker->numberBetween(1, 12),
                $faker->numberBetween(1, 28),
                $faker->numberBetween(0, 23),
                $faker->numberBetween(0, 59),
                0
            );

            Preference::create([
                'user_id' => $userId,
                'coffee_type' => $faker->randomElement(['strong', 'balanced', 'sweet']),
                'coffee_allowance' => $faker->numberBetween(200, 2500),
                'serving_temp' => $faker->randomElement(['hot', 'cold', 'both']),
                'lactose' => $faker->boolean(),
                'nuts_allergy' => $faker->boolean(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
