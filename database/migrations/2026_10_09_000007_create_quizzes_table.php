<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('chapter_id')->constrained()->restrictOnDelete();
            $table->string('slug', 191)->unique();
            $table->string('title');
            $table->enum('type', ['chapter_quiz', 'final_exam']);
            $table->decimal('passing_score', 5, 2)->default(80);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
            $table->index(['chapter_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
