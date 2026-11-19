<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_contents', function (Blueprint $table) {
            $table->char('draft_source_hash', 64)->nullable()->after('draft');
            $table->char('published_source_hash', 64)->nullable()->after('published');
        });
    }

    public function down(): void
    {
        Schema::table('material_contents', function (Blueprint $table) {
            $table->dropColumn(['draft_source_hash', 'published_source_hash']);
        });
    }
};
