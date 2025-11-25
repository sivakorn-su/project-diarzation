<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeetingInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetings = \App\Models\Meeting::all();

        if ($meetings->isEmpty()) {
            $this->command->warn('No meetings found. Please run MeetingSeeder first.');
            return;
        }

        foreach ($meetings as $meeting) {
            // สร้าง MeetingInfo
            $meetingInfo = \App\Models\MeetingInfo::factory()->create([
                'meeting_id' => $meeting->id,
            ]);

            // ถ้า status เป็น 'done' ให้สร้าง transcript segments ด้วย
            if ($meetingInfo->status === 'done') {
                $segmentCount = rand(5, 15);

                for ($i = 0; $i < $segmentCount; $i++) {
                    \App\Models\TranscriptSegments::factory()->create([
                        'meeting_info_id' => $meetingInfo->id,
                        'idx' => $i,
                    ]);
                }

                $this->command->info("Created MeetingInfo #{$meetingInfo->id} (done) with {$segmentCount} segments");
            } else {
                $this->command->info("Created MeetingInfo #{$meetingInfo->id} ({$meetingInfo->status})");
            }
        }
    }
}
