<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'material_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        if (DB::table('user_progress')->exists()) {
            throw new RuntimeException('Export learning progress before rolling back user_progress.');
        }
        Schema::dropIfExists('user_progress');
    }
};
