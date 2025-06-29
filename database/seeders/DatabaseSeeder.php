<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test',
            'email' => 'test@gmail.com',
            'password'=>'password'
        ]);
        User::factory()->create([
            'name' => 'Tesla',
            'email' => 'tesla@gmail.com',
            'password'=>'password'
        ]);
        $this->call(MeetingSeeder::class);
    }
}
