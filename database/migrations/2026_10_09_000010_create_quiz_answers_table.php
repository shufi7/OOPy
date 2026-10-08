<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('attempt_id');
            $table->foreignId('question_id');
            // Shared quiz scope lets the database reject cross-quiz answers.
            $table->foreignId('quiz_id');
            $table->longText('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
            $table->foreign(['attempt_id', 'quiz_id'], 'answers_attempt_quiz_foreign')
                ->references(['id', 'quiz_id'])->on('quiz_attempts')->cascadeOnDelete();
            $table->foreign(['question_id', 'quiz_id'], 'answers_question_quiz_foreign')
                ->references(['id', 'quiz_id'])->on('questions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_answers')->exists()) {
            throw new RuntimeException('Export quiz answers before rolling back quiz_answers.');
        }
        Schema::dropIfExists('quiz_answers');
    }
};
