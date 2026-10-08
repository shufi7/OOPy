<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['in_progress', 'completed', 'expired'])->default('in_progress');
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['id', 'quiz_id'], 'attempts_id_quiz_unique');
            $table->index(['user_id', 'quiz_id', 'started_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_attempts')->exists()) {
            throw new RuntimeException('Export quiz attempts before rolling back quiz_attempts.');
        }
        Schema::dropIfExists('quiz_attempts');
    }
};
