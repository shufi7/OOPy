<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LearningMigrationLifecycleTest extends TestCase
{
    public function test_learning_migrations_rollback_and_reapply_without_losing_existing_users(): void
    {
        // This lifecycle check only touches a brand new, isolated in-memory database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        try {
            $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertSuccessful();
            $user = User::factory()->create();
            $password = $user->getRawOriginal('password');
            // Unlike the legacy fixed-count test, include newly added learning migrations.
            $steps = DB::table('migrations')->where('migration', 'like', '2026_%')->count();
            $this->assertGreaterThan(12, $steps);
            $this->artisan('migrate:rollback', ['--database' => 'sqlite', '--step' => $steps, '--force' => true])->assertSuccessful();
            $this->assertTrue(Schema::hasTable('users'));
            $this->assertFalse(Schema::hasColumn('users', 'role'));
            $this->assertFalse(Schema::hasTable('chapters'));
            $this->assertSame($password, DB::table('users')->where('id', $user->id)->value('password'));
            $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertSuccessful();
            $this->assertSame('user', $user->fresh()->role);
            $this->assertTrue(Schema::hasColumn('questions', 'publication_status'));
            $this->assertTrue(Schema::hasTable('quiz_attempt_questions'));
        } finally {
            DB::disconnect('sqlite');
        }
    }
}
