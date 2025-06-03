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
        Meeting::factory()
            ->has(MeetingInfo::factory(),'info')
            ->count(10)
            ->create();
    }
}
