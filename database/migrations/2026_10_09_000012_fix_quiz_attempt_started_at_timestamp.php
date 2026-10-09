<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // An explicit DEFAULT suppresses legacy MariaDB's implicit ON UPDATE
            // on the first non-null TIMESTAMP. Never change the stored start time.
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->timestamp('started_at')->useCurrent()->change();
            });
        }
    }

    public function down(): void
    {
        // Keep the corrected definition: restoring implicit ON UPDATE would
        // rewrite start times and break the completion integrity constraint.
    }
};
