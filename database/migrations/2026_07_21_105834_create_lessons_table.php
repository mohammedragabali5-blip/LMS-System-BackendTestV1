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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->string('video_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('attachments')->nullable();
            $table->string('storage_disk')->default('local');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('allow_attachments')->default(false);
            $table->boolean('allow_download')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};