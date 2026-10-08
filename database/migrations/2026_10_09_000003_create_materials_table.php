<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('chapter_id')->constrained()->restrictOnDelete();
            $table->string('slug', 191);
            $table->string('title');
            $table->longText('content')->nullable();
            $table->unsignedInteger('material_order')->default(1);
            $table->timestamps();
            $table->unique(['chapter_id', 'slug']);
            $table->index(['chapter_id', 'material_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
