<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_local_admin_is_hashed_and_can_login_and_open_the_admin_panel(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->seed(AdminSeeder::class);
        $this->app->detectEnvironment(fn () => 'testing');
        $admin = User::where('email', 'admin@gmail.com')->sole();

        $this->assertSame('admin', $admin->role);
        $this->assertNotSame('admin123', $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check('admin123', $admin->password));
        $this->post('/login', ['email' => 'admin@gmail.com', 'password' => 'admin123'])->assertRedirect('/materi');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin')->assertOk()->assertSee('Dashboard');
    }

    public function test_rerunning_the_seeder_preserves_an_existing_admin_and_changed_password(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = User::where('email', 'admin@gmail.com')->sole();
        $admin->name = 'Administrator Teman';
        $admin->password = 'password-baru-teman';
        $admin->save();
        $attributes = $admin->getRawOriginal();

        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $admin->fresh()->getRawOriginal());
        $this->assertTrue(Hash::check('password-baru-teman', $admin->fresh()->password));
    }

    public function test_an_existing_regular_account_is_not_promoted_or_overwritten(): void
    {
        $user = User::factory()->create(['email' => 'admin@gmail.com']);
        $attributes = $user->fresh()->getRawOriginal();

        try {
            $this->seed(AdminSeeder::class);
            $this->fail('A regular account must not be silently promoted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sudah dipakai pengguna biasa', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $user->fresh()->getRawOriginal());
    }

    public function test_default_admin_credentials_cannot_be_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $this->app->make(AdminSeeder::class)->run();
            $this->fail('Development credentials must not be created in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('hanya untuk pengembangan lokal', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
