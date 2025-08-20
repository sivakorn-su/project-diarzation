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
        Schema::table('transcript_segments', function (Blueprint $table) {
            $table->float('confidence')->nullable()->after('avg_probability');
            $table->string('tag')->nullable()->after('confidence');
            $table->string('remove_reason')->nullable()->after('tag');
            $table->boolean('has_overlap')->default(false)->after('remove_reason');
            $table->float('overlap_ratio')->nullable()->after('has_overlap');
            $table->json('overlap_intervals')->nullable()->after('overlap_ratio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transcript_segments', function (Blueprint $table) {
            $table->dropColumn([
                'confidence',
                'tag',
                'remove_reason',
                'has_overlap',
                'overlap_ratio',
                'overlap_intervals',
            ]);
        });
    }
};
