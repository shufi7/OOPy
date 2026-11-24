<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', fn (Blueprint $table) => $table->json('transcript_snapshot')->nullable());
        Schema::table('certificate_orders', fn (Blueprint $table) => $table->string('participant_name', 100)->nullable());
        Schema::table('certificate_payment_attempts', function (Blueprint $table) {
            $table->string('participant_name', 100)->nullable();
            $table->json('academic_baseline')->nullable();
        });
        Schema::create('certificate_identities', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('participant_name', 100);
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('certificate_identities')->exists()
            || DB::table('certificates')->whereNotNull('transcript_snapshot')->exists()
            || DB::table('certificate_orders')->whereNotNull('participant_name')->exists()
            || DB::table('certificate_payment_attempts')->whereNotNull('participant_name')->orWhereNotNull('academic_baseline')->exists()) {
            throw new RuntimeException('Export and review certificate identity and transcript records before rollback.');
        }

        Schema::dropIfExists('certificate_identities');
        Schema::table('certificate_payment_attempts', fn (Blueprint $table) => $table->dropColumn(['participant_name', 'academic_baseline']));
        Schema::table('certificate_orders', fn (Blueprint $table) => $table->dropColumn('participant_name'));
        Schema::table('certificates', fn (Blueprint $table) => $table->dropColumn('transcript_snapshot'));
    }
};
