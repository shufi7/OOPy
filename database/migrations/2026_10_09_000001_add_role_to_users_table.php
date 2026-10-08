<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            throw new RuntimeException('users.role already exists; inspect its data before reconciling the migration.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->index();
        });
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', '!=', 'user')->exists()) {
            throw new RuntimeException('Export/reconcile privileged roles before rolling back users.role.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
