<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('chapter_order')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
