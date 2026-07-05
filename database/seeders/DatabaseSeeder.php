<?php

namespace database\Seeders;

use App\Models\User;
use Illuminate\database\Console\Seeds\WithoutModelEvents;
use Illuminate\database\Seeder;

class databaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
