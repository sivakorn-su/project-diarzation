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
            $table->unsignedBigInteger('transcript_id');
            $table->string('speaker')->nullable()->index();
            $table->string('filename')->nullable();
            $table->decimal('start', 10, 3)->nullable()->index();
            $table->decimal('end', 10, 3)->nullable()->index();
            $table->decimal('avg_probability', 6, 4)->nullable();
            $table->longText('text')->nullable();
            $table->longText('llm_corrected_text')->nullable();
            $table->timestamps();

            $table->foreign('transcript_id')->references('id')->on('transcripts')->onDelete('cascade');
            $table->index(['transcript_id','start']);
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
