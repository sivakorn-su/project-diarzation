<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MigrateTagStringToBooleanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        // Mapping
        DB::table('transcript_segments')->where('tag', 'use')->update(['is_remove' => false]);
        DB::table('transcript_segments')->where('tag', 'remove')->update(['is_remove' => true]);

        DB::table('transcript_segments')->where('tag', null)->update(['is_remove' => false]);
    }
}
