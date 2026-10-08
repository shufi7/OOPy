<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->enum('question_type', ['multiple_choice', 'code_fill', 'essay']);
            $table->text('question_text');
            $table->longText('code_snippet')->nullable();
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('question_order');
            $table->timestamps();
            $table->unique(['quiz_id', 'question_order']);
            $table->unique(['id', 'quiz_id'], 'questions_id_quiz_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
