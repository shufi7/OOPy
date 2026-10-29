<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_exam_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('in_progress');
            $table->json('snapshot');
            $table->json('answers');
            $table->json('essay_scores')->nullable();
            $table->json('essay_feedback')->nullable();
            $table->decimal('objective_score', 6, 2)->nullable();
            $table->decimal('objective_points', 6, 2)->nullable();
            $table->decimal('essay_score', 6, 2)->nullable();
            $table->decimal('essay_points', 6, 2)->nullable();
            $table->decimal('total_score', 6, 2)->nullable();
            $table->decimal('passing_score', 6, 2);
            $table->unsignedInteger('duration_seconds');
            $table->timestamp('started_at')->useCurrent();
            // DATETIME avoids MariaDB's implicit zero TIMESTAMP default on a
            // required second timestamp column under strict SQL modes.
            $table->dateTime('deadline_at');
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('timed_out')->default(false);
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        if (DB::table('final_exam_attempts')->exists()) {
            throw new RuntimeException('Export final evaluation records before rolling back this table.');
        }
        Schema::dropIfExists('final_exam_attempts');
    }
};
