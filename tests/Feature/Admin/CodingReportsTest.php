<?php

namespace Tests\Feature\Admin;

use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\CodingProgressService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CodingReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $this->admin = User::factory()->create(['name' => 'Administrator']);
        $this->admin->role = 'admin';
        $this->admin->save();
    }

    private function submit(User $user, int $exercise, string $status, ?float $score, string $time = '2026-11-05 00:01:00'): ExerciseSubmission
    {
        return $user->exerciseSubmissions()->create([
            'exercise_id' => Exercise::where('exercise_key', CodingProgressService::EXERCISES[$exercise]['key'])->sole()->id,
            'code' => 'private-coding-source', 'output' => 'private-coding-output',
            'status' => $status, 'score' => $score, 'submitted_at' => $time,
        ]);
    }

    private function quiz(User $user, int $score): void
    {
        $user->quizAttempts()->create([
            'quiz_id' => Quiz::where('type', 'chapter_quiz')->orderBy('id')->firstOrFail()->id,
            'score' => $score, 'status' => 'completed', 'started_at' => now()->subMinutes(10), 'completed_at' => now(),
        ]);
    }

    private function csv(string $query = ''): array
    {
        $content = $this->actingAs($this->admin)->get('/admin/scores/export'.$query)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    public function test_coding_reports_remain_private_and_read_only(): void
    {
        $user = User::factory()->create();
        $this->submit($user, 1, 'passed', 90);
        foreach (['/admin', '/admin/users/'.$user->id, '/admin/scores?coding_status=completed', '/admin/scores/export?coding_status=completed'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
        foreach (['/admin', '/admin/users/'.$user->id, '/admin/scores', '/admin/scores/export'] as $uri) {
            $this->actingAs($user)->get($uri)->assertForbidden();
            $this->actingAs($this->admin)->get($uri)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->post('/admin/scores', ['score' => 100])->assertStatus(405);
        $this->assertDatabaseHas('exercise_submissions', ['user_id' => $user->id, 'score' => 90, 'status' => 'passed']);
        $this->assertDatabaseCount('exercise_submissions', 1);
    }

    public static function completionCases(): array
    {
        return [
            'empty' => [[], 0, ['not_started', 'not_started'], [null, null]],
            'failed high score and zero' => [[[1, 'failed', 100], [2, 'failed', 0]], 0, ['in_progress', 'in_progress'], [100.0, 0.0]],
            'one passed then failed' => [[[1, 'passed', 90], [1, 'failed', 70]], 50, ['completed', 'not_started'], [90.0, null]],
            'two passed first' => [[[2, 'passed', 90]], 50, ['not_started', 'completed'], [null, 90.0]],
            'both passed then error' => [[[1, 'passed', 90], [2, 'passed', 100], [2, 'error', 0]], 100, ['completed', 'completed'], [90.0, 100.0]],
            'null and ungraded score' => [[[1, 'failed', null], [2, 'submitted', 100]], 0, ['in_progress', 'in_progress'], [null, null]],
        ];
    }

    #[DataProvider('completionCases')]
    public function test_admin_summary_matches_the_student_dashboard(array $submissions, int $percent, array $statuses, array $scores): void
    {
        $student = User::factory()->create();
        foreach ($submissions as [$number, $status, $score]) {
            $this->submit($student, $number, $status, $score);
        }
        $this->submit($this->admin, 1, 'passed', 100);
        $response = $this->actingAs($this->admin)->get('/admin/users/'.$student->id)->assertOk()
            ->assertSee('Progres Latihan Coding')->assertSee('Riwayat Pengerjaan Coding')
            ->assertDontSee('private-coding-source')->assertDontSee('private-coding-output');
        $studentSummary = $this->actingAs($student)->get('/dashboard')->assertOk()->viewData('dashboard')['coding'];
        $detailSummary = $response->viewData('dashboard')['coding'];
        $reports = app(LearningReportService::class);
        $row = $reports->row($reports->users()->whereKey($student->id)->sole());
        foreach ([$studentSummary, $detailSummary, $row['coding']] as $summary) {
            $this->assertSame($percent, $summary['progress_percent']);
            $this->assertSame((int) ($percent / 50), $summary['completed_exercises']);
            $this->assertSame($statuses, array_column($summary['exercises'], 'status'));
            $this->assertSame($scores, array_column($summary['exercises'], 'best_score'));
        }
        if ($submissions === []) {
            $response->assertSee('Belum Dikerjakan')->assertSee('Pengguna ini belum mengerjakan latihan Coding.');
            $this->assertSame('Belum Dikerjakan', $row['coding']['exercises'][0]['status_label']);
        }
    }

    public function test_dashboard_counts_unique_students_and_shows_only_ten_latest_standalone_submissions(): void
    {
        [$both, $one, $two, $failed, $live] = User::factory()->count(5)->create()->all();
        $this->submit($both, 1, 'passed', 90);
        $this->submit($both, 1, 'failed', 70);
        $this->submit($both, 2, 'passed', 90);
        $this->submit($one, 1, 'passed', 90);
        $this->submit($two, 2, 'passed', 90);
        $ids = [];
        for ($index = 0; $index < 12; $index++) {
            $ids[] = $this->submit($failed, 1, 'failed', 100, '2026-11-05 00:07:00')->id;
        }
        $this->submit($this->admin, 1, 'passed', 100, '2026-11-05 00:10:00');
        $this->submit($this->admin, 2, 'passed', 100, '2026-11-05 00:10:00');
        $live->exerciseSubmissions()->create([
            'exercise_id' => Exercise::whereNotIn('exercise_key', ['coding-latihan-1', 'coding-latihan-2-multifile-v1'])->firstOrFail()->id,
            'code' => 'private-live-source', 'status' => 'passed', 'score' => 100, 'submitted_at' => '2026-11-05 00:11:00',
        ]);
        $response = $this->actingAs($this->admin)->get('/admin')->assertOk();
        $stats = $response->viewData('stats');
        $this->assertSame(['attempted_users' => 4, 'exercise_1_completed' => 2, 'exercise_2_completed' => 2, 'both_completed' => 1, 'none_passed' => 1], $stats['coding']);
        $response->assertSee(route('admin.users.show', $failed));
        $this->assertCount(10, $stats['recent_coding']);
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), $stats['recent_coding']->pluck('id')->all());
        $this->assertSame('failed', $stats['recent_coding'][0]['status']);
        $this->assertSame('5 Nov 2026, 08:07 WITA', $stats['recent_coding'][0]['date_label']);
        $this->assertSame(route('admin.users.show', $failed), $stats['recent_coding'][0]['user_url']);
        $response->assertDontSee('private-coding-source')->assertDontSee('private-coding-output')->assertDontSee('private-live-source');
    }

    public function test_history_is_user_scoped_paginated_and_independent_of_quiz_and_final_pages(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $ids = [];
        for ($index = 1; $index <= 25; $index++) {
            $ids[] = $this->submit($student, 1, 'failed', $index)->id;
        }
        $this->submit($other, 2, 'passed', 100, '2026-11-05 00:12:00');
        for ($index = 0; $index < 21; $index++) {
            $this->quiz($student, 80);
        }
        for ($index = 0; $index < 11; $index++) {
            FinalExamAttempt::create([
                'user_id' => $student->id, 'quiz_id' => Quiz::where('type', 'final_exam')->sole()->id,
                'snapshot' => [], 'answers' => [], 'status' => 'pending_review', 'duration_seconds' => 3600,
                'passing_score' => 70, 'started_at' => now()->subHour(), 'deadline_at' => now(), 'submitted_at' => now(),
            ]);
        }
        $response = $this->actingAs($this->admin)->get('/admin/users/'.$student->id.'?coding_page=2&history_page=2&final_page=2')->assertOk();
        $history = $response->viewData('codingHistory');
        $this->assertSame(25, $history->total());
        $this->assertSame(2, $history->currentPage());
        $this->assertCount(10, $history);
        $this->assertSame(array_slice(array_reverse($ids), 10, 10), $history->pluck('id')->all());
        $this->assertSame(2, $response->viewData('history')->currentPage());
        $this->assertCount(1, $response->viewData('history'));
        $this->assertSame(2, $response->viewData('finals')->currentPage());
        $this->assertCount(1, $response->viewData('finals'));
        $this->assertStringContainsString('history_page=2', $history->nextPageUrl());
        $this->assertStringContainsString('final_page=2', $history->nextPageUrl());
        $exercise = $response->viewData('dashboard')['coding']['exercises'][0];
        $this->assertSame(25, $exercise['attempts_count']);
        $this->assertSame('2026-11-05 00:01:00', $exercise['last_at']);
        $this->assertSame(25.0, $exercise['best_score']);
    }

    public static function filterCases(): array
    {
        return [
            ['coding-latihan-1', 'not_started', ['A New', 'D Two']],
            ['coding-latihan-1', 'in_progress', ['B Failed', 'F Pending']],
            ['coding-latihan-1', 'completed', ['C One', 'E Both']],
            ['coding-latihan-2-multifile-v1', 'not_started', ['A New', 'B Failed', 'C One', 'F Pending']],
            ['coding-latihan-2-multifile-v1', 'completed', ['D Two', 'E Both']],
            ['', 'not_started', ['A New']],
            ['', 'in_progress', ['B Failed', 'C One', 'D Two', 'F Pending']],
            ['', 'completed', ['E Both']],
        ];
    }

    #[DataProvider('filterCases')]
    public function test_exercise_and_completion_filters_run_before_pagination(string $exercise, string $status, array $expected): void
    {
        [$new, $failed, $one, $two, $both, $pending] = collect(['A New', 'B Failed', 'C One', 'D Two', 'E Both', 'F Pending'])
            ->map(fn ($name) => User::factory()->create(['name' => $name]))->all();
        $this->submit($failed, 1, 'failed', 0);
        $this->submit($one, 1, 'passed', 90);
        $this->submit($one, 1, 'failed', 70);
        $this->submit($two, 2, 'passed', 90);
        $this->submit($both, 1, 'passed', 90);
        $this->submit($both, 2, 'passed', 90);
        $this->submit($pending, 1, 'submitted', 100);
        $response = $this->actingAs($this->admin)->get('/admin/scores?'.http_build_query(['coding_exercise' => $exercise, 'coding_status' => $status]))->assertOk();
        $this->assertSame($expected, $response->viewData('users')->pluck('name')->all());
        $this->assertSame(count($expected), $response->viewData('users')->total());
    }

    public function test_coding_and_quiz_filters_search_and_csv_are_combined_with_and(): void
    {
        $a = User::factory()->create(['name' => 'Alpha', 'email' => 'filter-alpha@example.test']);
        $b = User::factory()->create(['name' => 'Beta', 'email' => 'filter-beta@example.test']);
        $c = User::factory()->create(['name' => 'Gamma', 'email' => 'filter-gamma@example.test']);
        $this->submit($a, 1, 'passed', 90);
        $this->submit($b, 1, 'passed', 90);
        $this->submit($c, 2, 'passed', 90);
        $this->quiz($a, 80);
        $this->quiz($b, 0);
        $this->quiz($c, 80);
        User::factory()->count(23)->create();
        $query = '?q=filter-&chapter=1&status=passed&coding_exercise=coding-latihan-1&coding_status=completed&sort=coding_best';
        $this->actingAs($this->admin)->get('/admin/scores'.$query)->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $a->id);
        $csv = $this->csv($query);
        $this->assertCount(2, $csv);
        $this->assertSame($a->email, $csv[1][1]);
        $this->assertSame('80', $csv[1][12]);
        $this->assertSame(['90', 'Selesai', '—', 'Belum Dikerjakan', '1', '50'], array_slice($csv[1], 15));
        $this->get('/admin/scores?q=Beta&chapter=1&status=not_passed&coding_exercise=coding-latihan-1&coding_status=completed')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $b->id);
        $this->get('/admin/users?q='.urlencode($c->email))->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $c->id);
    }

    public function test_score_pagination_and_export_keep_all_active_filters(): void
    {
        $students = collect(range(1, 23))->map(fn ($number) => User::factory()->create([
            'name' => 'Filter '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
        ]));
        foreach ($students as $student) {
            $this->quiz($student, 80);
            $this->submit($student, 1, 'passed', 90);
        }
        $quizOnly = User::factory()->create(['name' => 'Filter Quiz Only']);
        $this->quiz($quizOnly, 80);
        $codingOnly = User::factory()->create(['name' => 'Filter Coding Only']);
        $this->submit($codingOnly, 1, 'passed', 90);
        $filters = ['q' => 'Filter', 'chapter' => 1, 'status' => 'passed',
            'coding_exercise' => 'coding-latihan-1', 'coding_status' => 'completed', 'sort' => 'name'];
        $url = '/admin/scores?'.http_build_query($filters);
        $response = $this->actingAs($this->admin)->get($url.'&page=2')->assertOk();
        $users = $response->viewData('users');
        $this->assertSame(23, $users->total());
        $this->assertSame(2, $users->currentPage());
        $this->assertSame($students->slice(20)->pluck('id')->values()->all(), $users->pluck('id')->all());
        parse_str(parse_url($users->previousPageUrl(), PHP_URL_QUERY), $previousFilters);
        foreach ($filters as $key => $value) {
            $this->assertEquals($value, $previousFilters[$key]);
        }
        $response->assertSee(route('admin.scores.export', $filters));
        $rows = $this->csv('?'.http_build_query($filters).'&page=2');
        $this->assertCount(24, $rows);
        $this->assertSame($students->pluck('email')->all(), array_column(array_slice($rows, 1), 1));
        $this->get('/admin/scores')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 25 && $users->currentPage() === 1);
    }

    public function test_coding_sorting_places_zero_before_null_and_uses_stable_id_ties(): void
    {
        [$zero, $null, $high, $tie, $empty] = User::factory()->count(5)->create()->all();
        $this->submit($zero, 1, 'failed', 0, '2026-11-05 00:01:00');
        $this->submit($zero, 2, 'passed', 100, '2026-11-05 00:03:00');
        $this->submit($null, 1, 'failed', null, '2026-11-05 00:02:00');
        $this->submit($high, 1, 'passed', 90, '2026-11-05 00:02:00');
        $this->submit($tie, 1, 'passed', 90, '2026-11-05 00:02:00');
        foreach ([
            '?sort=coding_best' => [$zero->id, $high->id, $tie->id, $null->id, $empty->id],
            '?sort=coding_best&coding_exercise=coding-latihan-1' => [$high->id, $tie->id, $zero->id, $null->id, $empty->id],
            '?sort=coding_latest' => [$zero->id, $null->id, $high->id, $tie->id, $empty->id],
            '?sort=coding_latest&coding_exercise=coding-latihan-1' => [$null->id, $high->id, $tie->id, $zero->id, $empty->id],
        ] as $query => $ids) {
            $response = $this->actingAs($this->admin)->get('/admin/scores'.$query)->assertOk();
            $this->assertSame($ids, $response->viewData('users')->pluck('id')->all());
            $this->assertSame($ids, array_map(fn ($row) => User::where('email', $row[1])->sole()->id, array_slice($this->csv($query), 1)));
        }
    }

    public function test_csv_preserves_original_columns_quoting_formula_defense_and_valid_zero(): void
    {
        ob_start();
        try {
            app(LearningReportService::class)->chapters();
            $preamble = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertSame('', $preamble);
        $student = User::factory()->create(['name' => "=SUM(1;2)\n\"nama\"", 'email' => 'csv-coding@example.test']);
        $this->submit($student, 1, 'failed', 0);
        $rows = $this->csv();
        $this->assertCount(2, $rows);
        $this->assertSame(['Nama Pengguna', 'Email', 'BAB 1', 'BAB 2', 'BAB 3', 'BAB 4', 'BAB 5', 'BAB 6', 'Evaluasi Akhir', 'Status Evaluasi', 'Kuis Selesai', 'Percobaan Selesai', 'Rata-rata Kuis Terbaik', 'Status Kuis', 'Pengerjaan Terakhir'], array_slice($rows[0], 0, 15));
        $this->assertSame(['Coding Latihan 1 — Nilai Terbaik', 'Coding Latihan 1 — Status', 'Coding Latihan 2 — Nilai Terbaik', 'Coding Latihan 2 — Status', 'Coding — Latihan Selesai', 'Coding — Persentase Progres'], array_slice($rows[0], 15));
        $this->assertSame("'".$student->name, $rows[1][0]);
        $this->assertSame(['0', 'Belum Lulus', '—', 'Belum Dikerjakan', '0', '0'], array_slice($rows[1], 15));
        $this->assertCount(21, $rows[1]);
    }

    public function test_coding_filter_inputs_are_validated(): void
    {
        foreach ([['coding_exercise' => 'live-code'], ['coding_exercise' => ['coding-latihan-1']], ['coding_status' => 'passed'], ['coding_status' => ['completed']], ['sort' => 'coding_best desc; DROP TABLE users']] as $query) {
            foreach (['/admin/scores', '/admin/scores/export', '/admin/users'] as $uri) {
                $this->actingAs($this->admin)->getJson($uri.'?'.http_build_query($query))->assertUnprocessable();
            }
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_bulk_report_and_csv_do_not_query_per_user_or_read_source_code(): void
    {
        $students = User::factory()->count(25)->create();
        foreach ($students as $student) {
            $this->submit($student, 1, 'passed', 90);
        }
        $reports = app(LearningReportService::class);
        $reports->chapters();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Model::preventLazyLoading();
        try {
            $rows = [];
            foreach ($reports->users()->cursor() as $student) {
                $rows[] = $reports->row($student)['coding']['progress_percent'];
            }
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            Model::preventLazyLoading(false);
        }
        $this->assertCount(25, $rows);
        $this->assertSame([50], array_values(array_unique($rows)));
        $this->assertCount(1, $queries);
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(code|output|password|remember_token)\b/i', $query['query']);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->assertCount(26, $this->csv());
            $exportQueries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }
        $this->assertCount(2, $exportQueries);
        foreach ($exportQueries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(code|output|password|remember_token)\b/i', $query['query']);
        }
        for ($index = 0; $index < 25; $index++) {
            $this->submit($students->first(), 1, 'failed', 0);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->actingAs($this->admin)->get('/admin/users/'.$students->first()->id)->assertOk();
            $codingQueries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'exercise_submissions'));
        } finally {
            DB::disableQueryLog();
        }
        $this->assertCount(4, $codingQueries);
        foreach ($codingQueries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(code|output)\b/i', $query['query']);
        }
    }
}
