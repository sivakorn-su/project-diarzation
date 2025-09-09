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
            // เก็บ key สั้น ๆ เช่น "media/videos/<uuid>.mp4"
            if (!Schema::hasColumn('meeting_infos', 'media_object_key')) {
                $table->string('media_object_key', 512)->nullable()->after('media_paths');
            }
            // กันอนาคต ถ้ายังอยากเก็บ URL ด้วย → ขยายเป็น TEXT
            $table->text('media_paths')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_infos', function (Blueprint $table) {
            // ย้อนกลับถ้าจำเป็น
            $table->string('media_paths', 255)->nullable()->change();
            $table->dropColumn('media_object_key');
        });
    }
};
