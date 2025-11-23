<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'email' => 'test@example.com',
        //     'password' => '12345678',
        // ]);

        $this->call([
        UserSeeder::class,
        CoffeeSeeder::class,
        InteractionSeeder::class,
        ]);
    }

}
