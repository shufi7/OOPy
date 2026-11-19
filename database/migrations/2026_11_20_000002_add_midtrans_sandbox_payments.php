<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_orders', function (Blueprint $table) {
            $table->string('provider', 24)->default('simulation');
            $table->dateTime('eligible_completed_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->string('paid_provider_order', 50)->nullable();
        });
        Schema::create('certificate_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('certificate_order_id')->constrained('certificate_orders')->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->string('provider_transaction_id', 100)->nullable()->index();
            $table->string('merchant_id', 100)->nullable();
            $table->string('status', 32)->default('creating');
            $table->string('payment_method', 32)->nullable();
            $table->string('fraud_status', 24)->nullable();
            $table->text('snap_token')->nullable();
            $table->text('redirect_url')->nullable();
            $table->dateTime('request_started_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
            $table->index(['certificate_order_id', 'created_at'], 'certificate_attempt_order_created_index');
        });
    }

    public function down(): void
    {
        if (DB::table('certificate_payment_attempts')->exists() || DB::table('certificate_orders')->where('provider', 'midtrans')->exists()) {
            throw new RuntimeException('Export and review Sandbox payment records before rollback.');
        }
        Schema::dropIfExists('certificate_payment_attempts');
        Schema::table('certificate_orders', fn (Blueprint $table) => $table->dropColumn([
            'provider', 'eligible_completed_at', 'paid_at', 'verified_at', 'paid_provider_order',
        ]));
    }
};
