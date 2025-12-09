<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an admin user
        User::create([
            'last_name' => 'blanza',
            'first_name' => 'deann samuel',
            'display_name' => 'sam',
            'birthdate' => '2006-05-03',
            'email' => 'sam@gmail.com',
            'password' => Hash::make('12345678'), // change to secure password
            'role' => 'admin',
        ],
        [
            'last_name' => 'quilang',
            'first_name' => 'john kenedy',
            'display_name' => 'ken',
            'birthdate' => '2005-04-24',
            'email' => 'ken@gmail.com',
            'password' => Hash::make('12345678'), // change to secure password
            'role' => 'user',
        ]);

        // Create 20 normal users using the factory
        User::factory()->count(1234)->create();
    }
}
