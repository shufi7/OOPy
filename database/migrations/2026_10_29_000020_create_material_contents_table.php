<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->unique()->constrained()->restrictOnDelete();
            $table->string('draft_title');
            $table->json('draft');
            $table->string('published_title')->nullable();
            $table->json('published')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_contents');
    }
};
