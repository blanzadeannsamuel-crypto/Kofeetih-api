<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Main\Coffee;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoffeeRating>
 */
class CoffeeRatingFactory extends Factory
{
    protected static array $existingPairs = [];

    public function definition(): array
    {
        do {
            $userId = User::inRandomOrder()->first()->id;
            $coffeeId = Coffee::inRandomOrder()->first()->id;
            $pairKey = $userId . '-' . $coffeeId;
        } while (in_array($pairKey, static::$existingPairs));

        static::$existingPairs[] = $pairKey;

        return [
            'user_id' => $userId,
            'coffee_id' => $coffeeId,
            'rating' => $this->faker->numberBetween(1, 5),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
