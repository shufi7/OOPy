<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_submissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->longText('code');
            $table->longText('output')->nullable();
            $table->enum('status', ['submitted', 'passed', 'failed', 'error'])->default('submitted');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['user_id', 'exercise_id', 'submitted_at'], 'submissions_user_exercise_time_index');
        });
    }

    public function down(): void
    {
        if (DB::table('exercise_submissions')->exists()) {
            throw new RuntimeException('Export coding submissions before rolling back exercise_submissions.');
        }
        Schema::dropIfExists('exercise_submissions');
    }
};
