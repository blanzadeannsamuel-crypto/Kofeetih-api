<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoffeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('coffees')->insert([
            [
                'coffee_name' => 'Espresso',
                'image_url' => 'espresso.jpg',
                'description' => 'A strong and bold coffee shot with intense flavor.',
                'ingredients' => 'Finely ground coffee beans',
                'coffee_type' => 'strong',
                'lactose' => 'no',
                'minimum_price' => 80.00,
                'maximum_price' => 120.00,
                'rating' => 5.0,
                'likes' => 25,
                'favorites' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'coffee_name' => 'Cappuccino',
                'image_url' => 'cappuccino.jpg',
                'description' => 'Perfect balance of espresso, steamed milk, and foamed milk.',
                'ingredients' => 'Espresso, steamed milk, milk foam',
                'coffee_type' => 'balanced',
                'lactose' => 'yes',
                'minimum_price' => 120.00,
                'maximum_price' => 180.00,
                'rating' => 4.6,
                'likes' => 40,
                'favorites' => 22,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'coffee_name' => 'Cafe Mocha',
                'image_url' => 'mocha.jpg',
                'description' => 'A delicious combination of chocolate, espresso, and milk.',
                'ingredients' => 'Espresso, chocolate syrup, steamed milk, whipped cream',
                'coffee_type' => 'sweet',
                'lactose' => 'yes',
                'minimum_price' => 140.00,
                'maximum_price' => 220.00,
                'rating' => 5.0,
                'likes' => 55,
                'favorites' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'coffee_name' => 'Americano',
                'image_url' => 'americano.jpg',
                'description' => 'Espresso diluted with hot water. Smooth but strong.',
                'ingredients' => 'Espresso, hot water',
                'coffee_type' => 'strong',
                'lactose' => 'no',
                'minimum_price' => 90.00,
                'maximum_price' => 130.00,
                'rating' => 3,
                'likes' => 10,
                'favorites' => 5.0,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'coffee_name' => 'Caramel Latte',
                'image_url' => 'caramel-latte.jpg',
                'description' => 'A sweet and creamy latte topped with caramel drizzle.',
                'ingredients' => 'Espresso, steamed milk, caramel syrup',
                'coffee_type' => 'sweet',
                'lactose' => 'yes',
                'minimum_price' => 150.00,
                'maximum_price' => 230.00,
                'rating' => 4.7,
                'likes' => 38,
                'favorites' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
