<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('publication_status', 16)->default('published')->index();
            $table->unsignedInteger('display_order')->nullable();
            // Metadata only: originals are retained; avoid rebuilding the live questions table.
            $table->unsignedBigInteger('revision_of_id')->nullable();
            $table->text('essay_rubric')->nullable();
        });
        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->unique(['attempt_id', 'question_id']);
            $table->unique(['attempt_id', 'position']);
        });

        // Pin the current question set for attempts created before publication existed.
        DB::table('quiz_attempts')->orderBy('id')->chunkById(100, function ($attempts) {
            foreach ($attempts as $attempt) {
                $questions = DB::table('questions')->where('quiz_id', $attempt->quiz_id)->orderBy('question_order')->pluck('id');
                foreach ($questions as $position => $id) {
                    DB::table('quiz_attempt_questions')->insert(['attempt_id' => $attempt->id, 'question_id' => $id, 'position' => $position + 1]);
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_attempt_questions')->exists() || DB::table('questions')->where('publication_status', '!=', 'published')->exists()) {
            throw new RuntimeException('Export question revisions and attempt versions before rollback.');
        }
        Schema::dropIfExists('quiz_attempt_questions');
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['publication_status']);
            $table->dropColumn(['publication_status', 'display_order', 'revision_of_id', 'essay_rubric']);
        });
    }
};
