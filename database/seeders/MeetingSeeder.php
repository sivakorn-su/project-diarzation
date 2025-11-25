<?php

namespace Database\Seeders;

use App\Models\MeetingInfo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Meeting;
class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // สร้างเฉพาะ Meeting ไม่มี MeetingInfo
        // MeetingInfo และ TranscriptSegments จะถูกสร้างโดย MeetingInfoSeeder
        Meeting::factory()
            ->count(10)
            ->create();

        $this->command->info('Created 10 meetings');
    }
}
