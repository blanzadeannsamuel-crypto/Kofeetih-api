<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Main\Coffee;
use App\Models\Main\Like;
use App\Models\Main\Favorite;
use App\Models\Main\CoffeeRating;
use Faker\Factory as Faker;
use Carbon\Carbon;

class InteractionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $coffees = Coffee::all();

        foreach (User::all() as $user) {

            // --------------------
            // Likes
            // --------------------
            $likeCount = rand(1, min(5, $coffees->count()));
            $randomCoffees = $coffees->random($likeCount);
            foreach ($randomCoffees as $coffee) {
                // Prevent duplicate likes
                Like::firstOrCreate(
                    ['user_id' => $user->id, 'coffee_id' => $coffee->coffee_id],
                    ['created_at' => $this->randomDate($faker), 'updated_at' => $this->randomDate($faker)]
                );
            }

            // --------------------
            // Favorites
            // --------------------
            $favCount = rand(1, min(3, $coffees->count()));
            $randomCoffees = $coffees->random($favCount);
            foreach ($randomCoffees as $coffee) {
                // Prevent duplicate favorites
                Favorite::firstOrCreate(
                    ['user_id' => $user->id, 'coffee_id' => $coffee->coffee_id],
                    ['created_at' => $this->randomDate($faker), 'updated_at' => $this->randomDate($faker)]
                );
            }

            // --------------------
            // Ratings
            // --------------------
            $ratingCount = rand(1, min(4, $coffees->count()));
            $randomCoffees = $coffees->random($ratingCount);
            foreach ($randomCoffees as $coffee) {
                if (rand(1, 100) <= 70) { // 70% chance user rates it
                    // Prevent duplicate ratings
                    CoffeeRating::updateOrCreate(
                        ['user_id' => $user->id, 'coffee_id' => $coffee->coffee_id],
                        ['rating' => rand(1, 5), 'created_at' => $this->randomDate($faker), 'updated_at' => $this->randomDate($faker)]
                    );
                }
            }
        }
    }

    private function randomDate($faker)
    {
        $year = $faker->numberBetween(2020, 2025);
        $month = $faker->numberBetween(1, 12);
        $day = $faker->numberBetween(1, 28);

        return Carbon::create(
            $year,
            $month,
            $day,
            $faker->numberBetween(0, 23),
            $faker->numberBetween(0, 59),
            0
        );
    }
}
