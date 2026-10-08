<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Test-only endpoints: no admin page or role-changing HTTP route is shipped.
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin', fn () => 'Admin allowed');
        Route::middleware(['web', 'role:admin'])->get('/_test/role-only', fn () => 'Admin allowed');
        Route::middleware(['web', 'auth', 'role:user,admin'])->get('/_test/account', fn () => 'Account allowed');
    }

    public function test_guest_is_redirected_to_login_and_intended_url_is_kept(): void
    {
        $this->get('/_test/admin')->assertRedirect('/login')->assertSessionHas('url.intended', url('/_test/admin'));
        $this->get('/_test/role-only')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403_from_the_server(): void
    {
        $this->actingAs(User::factory()->create())->get('/_test/admin?role=admin')->assertForbidden();
    }

    public function test_admin_is_allowed_and_can_return_to_a_protected_intended_url_after_login(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->get('/_test/admin')->assertRedirect('/login');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/_test/admin');
        $this->get('/_test/admin')->assertOk()->assertSee('Admin allowed');
    }

    public function test_multiple_explicit_roles_are_supported(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/_test/account')->assertOk();
        $user->role = 'admin';
        $user->save();
        $this->get('/_test/account')->assertOk();
    }

    public function test_registering_with_admin_input_never_grants_admin_access(): void
    {
        $this->post('/register?role=admin', [
            'name' => 'Pelajar', 'email' => 'learner@example.test', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'admin',
        ])->assertRedirect('/materi');
        $this->assertSame('user', User::sole()->role);
        $this->get('/_test/admin')->assertForbidden();
    }

    public function test_navbar_shows_real_guest_links_and_escaped_account_details_with_post_logout(): void
    {
        $this->get('/')->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)->assertDontSee('Dashboard');
        $user = User::factory()->create(['name' => '<script>alert("test")</script>']);
        $user->role = 'admin';
        $user->save();
        $this->actingAs($user)->get('/')->assertSee($user->name)->assertDontSee($user->name, false)
            ->assertSee('Role akun: Admin')->assertSee('method="POST" action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)->assertDontSee('href="'.route('login').'"', false);
    }

    public function test_all_existing_learning_pages_remain_public_and_auth_css_is_scoped(): void
    {
        foreach (['/', '/materi', '/materi/dasar-pemrograman-oop', '/materi/kelas-dan-objek', '/materi/enkapsulasi', '/materi/pewarisan', '/materi/polimorfisme', '/materi/kelas-abstrak', '/materi/evaluasi-akhir', '/materi/evaluasi-akhir/ujian', '/materi/evaluasi-akhir/hasil', '/editor'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('css/oopy/auth/auth.css');
        }
        $this->assertGuest();
    }
}
