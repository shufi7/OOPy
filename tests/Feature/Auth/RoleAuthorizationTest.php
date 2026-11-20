<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureChapterUnlocked;
use App\Models\User;
use DOMDocument;
use DOMXPath;
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

    public function test_navbar_identifies_one_current_destination_including_final_evaluation(): void
    {
        $this->withoutMiddleware(EnsureChapterUnlocked::class);
        foreach ([
            '/' => 'home', '/materi' => 'materi.index', '/coding' => 'coding.index',
            '/login' => 'login', '/register' => 'register',
            '/materi/evaluasi-akhir' => 'materi.index',
            '/materi/evaluasi-akhir/ujian' => 'materi.index',
            '/materi/evaluasi-akhir/hasil' => 'materi.index',
        ] as $path => $destination) {
            $html = $this->get($path)->assertOk()->getContent();
            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $current = (new DOMXPath($dom))->query('//nav[contains(concat(" ", normalize-space(@class), " "), " site-navbar ")]//a[@aria-current="page"]');
            $this->assertSame(1, $current->length, $path);
            $this->assertSame(route($destination), $current->item(0)->getAttribute('href'));
        }
    }

    public function test_introductory_pages_remain_public_and_later_chapters_require_progress(): void
    {
        foreach (['/', '/materi', '/materi/dasar-pemrograman-oop', '/editor'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('css/oopy/auth/auth.css');
        }
        foreach (['kelas-dan-objek', 'enkapsulasi', 'pewarisan', 'polimorfisme', 'kelas-abstrak', 'evaluasi-akhir', 'evaluasi-akhir/ujian', 'evaluasi-akhir/hasil'] as $slug) {
            $this->get('/materi/'.$slug)->assertRedirect(route('materi.index'));
        }
        $this->assertGuest();
    }
}
