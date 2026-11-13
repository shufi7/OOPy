<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\User;
use App\Services\CodingProgressService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodingMultifileTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'coding-latihan-2-multifile-v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        config(['coding.grader_url' => 'http://grader.test', 'coding.grader_token' => str_repeat('x', 40)]);
        Http::preventStrayRequests();
    }

    private function files(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/coding-multifile.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    private function report(): array
    {
        return ['version' => 2, 'exercise_key' => self::KEY, 'status' => 'graded', 'output' => "Rawa Bangkau - Hulu Sungai Selatan\nStatus air: Siaga",
            'checks' => array_map(fn ($id) => ['id' => $id, 'label' => $id, 'passed' => true, 'required' => $id !== 'presentation',
                'weight' => $id === 'presentation' ? 10 : 15, 'feedback' => 'Sudah sesuai.'],
                ['modules', 'constructor', 'parent_status', 'inheritance', 'child_constructor', 'overriding', 'presentation'])];
    }

    public function test_full_project_snapshot_is_preserved_and_sent_with_session_identity(): void
    {
        Http::fake(['grader.test/grade' => Http::response($this->report())]);
        $files = array_map(fn ($source) => "\n".$source."\n\n", $this->files());
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/coding/latihan-2/submit', ['files' => $files])->assertOk()
            ->assertJsonPath('score', 100)->assertJsonPath('status', 'passed')->assertJsonPath('completed', true);
        $submission = ExerciseSubmission::sole();
        $this->assertSame($user->id, $submission->user_id);
        $this->assertSame(self::KEY, $submission->exercise->exercise_key);
        $this->assertSame(['entry_file' => 'main.py', 'files' => $files], json_decode($submission->code, true));
        Http::assertSent(fn ($request) => $request['exercise_key'] === self::KEY && $request['files'] === $files);
        $this->get('/dashboard')->assertOk()->assertViewHas('dashboard', fn ($data) => $data['coding']['progress_percent'] === 50);
        $this->postJson('/coding/latihan-1/submit', ['files' => $files])->assertUnprocessable();
    }

    public function test_missing_extra_traversal_absolute_empty_and_oversized_files_are_rejected(): void
    {
        $files = $this->files();
        $missing = $files;
        unset($missing['rawa.py']);
        $cases = [$missing, $files + ['extra.py' => 'pass'], $files + ['../main.py' => 'pass'], $files + ['C:\\main.py' => 'pass'],
            $files + ['/main.py' => 'pass'], ['main.py' => 'pass'], array_values($files),
            $files + ['rawa/../rawa.py' => 'pass'], $files + ['ekosistem.py.extra' => 'pass'],
            array_replace($files, ['rawa.py' => []]), array_replace($files, ['ekosistem.py' => " \n\t"]),
            array_replace($files, ['rawa.py' => "\u{00A0}\u{FEFF}"]),
            array_replace($files, ['rawa.py' => str_repeat('a', 30001)]),
            array_replace($files, ['rawa.py' => str_repeat('界', 21000)]),
            array_fill_keys(array_keys($files), str_repeat('a', 11000)),
            array_fill_keys(array_keys($files), str_repeat('界', 8000))];
        foreach ($cases as $case) {
            $this->actingAs(User::factory()->create())->postJson('/coding/latihan-2/submit', ['files' => $case])->assertUnprocessable();
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_identity_and_browser_grades_are_rejected_for_multifile(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/coding/latihan-2/submit', [
            'files' => $this->files(), 'score' => 100, 'passed' => true, 'status' => 'passed', 'role' => 'admin', 'user_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['score', 'passed', 'status', 'role', 'user_id']);
        Http::assertNothingSent();
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_failed_attempt_is_shown_as_not_passed_on_coding_pages(): void
    {
        $report = $this->report();
        $report['checks'][4]['passed'] = false;
        Http::fake(['grader.test/grade' => Http::response($report)]);
        $user = User::factory()->create();
        $this->actingAs($user)->get('/coding/latihan-2')->assertOk()->assertSee('Belum Dikerjakan');
        $this->postJson('/coding/latihan-2/submit', ['files' => $this->files()])->assertOk()
            ->assertJsonPath('score', 85)->assertJsonPath('status', 'failed')->assertJsonPath('completed', false);
        $this->get('/coding')->assertOk()->assertSee('Belum Lulus')->assertSee('Belum Dikerjakan');
        $this->get('/coding/latihan-2')->assertOk()->assertSee('Belum Lulus')->assertDontSee('Belum Dikerjakan');
        $this->get('/dashboard')->assertOk()->assertViewHas('dashboard', fn ($data) => $data['coding']['exercises'][1]['status_label'] === 'Belum Lulus');
        $this->actingAs(User::factory()->create())->get('/coding/latihan-2')->assertOk()
            ->assertSee('Belum Dikerjakan')->assertDontSee('Belum Lulus');
    }

    public function test_corrupt_or_old_rubric_cannot_publish_a_pass(): void
    {
        $good = $this->report();
        $missing = $duplicate = $weight = $order = $legacy = $good;
        array_pop($missing['checks']);
        $duplicate['checks'][0] = $duplicate['checks'][1];
        $weight['checks'][0]['weight'] = 100;
        $order['checks'] = array_reverse($order['checks']);
        $legacy['exercise_key'] = 'coding-latihan-2';
        foreach ([$missing, $duplicate, $weight, $order, $legacy] as $current) {
            Http::fake(['grader.test/grade' => Http::response($current)]);
            $this->actingAs(User::factory()->create())->postJson('/coding/latihan-2/submit', ['files' => $this->files()])->assertStatus(503);
        }
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_legacy_pass_is_retained_in_history_but_not_active_progress(): void
    {
        $active = Exercise::where('exercise_key', self::KEY)->sole();
        $legacy = $active->replicate();
        $legacy->exercise_key = 'coding-latihan-2';
        $legacy->title = 'Latihan 2 versi lama';
        $legacy->instruction = 'Instruksi lama tetap utuh';
        $legacy->save();
        $user = User::factory()->create();
        $old = $user->exerciseSubmissions()->create([
            'exercise_id' => $legacy->id, 'code' => '{"entry_file":"main.py","files":{"main.py":"kode lama"}}',
            'output' => 'Output lama', 'status' => 'passed', 'score' => 100, 'submitted_at' => now(),
        ]);
        $before = $old->fresh()->getAttributes();
        $legacyBefore = $legacy->fresh()->getAttributes();
        $this->seed(CodingExerciseSeeder::class);
        $this->assertSame($before, $old->fresh()->getAttributes());
        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
        $this->assertSame($active->id, Exercise::where('exercise_key', self::KEY)->sole()->id);
        $summary = app(CodingProgressService::class)->forUser($user);
        $this->assertSame(0, $summary['progress_percent']);
        $this->assertStringContainsString('versi lama', $summary['recent_activity'][0]['title']);
        $this->actingAs($user)->get('/coding/latihan-2')->assertOk()->assertSee('Belum Dikerjakan');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/users/'.$user->id)->assertOk()->assertSee('versi lama')->assertSee('Belum Dikerjakan');
        $report = $this->report();
        $report['checks'][6]['passed'] = false;
        Http::fake(['grader.test/grade' => Http::response($report)]);
        $this->actingAs($user)->postJson('/coding/latihan-2/submit', ['files' => $this->files()])->assertOk()
            ->assertJsonPath('status', 'passed')->assertJsonPath('score', 90)->assertJsonPath('completed', true);
        $this->assertSame(50, app(CodingProgressService::class)->forUser($user)['progress_percent']);
        $this->assertSame($before, $old->fresh()->getAttributes());
    }
}
