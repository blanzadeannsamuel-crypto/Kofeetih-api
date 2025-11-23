<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Main\Coffee;

class InteractionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all existing coffees
        $coffees = Coffee::all();

        // Create 300 users
        $users = User::factory(300)->create();

        foreach ($users as $user) {
            // --------------------
            // Likes
            // --------------------
            $randomCoffees = $coffees->random(rand(1, 5));
            foreach ($randomCoffees as $coffee) {
                $user->likedCoffees()->syncWithoutDetaching([$coffee->id]);
            }

            // --------------------
            // Favorites
            // --------------------
            $randomCoffees = $coffees->random(rand(1, 3));
            foreach ($randomCoffees as $coffee) {
                $user->favoritedCoffees()->syncWithoutDetaching([$coffee->id]);
            }

            // --------------------
            // Ratings (some may skip)
            // --------------------
            $randomCoffees = $coffees->random(rand(1, 4));
            foreach ($randomCoffees as $coffee) {
                // 70% chance the user rates this coffee
                if (rand(1, 100) <= 70) {
                    $coffee->ratings()->create([
                        'user_id' => $user->id,
                        'rating' => rand(1, 5),
                    ]);
                }
            }
        }
    }
}
