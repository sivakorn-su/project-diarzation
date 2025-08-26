<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('transcript_segments', function (Blueprint $table) {
            $table->boolean('is_remove')->nullable()->after('tag');
        });
    }
   
    
    public function down(): void {
        Schema::table('transcript_segments', function (Blueprint $table) {
            $table->dropColumn('is_remove');
        });
    }
};
