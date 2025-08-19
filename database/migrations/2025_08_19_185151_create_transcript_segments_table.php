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
        Schema::create('transcript_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_info_id')->constrained('meeting_infos')->cascadeOnDelete();

            $table->unsignedInteger('idx')->default(0);
            $table->decimal('start', 10, 3)->nullable();
            $table->decimal('end', 10, 3)->nullable();

            $table->text('text')->nullable();
            $table->text('llm_corrected_text')->nullable();

            $table->string('speaker', 64)->nullable();
            $table->string('filename')->nullable();
            $table->decimal('avg_probability', 6, 4)->nullable();

            $table->timestamps();

            $table->index(['meeting_info_id', 'idx']);
            $table->index(['meeting_info_id', 'speaker']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcript_segments');
    }
};
