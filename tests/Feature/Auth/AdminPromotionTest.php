<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_account_is_promoted_only_after_confirmation_and_password_is_preserved(): void
    {
        $user = User::factory()->create();
        $password = $user->password;
        $this->artisan('oopy:promote-admin', ['email' => $user->email])
            ->expectsConfirmation('Promosikan akun ini menjadi admin?', 'yes')->assertSuccessful();
        $this->assertSame('admin', $user->fresh()->role);
        $this->assertSame($password, $user->fresh()->password);
        $this->artisan('oopy:promote-admin', ['email' => $user->email])->assertSuccessful();
    }

    public function test_declined_confirmation_does_not_change_role(): void
    {
        $user = User::factory()->create();
        $this->artisan('oopy:promote-admin', ['email' => $user->email])
            ->expectsConfirmation('Promosikan akun ini menjadi admin?', 'no')->assertFailed();
        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_invalid_or_missing_account_is_rejected_and_no_account_is_created(): void
    {
        $this->artisan('oopy:promote-admin', ['email' => 'invalid'])->assertFailed();
        $this->artisan('oopy:promote-admin', ['email' => 'absent@example.test'])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
