<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoffeeFact;

class CoffeeFactSeeder extends Seeder
{
    public function run(): void
    {
        $facts = [
            "Coffee is the second most traded commodity in the world.",
            "Espresso has less caffeine than a regular cup of coffee.",
            "Coffee beans are actually seeds of berries.",
            "Finland drinks the most coffee per person in the world.",
            "Decaf coffee still contains a small amount of caffeine.",
            "The world's most expensive coffee comes from elephant poop!",
            "Coffee was originally chewed, not brewed.",
            "Instant coffee was invented in 1901 by a Japanese-American chemist.",
            "Coffee can help improve physical performance by increasing adrenaline levels.",
            "There are over 100 species of coffee, but only two are widely cultivated: Arabica and Robusta."
        ];

        foreach ($facts as $fact) {
            CoffeeFact::create(['fact' => $fact]);
        }
    }
}
