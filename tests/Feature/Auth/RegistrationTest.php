<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Pelajar OOPy',
            'email' => 'pelajar@example.test',
            'password' => 'belajar123',
            'password_confirmation' => 'belajar123',
        ], $overrides);
    }

    public function test_guest_can_open_registration_form(): void
    {
        $this->get('/register')->assertOk()->assertSee('Buat Akun OOPy')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('css/oopy/auth/auth.css')->assertDontSee('name="role"', false);
    }

    public function test_registration_hashes_password_logs_in_rotates_session_and_defaults_to_user(): void
    {
        $this->withSession(['url.intended' => '/editor']);
        $oldSessionId = session()->getId();
        session()->save();

        $this->withCookie(config('session.cookie'), $oldSessionId)
            ->post('/register', $this->validData(['role' => 'admin']))
            ->assertRedirect('/materi')->assertSessionHas('status')->assertSessionMissing('url.intended');

        $user = User::sole();
        $this->assertSame('user', $user->role);
        $this->assertSame('Pelajar OOPy', $user->name);
        $this->assertNotSame('belajar123', $user->password);
        $this->assertTrue(Hash::check('belajar123', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->get('/materi')->assertSee('Akun berhasil dibuat. Selamat belajar di OOPy!');
    }

    public function test_duplicate_email_is_rejected_without_creating_or_authenticating_a_user(): void
    {
        User::factory()->create(['email' => 'pelajar@example.test']);
        $this->from('/register')->post('/register', $this->validData())
            ->assertRedirect('/register')->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post('/register', $this->validData(['password_confirmation' => 'berbeda123']))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_required_fields_email_format_lengths_and_minimum_password_are_validated(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->post('/register', $this->validData([
            'name' => str_repeat('a', 256), 'email' => 'bukan-email',
            'password' => 'pendek', 'password_confirmation' => 'pendek',
        ]))->assertSessionHasErrors(['name', 'email', 'password']);
        $this->post('/register', $this->validData(['email' => str_repeat('a', 245).'@example.test']))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_failed_registration_preserves_name_email_but_never_passwords(): void
    {
        $this->from('/register')->post('/register', $this->validData(['password_confirmation' => 'salah123']))
            ->assertSessionHasInput('name', 'Pelajar OOPy')->assertSessionHasInput('email', 'pelajar@example.test');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
        $this->get('/register')->assertSee('value="pelajar@example.test"', false)
            ->assertSee('Konfirmasi password tidak sesuai.')->assertDontSee('belajar123');
    }

    public function test_authenticated_user_is_redirected_away_from_registration(): void
    {
        $this->actingAs(User::factory()->create())->get('/register')->assertRedirect('/materi');
        $this->post('/register', $this->validData())->assertRedirect('/materi');
        $this->assertDatabaseCount('users', 1);
    }
}
