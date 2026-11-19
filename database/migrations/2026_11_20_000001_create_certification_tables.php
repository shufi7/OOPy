<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_key', 80);
            $table->string('order_number', 64)->unique();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('IDR');
            $table->string('payment_method', 32);
            $table->string('mode', 16)->default('simulation');
            $table->string('status', 32)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'program_key']);
        });
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_key', 80);
            $table->uuid('public_id')->unique();
            $table->string('certificate_number', 64)->unique();
            $table->string('participant_name');
            $table->string('course_title');
            $table->dateTime('completed_at');
            $table->dateTime('issued_at');
            $table->foreignId('order_id')->unique()->constrained('certificate_orders')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamps();
            // Retain revoked records as well: reissuing needs an explicit future policy.
            $table->unique(['user_id', 'program_key']);
        });
    }

    public function down(): void
    {
        if (DB::table('certificates')->exists() || DB::table('certificate_orders')->exists()) {
            throw new RuntimeException('Export and review certification records before rolling back these tables.');
        }
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_orders');
    }
};
