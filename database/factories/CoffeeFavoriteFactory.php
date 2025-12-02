<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Main\Coffee;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Main\Favorite>
 */
class CoffeeFavoriteFactory extends Factory
{
    protected static array $existingPairs = [];

    public function definition(): array
    {
        do {
            $userId = User::inRandomOrder()->first()->id;
            $coffeeId = Coffee::inRandomOrder()->first()->coffee_id; // <- use coffee_id
            $pairKey = $userId . '-' . $coffeeId;
        } while (in_array($pairKey, static::$existingPairs));

        static::$existingPairs[] = $pairKey;

        return [
            'user_id' => $userId,
            'coffee_id' => $coffeeId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
