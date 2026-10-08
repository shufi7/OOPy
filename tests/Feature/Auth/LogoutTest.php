<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_session_rotates_token_and_removes_authentication(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
        $this->withSession(['private-marker' => 'remove-me']);
        $oldId = session()->getId();
        $oldToken = session()->token();
        session()->save();

        $this->withCookie(config('session.cookie'), $oldId)->post('/logout')
            ->assertRedirect('/')->assertSessionMissing('private-marker');
        $this->assertGuest('web');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertSame('', session()->getHandler()->read($oldId));
        Auth::forgetGuards();
        // Replay the previous authenticated cookie; its server-side session is gone.
        $this->get('/')->assertOk()->assertSee('href="'.route('login').'"', false)->assertDontSee('oopyAccountMenu');
        $this->assertGuest('web');
    }

    public function test_logout_is_post_only_and_requires_authentication(): void
    {
        $this->get('/logout')->assertStatus(405);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->actingAs(User::factory()->create())->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
    }

    private function enforceCsrf(): void
    {
        // Laravel bypasses CSRF in tests by default; exercise the real verifier here.
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
    }

    public function test_logout_rejects_missing_and_invalid_csrf_but_accepts_matching_token(): void
    {
        $this->enforceCsrf();
        $this->actingAs(User::factory()->create())->withSession(['_token' => 'valid-token']);
        $this->post('/logout')->assertStatus(419);
        $this->post('/logout', ['_token' => 'wrong-token'])->assertStatus(419);
        $this->assertAuthenticated();
        $this->post('/logout', ['_token' => 'valid-token'])->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_registration_and_login_also_require_csrf(): void
    {
        $this->enforceCsrf();
        $this->post('/register', [])->assertStatus(419);
        $this->post('/login', [])->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
