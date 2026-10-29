<?php

namespace Tests\Feature\Admin;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    private function attempt(User $user, Quiz $quiz, float $score, string $status = 'completed'): QuizAttempt
    {
        return $user->quizAttempts()->create([
            'quiz_id' => $quiz->id, 'score' => $score, 'status' => $status,
            'started_at' => now()->subMinutes(10), 'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    public function test_every_admin_endpoint_enforces_backend_authorization_including_writes(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'admin.'));
        $this->assertGreaterThanOrEqual(20, $routes->count());
        $user = User::factory()->create();
        foreach ($routes as $route) {
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $method = $route->methods()[0];
            $this->app['auth']->forgetGuards();
            $this->call($method, $uri)->assertRedirect('/login');
            $this->actingAs($user)->call($method, $uri)->assertForbidden();
        }
    }

    public function test_admin_pages_render_real_empty_states_and_never_expose_credentials(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create(['name' => '<script>alert(1)</script>']);
        foreach (['/admin', '/admin/users', '/admin/scores', '/admin/questions', '/admin/materials', '/admin/users/'.$student->id] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
                ->assertDontSee($student->password)->assertDontSee('<script>alert(1)</script>', false);
        }
        $stats = $this->get('/admin')->viewData('stats');
        $this->assertSame(1, $stats['users']);
        $this->assertSame(0, $stats['attempts']);
        $this->assertNull($stats['average']);
        $this->actingAs($admin)->get('/admin/users/'.$admin->id)->assertNotFound();
    }

    public function test_best_completed_scores_filters_zero_and_absent_scores_are_distinct(): void
    {
        $this->seed(OopyContentSeeder::class);
        $admin = $this->admin();
        $student = User::factory()->create(['name' => 'Nilai Terbaik']);
        $zero = User::factory()->create(['name' => 'Nilai Nol']);
        $new = User::factory()->create(['name' => 'Belum Kuis']);
        $quiz = Quiz::where('type', 'chapter_quiz')->firstOrFail();
        $this->attempt($student, $quiz, 100);
        $this->attempt($student, $quiz, 40);
        $this->attempt($student, $quiz, 20, 'in_progress');
        $this->attempt($zero, $quiz, 0);
        $reports = app(LearningReportService::class);
        $users = $reports->users()->get()->keyBy('id');
        $row = $reports->row($users[$student->id]);
        $this->assertSame(100.0, $row['scores'][1]);
        $this->assertNull($row['scores'][2]);
        $this->assertSame(100.0, $row['average']);
        $this->assertSame(2, (int) $users[$student->id]->completed_attempts);
        $this->assertSame(0.0, $reports->row($users[$zero->id])['scores'][1]);
        $this->assertNull($reports->row($users[$new->id])['average']);
        $passed = $this->actingAs($admin)->get('/admin/scores?chapter=1&status=passed')->assertOk()->viewData('users');
        $this->assertSame([$student->id], $passed->pluck('id')->all());
        $failed = $this->get('/admin/scores?chapter=1&status=not_passed')->assertOk()->viewData('users');
        $this->assertSame([$zero->id], $failed->pluck('id')->all());
        $unstarted = $this->get('/admin/scores?chapter=1&status=not_started')->assertOk()->viewData('users');
        $this->assertSame([$new->id], $unstarted->pluck('id')->all());
        $stats = $this->get('/admin')->viewData('stats');
        $this->assertSame(3, $stats['attempts']);
        $this->assertEqualsWithDelta(140 / 3, $stats['average'], .001);
        $this->assertSame(50, $stats['questions']);
        $this->get('/admin/users/'.$student->id)->assertOk()->assertSee('100');
    }

    public function test_search_pagination_and_filtered_formula_safe_csv(): void
    {
        $this->seed(OopyContentSeeder::class);
        $admin = $this->admin();
        User::factory()->count(23)->create();
        $student = User::factory()->create(['name' => '=HYPERLINK("https://invalid.test")', 'email' => 'csv@example.test']);
        $this->attempt($student, Quiz::where('type', 'chapter_quiz')->firstOrFail(), 80);
        $this->actingAs($admin)->get('/admin/users?page=2')->assertOk()->assertViewHas('users', fn ($users) => $users->currentPage() === 2 && $users->count() === 4);
        $this->get('/admin/users?q=csv%40example.test')->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 1);
        $response = $this->get('/admin/scores/export?q=csv%40example.test&chapter=1&status=passed')->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('csv@example.test', $csv);
        $this->assertStringNotContainsString($student->password, $csv);
        $this->assertCount(3, explode("\n", $csv));
        foreach (['=1+1', '+SUM(A1)', '-2+3', '@SUM(1)', "\t=CMD()", " \r+X"] as $cell) {
            $this->assertStringStartsWith("'", LearningReportService::csvCell($cell));
        }
        $this->assertSame('Pelajar', LearningReportService::csvCell('Pelajar'));
        $this->get('/admin/scores?sort=score%20desc')->assertSessionHasErrors('sort');
    }

    public function test_pending_essays_are_reported_without_a_fabricated_final_total(): void
    {
        $this->seed(OopyContentSeeder::class);
        $admin = $this->admin();
        $student = User::factory()->create();
        $id = $this->actingAs($student)->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => [], 'score' => 100])->assertOk();
        $reports = app(LearningReportService::class);
        $record = $reports->users()->whereKey($student->id)->firstOrFail();
        $this->assertNull($record->final_score);
        $this->assertSame(1, (int) $record->final_pending);
        $stats = $this->actingAs($admin)->get('/admin')->assertOk()->viewData('stats');
        $this->assertSame(1, $stats['finals']);
        $this->assertSame(1, $stats['pending']);
        $this->assertNull($stats['final_average']);
        $this->get('/admin/users/'.$student->id)->assertOk()->assertSee('Menunggu penilaian uraian');
        $this->get('/admin/scores/export')->assertOk();
    }
}
