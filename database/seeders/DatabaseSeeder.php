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
        // สร้าง Users
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password'
        ]);

        $teslaUser = User::factory()->create([
            'name' => 'Tesla',
            'email' => 'tesla@gmail.com',
            'password' => 'password'
        ]);

        $this->command->info('Created 2 users');

        // สร้าง Meetings (จะสร้าง MeetingInfo แบบ inline ผ่าน factory)
        $this->call(MeetingSeeder::class);

        // สร้าง MeetingInfo และ TranscriptSegments สำหรับแต่ละ Meeting
        $this->call(MeetingInfoSeeder::class);
    }
}
