<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\User;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Use only an isolated in-memory DB; never reset the application database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertExitCode(0);
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        config(['coding.grader_url' => 'http://grader.test', 'coding.grader_token' => str_repeat('t', 40)]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function payload(): array
    {
        return ['files' => ['main.py' => "print('contoh snapshot')"]];
    }

    private function report(bool $passed = true, string $slug = 'latihan-1'): array
    {
        $catalog = require resource_path('coding/exercises.php');

        return ['version' => 2, 'exercise_key' => 'coding-'.$slug, 'status' => 'graded', 'output' => 'Output resmi',
            'checks' => array_map(fn ($id) => ['id' => $id, 'label' => $id, 'required' => $id !== 'presentation',
                'weight' => $id === 'presentation' ? 10 : 15, 'passed' => $passed,
                'feedback' => $passed ? 'Sudah sesuai.' : 'Perbaiki '.$id.'.'], [...$catalog[$slug]['core_checks'], 'presentation'])];
    }

    public function test_public_catalog_navbar_and_both_exercises_reuse_live_code(): void
    {
        $this->get('/')->assertOk()->assertSee('href="'.route('coding.index').'"', false);
        $this->get('/coding')->assertOk()->assertSeeInOrder(['Materi', 'Coding', 'Masuk', 'Daftar'])
            ->assertSee('Latihan Coding OOPy')->assertSee('Membuat Class dan Object')->assertSee('Pewarisan dan Method Overriding')
            ->assertSee('/coding/latihan-1')->assertSee('/coding/latihan-2');
        foreach (['latihan-1' => 'SensorAir', 'latihan-2' => 'Ekosistem'] as $slug => $name) {
            $html = $this->get('/coding/'.$slug)->assertOk()->assertSee($name)->assertSee('Masuk untuk menyimpan hasil latihan')
                ->assertSee('Output Program')->getContent();
            $this->assertSame(1, substr_count($html, 'data-live-code'));
            $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
            $this->assertLessThan(strpos($html, 'data-role="monaco-editor"'), strpos($html, 'Soal Latihan'));
            preg_match('/data-role="config">(.*?)<\/script>/s', $html, $match);
            $config = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertTrue($config['persistence_enabled']);
            $this->assertFalse($config['authenticated']);
            $this->assertSame(['main.py' => ''], $config['files']);
            $this->assertTrue($config['require_code']);
            $this->assertFalse($config['show_activity_label']);
            $this->assertStringNotContainsString(config('coding.grader_token'), $html);
            $this->assertStringNotContainsString('secrets.randbelow', $html);
        }
        $this->get('/coding/latihan-3')->assertNotFound();
        $this->get('/coding/unknown')->assertNotFound();
    }

    public function test_blank_editor_contract_and_instructions_match_official_requirements(): void
    {
        foreach (['latihan-1', 'latihan-2'] as $slug) {
            $this->get('/coding/'.$slug)->assertOk()->assertSeeInOrder([
                'Aktivitas Live Coding', 'Soal Latihan', 'Deskripsi Soal', 'Instruksi Pengerjaan',
                'Ketentuan Program', 'Contoh Output', 'Kriteria Keberhasilan', 'Editor Python',
            ])->assertDontSee('TODO')->assertDontSee('AKTIVITAS LIVE CODING');
            $record = Exercise::where('exercise_key', 'coding-'.$slug)->sole();
            $this->assertSame('', $record->starter_code);
        }
        $this->get('/coding/latihan-1')->assertSee('6.5')->assertSee('8.5')->assertSee('Perlu pemeriksaan')
            ->assertSee('Status air: Normal')->assertSee('ph terbaru');
        $this->get('/coding/latihan-2')->assertSee('Status belum ditentukan')->assertSee('Genangan rawa dipantau')
            ->assertSee('super()')->assertSee('Kalimantan Selatan');
    }

    public function test_guests_cannot_submit_and_reading_never_creates_a_submission(): void
    {
        $this->postJson('/coding/latihan-1/submit', $this->payload())->assertUnauthorized();
        $this->post('/coding/latihan-1/submit', $this->payload())->assertRedirect('/login');
        $this->assertDatabaseCount('exercise_submissions', 0);
        Http::assertNothingSent();
    }

    public function test_official_grading_saves_snapshot_and_session_owner_only(): void
    {
        Http::fake(['grader.test/grade' => Http::response($this->report())]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/coding/latihan-1/submit', $this->payload())
            ->assertOk()->assertJsonPath('status', 'passed')->assertJsonPath('score', 100)->assertJsonPath('completed', true);
        $submission = ExerciseSubmission::sole();
        $this->assertSame($user->id, $submission->user_id);
        $this->assertSame('coding-latihan-1', $submission->exercise->exercise_key);
        $this->assertSame($this->payload()['files'], json_decode($submission->code, true)['files']);
        $this->assertNotNull($submission->submitted_at);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.config('coding.grader_token'))
            && $request['exercise_key'] === 'coding-latihan-1' && $request['files'] === $this->payload()['files']);
    }

    public function test_failed_submission_is_not_complete_and_second_exercise_needs_no_unlock(): void
    {
        Http::fake(['grader.test/grade' => Http::response($this->report(false, 'latihan-2'))]);
        $this->actingAs(User::factory()->create())->postJson('/coding/latihan-2/submit', $this->payload())
            ->assertOk()->assertJsonPath('status', 'failed')->assertJsonPath('completed', false);
        $this->assertDatabaseHas('exercise_submissions', ['status' => 'failed', 'score' => 0]);
        $this->get('/coding')->assertDontSee('Kerjakan Lagi')->assertSee('Belum Dikerjakan');
    }

    public function test_source_whitespace_is_preserved_in_grader_request_and_database_snapshot(): void
    {
        Http::fake(['grader.test/grade' => Http::response($this->report())]);
        $source = "\n\nprint('source snapshot')\n\n";
        $this->actingAs(User::factory()->create())->postJson('/coding/latihan-1/submit', ['files' => ['main.py' => $source]])
            ->assertOk();
        $this->assertSame($source, json_decode(ExerciseSubmission::sole()->code, true)['files']['main.py']);
        Http::assertSent(fn ($request) => $request['files']['main.py'] === $source);
    }

    public function test_exercise_two_can_be_completed_first_and_partial_checks_do_not_pass(): void
    {
        $partial = $this->report(false);
        $partial['checks'][0]['passed'] = true;
        $partial['checks'][1]['passed'] = true;
        Http::fake(['grader.test/grade' => Http::sequence()->push($this->report(true, 'latihan-2'))->push($partial)]);
        $this->actingAs(User::factory()->create());
        $this->postJson('/coding/latihan-2/submit', $this->payload())->assertOk()
            ->assertJsonPath('status', 'passed')->assertJsonPath('completed', true);
        $this->postJson('/coding/latihan-1/submit', $this->payload())->assertOk()
            ->assertJsonPath('status', 'failed')->assertJsonPath('score', 30)->assertJsonPath('completed', false);
        $this->assertDatabaseHas('exercise_submissions', ['status' => 'passed', 'score' => 100]);
        $this->assertDatabaseHas('exercise_submissions', ['status' => 'failed', 'score' => 30]);
    }

    public function test_pass_remains_after_failed_retry_refresh_logout_login_and_is_private(): void
    {
        Http::fake(['grader.test/grade' => Http::sequence()->push($this->report())->push($this->report(false))]);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->postJson('/coding/latihan-1/submit', $this->payload())->assertOk();
        $this->postJson('/coding/latihan-1/submit', $this->payload())->assertOk()->assertJsonPath('status', 'failed')->assertJsonPath('completed', true);
        $this->get('/coding')->assertSee('Selesai')->assertSee('Kerjakan Lagi');
        $this->post('/logout')->assertRedirect('/');
        Auth::forgetGuards();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
        $this->get('/coding')->assertSee('Kerjakan Lagi');
        $this->get('/coding/latihan-1')->assertSee('✓ Selesai');
        $this->actingAs($other)->get('/coding')->assertDontSee('Kerjakan Lagi');
        $this->get('/coding/latihan-1')->assertDontSee('✓ Selesai')->assertDontSee('contoh snapshot');
        $this->get('/coding/submissions/1')->assertNotFound();
        $this->assertDatabaseCount('exercise_submissions', 2);
    }

    public function test_forged_identity_and_grading_fields_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/coding/latihan-1/submit', $this->payload() + ['passed' => true, 'score' => 100, 'user_id' => 99, 'role' => 'admin', 'status' => 'passed', 'exercise_id' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors(['passed', 'score', 'user_id', 'role', 'status', 'exercise_id']);
        Http::assertNothingSent();
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_file_validation_rejects_paths_extra_files_empty_and_oversized_code(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([[], ['../main.py' => 'pass'], ['main.py' => []], ['main.py' => ''], ['main.py' => str_repeat('a', 30001)], ['main.py' => 'pass', 'other.py' => 'pass']] as $files) {
            $this->postJson('/coding/latihan-1/submit', ['files' => $files])->assertUnprocessable();
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_runtime_error_and_timeout_reports_never_pass(): void
    {
        $report = $this->report();
        $report['status'] = 'error';
        $report['checks'] = [['id' => 'execution', 'label' => 'Eksekusi program', 'required' => true,
            'weight' => 0, 'passed' => false, 'feedback' => 'ZeroDivisionError']];
        Http::fake(['grader.test/grade' => Http::response($report)]);
        $this->actingAs(User::factory()->create())->postJson('/coding/latihan-1/submit', $this->payload())
            ->assertOk()->assertJsonPath('status', 'error')->assertJsonPath('score', 0)->assertJsonPath('completed', false);
        $this->assertDatabaseHas('exercise_submissions', ['status' => 'error', 'score' => 0]);
    }

    public function test_missing_unavailable_or_malformed_grader_never_saves_a_pass(): void
    {
        $this->actingAs(User::factory()->create());
        config(['coding.grader_url' => null]);
        $this->postJson('/coding/latihan-1/submit', $this->payload())->assertStatus(503);
        config(['coding.grader_url' => 'http://grader.test']);
        $empty = $this->report();
        $empty['checks'] = [];
        $wrong = $this->report(true, 'latihan-2');
        Http::fake(['grader.test/grade' => Http::sequence()->push([], 503)->push(['passed' => true, 'score' => 100])->push($empty)->push($wrong)->pushFailedConnection()]);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/coding/latihan-1/submit', $this->payload())->assertStatus(503);
        }
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_submit_is_rate_limited_and_csrf_protected(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['grader.test/grade' => Http::response($this->report(false))]);
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/coding/latihan-1/submit', $this->payload())->assertOk();
        }
        $this->postJson('/coding/latihan-1/submit', $this->payload())->assertStatus(429);
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->withSession(['_token' => 'real-token'])->post('/coding/latihan-2/submit', $this->payload())->assertStatus(419);
    }

    public function test_presentation_is_optional_but_each_core_competency_is_required(): void
    {
        $currentReport = [];
        Http::fake(function () use (&$currentReport) {
            return Http::response($currentReport);
        });
        foreach (['latihan-1', 'latihan-2'] as $slug) {
            $user = User::factory()->create();
            $optional = $this->report(true, $slug);
            $optional['checks'][6]['passed'] = false;
            $currentReport = $optional;
            $this->actingAs($user)->postJson('/coding/'.$slug.'/submit', $this->payload())->assertOk()
                ->assertJsonPath('status', 'passed')->assertJsonPath('score', 90)->assertJsonPath('completed', true);
            foreach (range(0, 5) as $index) {
                $core = $this->report(true, $slug);
                $core['checks'][$index]['passed'] = false;
                $core['score'] = 100;
                $core['passed'] = true;
                $currentReport = $core;
                $this->actingAs(User::factory()->create())->postJson('/coding/'.$slug.'/submit', $this->payload())->assertOk()
                    ->assertJsonPath('status', 'failed')->assertJsonPath('score', 85)->assertJsonPath('completed', false);
            }
        }
    }

    public function test_incomplete_duplicate_downgraded_and_old_rubrics_are_rejected(): void
    {
        $valid = $this->report();
        $missing = $duplicate = $downgraded = $wrongWeight = $old = $fakeError = $valid;
        array_shift($missing['checks']);
        $duplicate['checks'][1] = $duplicate['checks'][0];
        $downgraded['checks'][0]['required'] = false;
        $wrongWeight['checks'][0]['weight'] = 0;
        $old['version'] = 1;
        $fakeError['status'] = 'error';
        $currentReport = [];
        Http::fake(function () use (&$currentReport) {
            return Http::response($currentReport);
        });
        foreach ([$missing, $duplicate, $downgraded, $wrongWeight, $old, $fakeError] as $report) {
            $currentReport = $report;
            $this->actingAs(User::factory()->create())->postJson('/coding/latihan-1/submit', $this->payload())->assertStatus(503);
        }
        $this->assertDatabaseCount('exercise_submissions', 0);
    }

    public function test_idempotent_seed_preserves_existing_exercises_and_submissions(): void
    {
        Http::fake(['grader.test/grade' => Http::response($this->report())]);
        $this->actingAs(User::factory()->create())->postJson('/coding/latihan-1/submit', $this->payload())->assertOk();
        $ids = Exercise::orderBy('id')->pluck('id', 'exercise_key')->all();
        $this->seed(CodingExerciseSeeder::class);
        $this->seed(CodingExerciseSeeder::class);
        $this->assertSame($ids, Exercise::orderBy('id')->pluck('id', 'exercise_key')->all());
        $this->assertDatabaseCount('exercise_submissions', 1);
        $this->assertSame(9, Exercise::count());
    }
}
