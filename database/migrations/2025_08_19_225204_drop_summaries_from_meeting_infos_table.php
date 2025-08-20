<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meeting_infos', function (Blueprint $table) {
            if (Schema::hasColumn('meeting_infos', 'summaries')) {
                $table->dropColumn('summaries');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_infos', function (Blueprint $table) {
            $table->json('summaries')->nullable();
        });
    }
};
