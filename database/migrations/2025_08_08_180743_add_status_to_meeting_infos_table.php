<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    
    public function up(): void
    {
        Schema::table('meeting_infos', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])
                  ->default('pending')
                  ->after('media_paths');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_infos', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};