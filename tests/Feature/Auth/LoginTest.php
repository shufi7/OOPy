<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_authenticated_user_is_redirected(): void
    {
        $this->get('/login')->assertOk()->assertSee('Selamat Datang')
            ->assertSee('autocomplete="current-password"', false);
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/materi');
        $this->post('/login', [])->assertRedirect('/materi');
    }

    public function test_valid_credentials_authenticate_and_regenerate_session(): void
    {
        $user = User::factory()->create();
        $this->withSession(['marker' => 'preserved']);
        $oldId = session()->getId();
        $oldToken = session()->token();
        session()->save();

        $this->withCookie(config('session.cookie'), $oldId)
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/materi')->assertSessionHas('marker', 'preserved');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
    }

    public function test_wrong_password_and_unknown_email_have_the_same_generic_error(): void
    {
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.test'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'salah123'])
                ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.'])
                ->assertSessionHasInput('email', $email);
            $this->assertGuest();
            $this->assertArrayNotHasKey('password', session()->getOldInput());
            $this->get('/login')->assertSee('value="'.$email.'"', false)->assertDontSee('salah123');
        }
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->post('/login', ['email' => 'invalid', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_returns_to_the_internal_intended_url(): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => url('/editor?mode=python')])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/editor?mode=python')->assertSessionMissing('url.intended');
    }

    public function test_unsafe_intended_urls_are_discarded(): void
    {
        $user = User::factory()->create();
        foreach (['https://evil.example/phishing', '//evil.example', '/\\evil.example', 'javascript:alert(1)', 'http://localhost:9000/editor', 'http://user@localhost/editor', "/editor\r\nLocation: https://evil.example"] as $url) {
            Auth::forgetGuards();
            $this->withSession(['url.intended' => $url])->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect('/materi')->assertSessionMissing('url.intended');
            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_five_failed_attempts_lock_out_even_correct_credentials_until_expiry(): void
    {
        $user = User::factory()->create(['email' => 'limited@example.test']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.']);
        }
        $this->postJson('/login', ['email' => strtoupper($user->email), 'password' => 'password'])
            ->assertStatus(429)->assertJsonValidationErrors('email');
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->get('/login')->assertSee('Terlalu banyak percobaan masuk.');
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_resets_the_account_failure_counter(): void
    {
        $user = User::factory()->create();
        for ($cycle = 0; $cycle < 2; $cycle++) {
            Auth::forgetGuards();
            for ($i = 0; $i < 4; $i++) {
                $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
            }
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_ip_limit_blocks_attempts_across_different_emails(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post('/login', ['email' => "unknown{$i}@example.test", 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.']);
        }
        $this->postJson('/login', ['email' => 'another@example.test', 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }
}
