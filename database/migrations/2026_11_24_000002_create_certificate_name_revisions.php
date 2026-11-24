<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_name_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->string('participant_name', 100);
            $table->dateTime('created_at');
            $table->index(['certificate_id', 'id']);
        });
    }

    public function down(): void
    {
        if (DB::table('certificate_name_revisions')->exists()) {
            throw new RuntimeException('Export and review certificate name revisions before rollback.');
        }

        Schema::dropIfExists('certificate_name_revisions');
    }
};
