<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback']);
    }

    private function google(array $profile = [], int $status = 200): void
    {
        $this->requests = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response($status, [], json_encode(['access_token' => 'private-access-token', 'expires_in' => 3600, 'token_type' => 'Bearer'])),
            new Response(200, [], json_encode(array_replace([
                'sub' => 'google-subject', 'email' => 'student@gmail.com', 'email_verified' => true, 'name' => 'Pelajar Google',
            ], $profile))),
        ]));
        $handler->push(Middleware::history($this->requests));
        config(['services.google.guzzle.handler' => $handler]);
        Socialite::forgetDrivers();
    }

    private function googleCallback(array $extra = [])
    {
        return $this->withSession(['state' => 'valid-state', 'code_verifier' => str_repeat('v', 64)])
            ->get(route('auth.google.callback', array_replace(['code' => 'valid-code', 'state' => 'valid-state'], $extra)));
    }

    public function test_buttons_and_missing_configuration_keep_password_login_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk dengan Google')->assertSee(route('auth.google.redirect'));
        $this->get('/register')->assertOk()->assertSee('Masuk dengan Google');
        config(['services.google.client_secret' => null]);
        $this->get(route('auth.google.redirect'))->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->get('/login')->assertSee('Login Google belum tersedia.')->assertSee('autocomplete="current-password"', false);
        $this->assertGuest();
    }

    public function test_redirect_uses_state_pkce_and_only_identity_scopes(): void
    {
        $response = $this->get(route('auth.google.redirect'))->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(session('state'), $query['state']);
        $this->assertNotEmpty($query['state']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', session('code_verifier'), true)), '+/', '-_'), '='), $query['code_challenge']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertStringNotContainsString('test-secret', $url);
    }

    public function test_invalid_state_is_rejected_before_contacting_google(): void
    {
        $this->google();
        $this->googleCallback(['state' => 'forged-state'])->assertRedirect('/login')->assertSessionHasErrors('google')
            ->assertSessionMissing('state')->assertSessionMissing('code_verifier');
        $this->assertSame([], $this->requests);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cancelled_and_malformed_callbacks_do_not_create_an_account(): void
    {
        $this->google();
        foreach ([['error' => 'access_denied'], ['code' => null], ['state' => ['invalid']], ['code' => ['invalid']]] as $extra) {
            $this->googleCallback($extra)->assertRedirect('/login')->assertSessionHasErrors('google');
        }
        $this->assertSame([], $this->requests);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_verified_google_account_registers_as_user_and_regenerates_session(): void
    {
        $this->google(['role' => 'admin']);
        $this->withSession(['url.intended' => '/dashboard', 'marker' => 'preserved']);
        $oldId = session()->getId();
        $oldToken = session()->token();
        $this->googleCallback(['role' => 'admin'])->assertRedirect('/dashboard')->assertSessionHas('marker', 'preserved')
            ->assertSessionMissing('state')->assertSessionMissing('code_verifier')->assertSessionMissing('url.intended');
        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('user', $user->role);
        $this->assertSame('google-subject', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertArrayNotHasKey('google_id', $user->toArray());
        parse_str((string) $this->requests[0]['request']->getBody(), $tokenRequest);
        $this->assertSame(str_repeat('v', 64), $tokenRequest['code_verifier']);
        $this->assertSame('test-secret', $tokenRequest['client_secret']);
        $this->assertSame('Bearer private-access-token', $this->requests[1]['request']->getHeaderLine('Authorization'));
    }

    public function test_existing_gmail_account_keeps_password_and_progress_identity(): void
    {
        $user = User::factory()->create(['email' => 'student@gmail.com']);
        $password = $user->getRawOriginal('password');
        $this->google();
        $this->googleCallback()->assertRedirect('/materi');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
        $this->assertSame('google-subject', $user->fresh()->google_id);
    }

    public function test_repeat_login_uses_google_subject_even_if_provider_email_changes(): void
    {
        $user = User::factory()->create(['email' => 'original@gmail.com']);
        $user->google_id = 'google-subject';
        $user->save();
        $this->google(['email' => 'changed@gmail.com']);
        $this->googleCallback()->assertRedirect('/materi');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('original@gmail.com', $user->fresh()->email);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_unverified_and_incomplete_profiles_are_rejected(): void
    {
        foreach ([['email_verified' => false], ['email_verified' => 'true'], ['sub' => null], ['email' => 'invalid']] as $profile) {
            $this->google($profile);
            $this->googleCallback()->assertRedirect('/login')->assertSessionHasErrors('google');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_admin_conflicting_identity_and_third_party_email_cannot_be_auto_linked(): void
    {
        foreach ([['role' => 'admin'], ['google_id' => 'another-subject'], ['email' => 'student@example.test']] as $attributes) {
            $user = User::factory()->create(['email' => $attributes['email'] ?? 'student@gmail.com']);
            $user->forceFill($attributes)->save();
            $this->google(['email' => $user->email]);
            $this->googleCallback()->assertRedirect('/login')->assertSessionHasErrors('google');
            $this->assertGuest();
            $this->assertSame($attributes['google_id'] ?? null, $user->fresh()->google_id);
            $user->delete();
        }
    }

    public function test_remote_failure_and_replayed_callback_do_not_authenticate(): void
    {
        $this->google(status: 503);
        $this->googleCallback()->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->google();
        $this->googleCallback()->assertRedirect('/materi');
        $this->post('/logout');
        Auth::forgetGuards();
        $this->google();
        $this->get(route('auth.google.callback', ['state' => 'valid-state', 'code' => 'valid-code']))
            ->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertSame([], $this->requests);
    }

    public function test_google_login_discards_external_intended_urls(): void
    {
        $this->google();
        $this->withSession(['url.intended' => 'https://evil.example/phishing']);
        $this->googleCallback()->assertRedirect('/materi')->assertSessionMissing('url.intended');
    }

    public function test_google_identity_migration_refuses_rollback_while_linked_accounts_exist(): void
    {
        $user = User::factory()->create();
        $user->google_id = 'linked-subject';
        $user->save();
        $migration = require database_path('migrations/2026_11_06_000001_add_google_id_to_users_table.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reconcile Google-linked accounts');
        $migration->down();
    }
}
