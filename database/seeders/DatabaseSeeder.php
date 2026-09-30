<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Membuat/Memastikan User Default dengan ID = 1 selalu ada
        User::firstOrCreate(
            ['id' => 1],
            [
                'name'     => 'Default User',
                'email'    => 'user@example.com',
                'password' => Hash::make('password123'),
            ]
        );
    }
}