<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Meeting;
class TestLogJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       Meeting::create([
        'title' => 'Test Meeting',
        'description' => 'This is a test meeting',
        'start_date' => now(),
        'end_date' => now()->addHours(1),
        'user_id' => 1,
        'level' => 'info',
       ]);
    }
}
