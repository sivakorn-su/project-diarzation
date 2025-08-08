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
        Schema::create('transcripts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('media_path')->nullable();      // public URL หรือ local URL
            $table->string('storage_driver')->nullable();  // public|supabase
            $table->enum('status', ['pending','processing','done','failed'])->default('pending');
            $table->json('transcript_json')->nullable();   // เก็บ JSON ทั้งก้อน (backup / debug)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
