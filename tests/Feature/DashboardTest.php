<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\ChapterQuizService;
use App\Services\DashboardProgressService;
use App\Services\QuizGradingService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function slugs(): array
    {
        return array_keys(array_filter(require resource_path('materi/chapters.php'), fn ($metadata) => isset((require resource_path('materi/'.$metadata['content']))['quiz'])));
    }

    private function grade(User $user, int $chapter, int $correct): void
    {
        $quizzes = app(ChapterQuizService::class);
        $quiz = $quizzes->quiz($this->slugs()[$chapter]);
        $attempt = $quizzes->start($quiz, $user);
        $answers = $quiz->questions->map(fn ($question, $index) => [
            'question_id' => $question->id,
            'answer' => $question->question_type === 'multiple_choice'
                ? ($index < $correct ? (int) $question->correct_answer : ((int) $question->correct_answer + 1) % count($question->options))
                : ($index < $correct ? $question->correct_answer : strtoupper($question->correct_answer)),
        ])->all();
        app(QuizGradingService::class)->submit($quiz, $attempt, $user, $answers);
    }

    private function submitCoding(User $user, string $key, string $status, ?float $score, ?string $time = null): ExerciseSubmission
    {
        return $user->exerciseSubmissions()->create([
            'exercise_id' => Exercise::where('exercise_key', $key)->sole()->id,
            'code' => 'private-source-for-'.$user->id,
            'output' => 'private-output-for-'.$user->id,
            'status' => $status,
            'score' => $score,
            'submitted_at' => $time ?? now(),
        ]);
    }

    public function test_guest_is_redirected_to_login_and_login_preserves_intended_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login')->assertSessionHas('url.intended', route('dashboard'));
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    public function test_new_account_has_real_empty_state_six_chapters_and_scoped_styles(): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create(['name' => 'Pelajar Baru']);
        $response = $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Pelajar Baru')->assertSee('Belum ada nilai kuis')->assertSee('Belum ada aktivitas belajar yang tersimpan.')
            ->assertSee('css/oopy/dashboard/dashboard.css')->assertSee('aria-valuenow="0"', false)
            ->assertSee('href="'.route('dashboard').'" class="oopy-dashboard-menu-link is-active"', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $data = $response->viewData('dashboard');
        $this->assertSame(0, $data['completed_chapters']);
        $this->assertSame(0, $data['completed_attempts']);
        $this->assertNull($data['average_score']);
        $this->assertCount(6, $data['chapters']);
        $this->assertSame(0, $data['coding']['progress_percent']);
        $this->assertSame(0, $data['coding']['completed_exercises']);
        $this->assertSame(2, $data['coding']['total_exercises']);
        $this->assertSame([], $data['coding']['recent_activity']);
        $response->assertSee('Progres Latihan Coding')->assertSee('Riwayat Latihan Coding')
            ->assertSee('Belum ada riwayat latihan coding.')->assertSee('Buka Latihan Coding');
        foreach ($data['coding']['exercises'] as $exercise) {
            $this->assertSame('not_started', $exercise['status']);
            $this->assertSame('Belum Dimulai', $exercise['status_label']);
            $this->assertNull($exercise['best_score']);
            $this->assertSame('—', $exercise['best_score_label']);
            $this->assertSame('Mulai Latihan', $exercise['action']);
            $response->assertSee($exercise['url']);
        }
        $this->assertSame(route('materi.show', $this->slugs()[0]), $data['recommendation']['url']);
        $this->assertSame('Mulai Belajar BAB 1', $data['recommendation']['action']);
        foreach ($data['chapters'] as $chapter) {
            $this->assertSame('not_started', $chapter['status']);
            $this->assertNull($chapter['best_score']);
            $response->assertSee($chapter['url']);
        }
        $this->get('/materi')->assertDontSee('css/oopy/dashboard/dashboard.css');
    }

    public function test_unseeded_catalog_still_renders_the_registry_and_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertViewHas('dashboard', fn ($data) => count($data['chapters']) === 6 && $data['average_score'] === null && $data['progress_percent'] === 0
                && count($data['coding']['exercises']) === 2 && $data['coding']['progress_percent'] === 0 && $data['coding']['recent_activity'] === []);
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertDatabaseCount('user_progress', 0);
        $this->assertDatabaseCount('exercises', 0);
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public static function completionPercentages(): array
    {
        return [[0, 0], [1, 17], [2, 33], [3, 50], [4, 67], [5, 83], [6, 100]];
    }

    #[DataProvider('completionPercentages')]
    public function test_completed_chapters_progress_and_next_recommendation(int $completed, int $percent): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create();
        for ($chapter = 0; $chapter < $completed; $chapter++) {
            $this->grade($user, $chapter, 4);
        }
        $response = $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('aria-valuenow="'.$percent.'"', false);
        $data = $response->viewData('dashboard');
        $this->assertSame($completed, $data['completed_chapters']);
        $this->assertSame($percent, $data['progress_percent']);
        $this->assertSame($completed === 6 ? route('evaluasi.index') : route('materi.show', $this->slugs()[$completed]), $data['recommendation']['url']);
        $this->assertSame($completed === 6 ? 'Lanjut ke Evaluasi Akhir' : ($completed === 0 ? 'Mulai Belajar BAB 1' : 'Lanjutkan BAB '.($completed + 1)), $data['recommendation']['action']);
    }

    public function test_statistics_average_the_best_per_chapter_excluding_unfinished_final_and_invalid_results(): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create();
        foreach ([3, 5, 4] as $correct) {
            $this->grade($user, 0, $correct);
        }
        $this->grade($user, 1, 4);
        $chapterQuiz = Quiz::where('slug', 'kuis-'.$this->slugs()[2])->firstOrFail();
        $user->quizAttempts()->create(['quiz_id' => $chapterQuiz->id, 'score' => 100]);
        $user->quizAttempts()->create(['quiz_id' => $chapterQuiz->id, 'status' => 'completed', 'score' => null, 'completed_at' => now()]);
        $user->quizAttempts()->create(['quiz_id' => $chapterQuiz->id, 'status' => 'expired', 'score' => 100, 'completed_at' => now()]);
        $final = Quiz::where('type', 'final_exam')->firstOrFail();
        $user->quizAttempts()->create(['quiz_id' => $final->id, 'status' => 'completed', 'score' => 100, 'completed_at' => now()]);
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard');
        $this->assertSame(90.0, $data['average_score']);
        $this->assertSame(4, $data['completed_attempts']);
        $this->assertSame(100.0, $data['chapters'][0]['best_score']);
        $this->assertSame(80.0, $data['chapters'][1]['best_score']);
        $this->assertNull($data['chapters'][2]['best_score']);
        $this->assertSame('in_progress', $data['chapters'][2]['status']);
        $this->assertCount(4, $data['recent_activity']);
    }

    public function test_zero_is_a_real_score_and_failed_attempt_is_real_in_progress(): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create();
        $this->grade($user, 0, 0);
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard');
        $this->assertSame(0.0, $data['average_score']);
        $this->assertSame('0', $data['average_score_label']);
        $this->assertSame('in_progress', $data['chapters'][0]['status']);
        $this->assertSame('Lanjutkan BAB 1', $data['recommendation']['action']);
    }

    public function test_recommendation_chooses_first_unfinished_chapter_and_failed_retries_do_not_revoke_completion(): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create();
        $this->grade($user, 0, 4);
        $this->grade($user, 2, 5);
        $this->grade($user, 0, 0);
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard');
        $this->assertSame(2, $data['completed_chapters']);
        $this->assertSame('completed', $data['chapters'][0]['status']);
        $this->assertSame(route('materi.show', $this->slugs()[1]), $data['recommendation']['url']);
        $this->assertSame('Pelajari Kembali', $data['chapters'][0]['action']);
    }

    public function test_accounts_are_isolated_and_request_parameters_cannot_select_another_account_or_raise_role(): void
    {
        $this->seed(OopyContentSeeder::class);
        $a = User::factory()->create(['name' => 'Akun Pertama']);
        $b = User::factory()->create(['name' => 'Akun Kedua']);
        $this->grade($a, 0, 5);
        $this->actingAs($b)->get('/dashboard?user_id='.$a->id.'&role=admin')->assertOk()
            ->assertDontSee('Akun Pertama')->assertViewHas('dashboard', fn ($data) => $data['completed_chapters'] === 0 && $data['average_score'] === null && $data['recent_activity'] === []);
        $this->assertSame('user', $b->fresh()->role);
        $this->actingAs($a)->get('/dashboard?user_id='.$b->id)->assertOk()
            ->assertViewHas('dashboard', fn ($data) => $data['completed_chapters'] === 1 && $data['completed_attempts'] === 1);
        $this->post('/dashboard', ['role' => 'admin'])->assertStatus(405);
    }

    public function test_admin_can_view_only_their_own_learning_dashboard(): void
    {
        $this->seed(OopyContentSeeder::class);
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->grade(User::factory()->create(), 0, 5);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Role Akun')->assertSee('Admin')
            ->assertViewHas('dashboard', fn ($data) => $data['completed_attempts'] === 0);
    }

    public function test_activity_is_limited_to_five_latest_completed_attempts_and_displays_actual_times(): void
    {
        $this->seed(OopyContentSeeder::class);
        $user = User::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-09 00:00:00', 'UTC'));
        for ($index = 0; $index < 7; $index++) {
            $this->travel(1)->minutes();
            $this->grade($user, $index % 6, $index === 6 ? 0 : 4);
        }
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard');
        $this->assertCount(5, $data['recent_activity']);
        $this->assertSame('9 Okt 2026, 08:07 WITA', $data['recent_activity'][0]['date_label']);
        $this->assertFalse($data['recent_activity'][0]['passed']);
        $this->assertTrue($data['recent_activity'][1]['passed']);
        $this->assertSame(6, $data['completed_chapters']);
    }

    public function test_dashboard_is_read_only_and_does_not_fetch_questions_or_all_attempts(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        foreach ([0, 1, 2, 3, 4, 5] as $chapter) {
            $this->grade($user, $chapter, 4);
        }
        for ($index = 0; $index < 12; $index++) {
            $this->submitCoding($user, 'coding-latihan-1', $index === 0 ? 'passed' : 'failed', $index === 0 ? 90 : 0);
        }
        $attemptsBefore = QuizAttempt::count();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Model::preventLazyLoading();
        try {
            $this->actingAs($user)->get('/dashboard')->assertOk()
                ->assertViewHas('dashboard', fn ($data) => count($data['coding']['recent_activity']) === 5 && $data['coding']['progress_percent'] === 50);
            $queries = DB::getQueryLog();
        } finally {
            Model::preventLazyLoading(false);
            DB::disableQueryLog();
        }
        $this->assertCount(9, $queries);
        foreach ($queries as $query) {
            $this->assertStringStartsWith('select ', strtolower($query['query']));
            $this->assertStringNotContainsString('quiz_answers', $query['query']);
            $this->assertStringNotContainsString('questions', $query['query']);
            $this->assertStringNotContainsString('"code"', $query['query']);
            $this->assertStringNotContainsString('"output"', $query['query']);
            $this->assertStringNotContainsString('"snapshot"', $query['query']);
        }
        $this->assertSame($attemptsBefore, QuizAttempt::count());
        $this->assertDatabaseCount('exercise_submissions', 12);
    }

    public static function codingCompletionPercentages(): array
    {
        return [
            'none' => [[], 0],
            'exercise one' => [['coding-latihan-1'], 50],
            'exercise two' => [['coding-latihan-2-multifile-v1'], 50],
            'both' => [['coding-latihan-1', 'coding-latihan-2-multifile-v1'], 100],
        ];
    }

    #[DataProvider('codingCompletionPercentages')]
    public function test_coding_completion_is_separate_from_chapter_and_quiz_progress(array $passed, int $percent): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        $this->grade($user, 0, 4);
        foreach ($passed as $key) {
            $this->submitCoding($user, $key, 'passed', 90);
        }
        $this->submitCoding($user, Exercise::whereNotIn('exercise_key', ['coding-latihan-1', 'coding-latihan-2-multifile-v1'])->firstOrFail()->exercise_key, 'passed', 100);
        $response = $this->actingAs($user)->get('/dashboard')->assertOk();
        $data = $response->viewData('dashboard');
        $coding = $data['coding'];
        $this->assertSame($percent, $coding['progress_percent']);
        $this->assertSame(count($passed), $coding['completed_exercises']);
        $this->assertSame(2, $coding['total_exercises']);
        $this->assertCount(count($passed), $coding['recent_activity']);
        foreach ($coding['exercises'] as $index => $exercise) {
            $done = in_array($exercise['key'], $passed, true);
            $this->assertSame($done ? 'Selesai' : 'Belum Dimulai', $exercise['status_label']);
            $this->assertSame($done ? 'Kerjakan Kembali' : 'Mulai Latihan', $exercise['action']);
            $this->assertSame(route('coding.show', 'latihan-'.($index + 1)), $exercise['url']);
            $this->get($exercise['url'])->assertOk();
        }
        $response->assertSee('aria-labelledby="coding-progress-title" aria-valuenow="'.$percent.'"', false);
        $this->assertSame(1, $data['completed_chapters']);
        $this->assertSame(17, $data['progress_percent']);
        $this->assertSame(80.0, $data['average_score']);
        $this->assertSame(1, $data['completed_attempts']);
        $this->assertCount(1, $data['recent_activity']);
    }

    public function test_coding_best_score_and_completion_survive_lower_failed_retries(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        foreach ([['failed', 60], ['failed', 75], ['passed', 90], ['failed', 70]] as [$status, $score]) {
            $this->submitCoding($user, 'coding-latihan-1', $status, $score);
        }
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard')['coding'];
        $this->assertSame(50, $data['progress_percent']);
        $this->assertSame(90.0, $data['exercises'][0]['best_score']);
        $this->assertSame('90', $data['exercises'][0]['best_score_label']);
        $this->assertSame('Selesai', $data['exercises'][0]['status_label']);
        $this->assertSame('Kerjakan Kembali', $data['exercises'][0]['action']);
        $this->assertSame('Belum Lulus', $data['recent_activity'][0]['status_label']);
        $this->assertSame('70', $data['recent_activity'][0]['score']);
    }

    public function test_coding_uses_official_status_even_with_a_high_score_and_keeps_valid_zero(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        $this->submitCoding($user, 'coding-latihan-1', 'failed', 100);
        $this->submitCoding($user, 'coding-latihan-2-multifile-v1', 'failed', 0);
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard')['coding'];
        $this->assertSame(0, $data['progress_percent']);
        foreach ($data['exercises'] as $exercise) {
            $this->assertSame('Belum Lulus', $exercise['status_label']);
            $this->assertSame('Lanjutkan Latihan', $exercise['action']);
        }
        $this->assertSame(100.0, $data['exercises'][0]['best_score']);
        $this->assertSame(0.0, $data['exercises'][1]['best_score']);
        $this->assertSame('0', $data['exercises'][1]['best_score_label']);
        $this->assertSame('0', $data['recent_activity'][0]['score']);
    }

    public function test_coding_without_a_valid_graded_score_shows_no_score(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        $this->submitCoding($user, 'coding-latihan-1', 'failed', null);
        $this->submitCoding($user, 'coding-latihan-2-multifile-v1', 'submitted', 100);
        $data = $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('dashboard')['coding'];
        $this->assertSame(0, $data['progress_percent']);
        foreach ($data['exercises'] as $exercise) {
            $this->assertSame('Belum Lulus', $exercise['status_label']);
            $this->assertNull($exercise['best_score']);
            $this->assertSame('—', $exercise['best_score_label']);
        }
        $this->assertSame(['—', '—'], array_column($data['recent_activity'], 'score'));
    }

    public function test_coding_history_has_five_latest_submissions_with_time_and_id_order_in_wita(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        foreach ([7, 1, 6, 2, 5, 3, 4] as $minute) {
            $this->submitCoding($user, 'coding-latihan-1', 'failed', $minute, '2026-11-05 00:0'.$minute.':00');
        }
        $this->submitCoding($user, 'coding-latihan-2-multifile-v1', 'error', 0, '2026-11-05 00:07:00');
        $this->submitCoding($user, 'coding-latihan-2-multifile-v1', 'passed', 90, '2026-11-05 00:07:00');
        $response = $this->actingAs($user)->get('/dashboard')->assertOk();
        $recent = $response->viewData('dashboard')['coding']['recent_activity'];
        $this->assertCount(5, $recent);
        $this->assertSame(['90', '0', '7', '6', '5'], array_column($recent, 'score'));
        $this->assertSame(['Lulus', 'Error', 'Belum Lulus', 'Belum Lulus', 'Belum Lulus'], array_column($recent, 'status_label'));
        $this->assertSame('5 Nov 2026, 08:07 WITA', $recent[0]['date_label']);
        $this->assertSame('2026-11-05T08:07:00+08:00', $recent[0]['datetime']);
        $response->assertSee('5 Nov 2026, 08:07 WITA')->assertSee('Error')
            ->assertDontSee('private-source-for-')->assertDontSee('private-output-for-');
    }

    public function test_coding_is_scoped_to_the_requested_user_including_admin_service_calls(): void
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $student = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->submitCoding($student, 'coding-latihan-1', 'passed', 90);
        $this->submitCoding($other, 'coding-latihan-2-multifile-v1', 'error', 0);
        $this->actingAs($other)->get('/dashboard?user_id='.$student->id)->assertOk()
            ->assertViewHas('dashboard', fn ($data) => $data['coding']['progress_percent'] === 0
                && $data['coding']['exercises'][0]['status'] === 'not_started'
                && count($data['coding']['recent_activity']) === 1 && $data['coding']['recent_activity'][0]['status'] === 'error');
        $this->actingAs($student)->get('/dashboard?user_id='.$other->id)->assertOk()
            ->assertViewHas('dashboard', fn ($data) => $data['coding']['progress_percent'] === 50
                && $data['coding']['exercises'][1]['status'] === 'not_started'
                && count($data['coding']['recent_activity']) === 1 && $data['coding']['recent_activity'][0]['status'] === 'passed');
        $this->actingAs($admin);
        $service = app(DashboardProgressService::class);
        $studentCoding = $service->forUser($student)['coding'];
        $this->assertSame(50, $studentCoding['progress_percent']);
        $this->assertSame('Selesai', $studentCoding['exercises'][0]['status_label']);
        $this->assertSame(0, $service->forUser($admin)['coding']['progress_percent']);
        $this->assertSame([], $service->forUser($admin)['coding']['recent_activity']);
    }

    public function test_names_are_escaped_and_navbar_dashboard_is_only_visible_when_logged_in(): void
    {
        $this->get('/')->assertDontSee('href="'.route('dashboard').'"', false);
        $name = '<script>alert("nama")</script>'.str_repeat('Nama', 40);
        $this->actingAs(User::factory()->create(['name' => $name]))->get('/dashboard')->assertOk()
            ->assertSee($name)->assertDontSee($name, false)->assertDontSee('name="password"', false);
    }
}
