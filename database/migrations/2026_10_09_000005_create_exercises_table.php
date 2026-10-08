<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->string('exercise_key', 191)->unique();
            $table->string('title');
            $table->text('instruction');
            $table->longText('starter_code');
            $table->longText('expected_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
