<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateOrder;
use App\Models\Exercise;
use App\Models\FinalExamAttempt;
use App\Models\User;
use App\Services\Admin\UserDeletionService;
use App\Services\CertificateEligibilityService;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use App\Services\CodingProgressService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CertificationFixture;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    private function student(bool $complete = true): User
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create(['name' => 'Peserta OOPy Aman']);
        if ($complete) {
            CertificationFixture::complete($user);
        }

        return $user;
    }

    private function eligibility(User $user): array
    {
        return app(CertificateEligibilityService::class)->forUser($user);
    }

    private function confirmation(array $extra = []): array
    {
        return array_merge(['payment_method' => 'e_wallet', 'simulation_confirmed' => '1'], $extra);
    }

    private function issue(User $user): Certificate
    {
        return app(CertificateIssuanceService::class)->issue($user, 'e_wallet');
    }

    public function test_guest_gets_information_without_fabricated_progress_and_private_routes_require_login(): void
    {
        $this->get('/sertifikasi')->assertOk()->assertSee('Masuk untuk Ambil Sertifikat')->assertDontSee('Belum ada nilai');
        foreach (['/sertifikasi/checkout', '/sertifikat-saya', '/sertifikat-saya/unknown', '/sertifikat-saya/unknown/pdf'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/sertifikasi/checkout', $this->confirmation())->assertRedirect('/login');
        $this->post('/sertifikasi', $this->confirmation())->assertRedirect('/login');
        $this->get('/sertifikasi/unknown/pdf')->assertRedirect('/login');
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_unseeded_and_new_accounts_are_ineligible_and_have_real_empty_states(): void
    {
        $user = User::factory()->create();
        foreach ([false, true] as $seed) {
            if ($seed) {
                $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
            }
            $data = $this->eligibility($user);
            $this->assertFalse($data['eligible']);
            $this->assertSame(6, $data['total_chapters']);
            $this->assertSame(0, $data['passed_quizzes']);
            $this->assertSame(0, $data['passed_coding']);
            $this->assertNull($data['best_final_score']);
            $this->assertCount(15, $data['missing']);
        }
        $this->actingAs($user)->get('/sertifikasi')->assertOk()->assertSee('Belum memenuhi syarat')
            ->assertViewHas('certificate', null)->assertDontSee('data-simulation-form', false);
        $this->get('/sertifikat-saya')->assertRedirect('/sertifikasi#sertifikat');
        $this->get('/sertifikasi/checkout')->assertRedirect('/sertifikasi');
        $this->post('/sertifikasi', $this->confirmation())->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('certificate_orders', 0);
    }

    public static function missingRequirements(): array
    {
        return [['material'], ['quiz_failed'], ['quiz_in_progress'], ['quiz_expired'], ['quiz_missing'],
            ['coding_1'], ['coding_2'], ['legacy_coding'], ['final_missing'], ['final_pending'], ['final_in_progress']];
    }

    #[DataProvider('missingRequirements')]
    public function test_each_missing_or_unofficial_requirement_prevents_checkout(string $missing): void
    {
        $user = $this->student();
        if ($missing === 'material') {
            $user->progress()->firstOrFail()->update(['status' => 'in_progress', 'completed_at' => null]);
        } elseif (str_starts_with($missing, 'quiz_')) {
            $attempt = $user->quizAttempts()->firstOrFail();
            match ($missing) {
                'quiz_missing' => $attempt->delete(),
                'quiz_failed' => $attempt->update(['score' => 0]),
                'quiz_expired' => $attempt->update(['status' => 'expired']),
                default => $attempt->update(['status' => 'in_progress', 'completed_at' => null]),
            };
        } elseif (str_starts_with($missing, 'coding_') || $missing === 'legacy_coding') {
            $key = CodingProgressService::EXERCISES[$missing === 'coding_1' ? 1 : 2]['key'];
            $submission = $user->exerciseSubmissions()->where('exercise_id', Exercise::where('exercise_key', $key)->sole()->id)->sole();
            $submission->update(['status' => 'failed', 'score' => 0]);
            if ($missing === 'legacy_coding') {
                $legacy = Exercise::create(['material_id' => Exercise::firstOrFail()->material_id, 'exercise_key' => 'coding-latihan-2', 'title' => 'Versi lama', 'instruction' => 'Fixture', 'starter_code' => 'pass']);
                $user->exerciseSubmissions()->create(['exercise_id' => $legacy->id, 'code' => 'legacy', 'status' => 'passed', 'score' => 100]);
            }
        } else {
            $final = FinalExamAttempt::where('user_id', $user->id)->sole();
            if ($missing === 'final_missing') {
                $final->delete();
            } else {
                $final->update(['status' => $missing === 'final_pending' ? 'pending_review' : 'in_progress']);
            }
        }
        $this->assertFalse($this->eligibility($user)['eligible']);
        $this->actingAs($user)->get('/sertifikasi/checkout')->assertRedirect('/sertifikasi');
        $this->post('/sertifikasi/checkout', $this->confirmation())->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('certificates', 0);
    }

    public static function finalScores(): array
    {
        return [[65, false], [70, true], [100, true]];
    }

    #[DataProvider('finalScores')]
    public function test_final_score_boundary_comes_from_official_completed_attempt(float $score, bool $eligible): void
    {
        $user = $this->student();
        FinalExamAttempt::where('user_id', $user->id)->sole()->update(['total_score' => $score]);
        $this->assertSame($eligible, $this->eligibility($user)['eligible']);
        $this->assertSame($score, $this->eligibility($user)['best_final_score']);
    }

    public function test_stored_final_threshold_is_honored_and_pending_score_is_excluded(): void
    {
        $user = $this->student();
        $final = FinalExamAttempt::where('user_id', $user->id)->sole();
        $final->update(['total_score' => 70, 'passing_score' => 80]);
        $this->assertFalse($this->eligibility($user)['eligible']);
        $final->update(['total_score' => 100, 'status' => 'pending_review']);
        $this->assertNull($this->eligibility($user)['best_final_score']);
    }

    public function test_visits_and_guest_practice_never_qualify_an_account(): void
    {
        $user = $this->student(false);
        $this->actingAs($user)->get('/materi/dasar-pemrograman-oop')->assertOk();
        $this->withSession(['final_exam_practice' => ['fake' => ['status' => 'completed', 'total_score' => 100]]]);
        $this->assertFalse($this->eligibility($user)['eligible']);
        $this->assertFalse($this->eligibility($user)['final_passed']);
    }

    public function test_valid_previous_passes_survive_failed_retries_and_completion_date_is_stable(): void
    {
        $user = $this->student();
        $completed = $this->eligibility($user)['completed_at'];
        $this->travel(2)->days();
        foreach ($user->exerciseSubmissions()->get() as $submission) {
            $user->exerciseSubmissions()->create(['exercise_id' => $submission->exercise_id, 'code' => 'retry', 'status' => 'failed', 'score' => 0]);
        }
        $quiz = $user->quizAttempts()->firstOrFail();
        $user->quizAttempts()->create(['quiz_id' => $quiz->quiz_id, 'status' => 'completed', 'score' => 0, 'completed_at' => now()]);
        $this->assertTrue($this->eligibility($user)['eligible']);
        $this->assertTrue($completed->equalTo($this->eligibility($user)['completed_at']));
    }

    public function test_eligible_checkout_issues_one_certificate_and_retries_do_not_change_it(): void
    {
        $user = $this->student();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Ambil Sertifikat')->assertDontSee('Sertifikat Saya');
        $this->get('/sertifikasi')->assertOk()->assertSee('Ambil Sertifikat')->assertSee($user->email)
            ->assertSee('data-simulation-form', false)->assertSee('Rp24.999');
        $this->get('/sertifikasi/checkout')->assertRedirect('/sertifikasi#penerbitan');
        $this->post('/sertifikasi', $this->confirmation())->assertRedirect('/sertifikasi#sertifikat');
        $certificate = Certificate::sole();
        $attributes = $certificate->getAttributes();
        $user->update(['name' => 'Nama Baru']);
        $this->post('/sertifikasi', $this->confirmation(['payment_method' => 'card']))->assertRedirect('/sertifikasi#sertifikat');
        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseCount('certificate_orders', 1);
        $this->assertSame($attributes, $certificate->fresh()->getAttributes());
        $this->assertDatabaseHas('certificate_orders', ['amount' => 24999, 'mode' => 'simulation', 'status' => 'simulation_completed', 'currency' => 'IDR', 'payment_method' => 'e_wallet']);
        $this->get('/sertifikasi/checkout')->assertRedirect('/sertifikasi#sertifikat');
        $this->get('/sertifikat-saya')->assertRedirect('/sertifikasi#sertifikat');
        $this->get('/sertifikasi')->assertSee('Unduh Sertifikat PDF')->assertSee($certificate->certificate_number)
            ->assertDontSee('data-simulation-form', false);
    }

    public function test_manually_reviewed_final_is_only_complete_at_the_official_grading_time(): void
    {
        $user = $this->student();
        $this->travel(2)->days();
        $gradedAt = now()->startOfSecond();
        FinalExamAttempt::where('user_id', $user->id)->sole()->update(['graded_at' => $gradedAt]);
        $this->assertTrue($gradedAt->equalTo($this->eligibility($user)['completed_at']));
    }

    public function test_inline_confirmation_reuses_pending_order_and_shows_the_issued_document(): void
    {
        $user = $this->student();
        $order = CertificateOrder::create([
            'user_id' => $user->id, 'program_key' => CertificateIssuanceService::PROGRAM,
            'order_number' => 'SIM-PENDING-FIXTURE', 'amount' => 24999, 'currency' => 'IDR',
            'payment_method' => 'e_wallet', 'mode' => 'simulation', 'status' => 'pending',
        ]);
        $this->actingAs($user)->get('/sertifikasi')->assertViewHas('certificate', null)->assertSee('data-simulation-form', false);
        $this->post('/sertifikasi', $this->confirmation())->assertRedirect('/sertifikasi#sertifikat');
        $this->assertSame($order->id, Certificate::sole()->order_id);
        $this->assertDatabaseCount('certificate_orders', 1);
        $this->get('/sertifikasi')->assertSee('Unduh Sertifikat PDF')->assertSee('Simulasi Selesai');
    }

    public function test_unique_numbers_and_tokens_differ_across_users(): void
    {
        $user = $this->student();
        $first = $this->issue($user);
        $other = User::factory()->create();
        CertificationFixture::complete($other);
        $second = $this->issue($other);
        $this->assertNotSame($first->public_id, $second->public_id);
        $this->assertNotSame($first->certificate_number, $second->certificate_number);
        $this->assertNotSame($first->order->order_number, $second->order->order_number);
    }

    public static function hostileFields(): array
    {
        return [['amount', 1], ['user_id', 999], ['status', 'paid'], ['payment_status', 'paid'],
            ['mode', 'live'], ['program_key', 'other'], ['payment_method', 'unknown'], ['simulation_confirmed', '0']];
    }

    #[DataProvider('hostileFields')]
    public function test_confirmation_rejects_forged_fields_and_requires_conscious_consent(string $field, mixed $value): void
    {
        $user = $this->student();
        $this->actingAs($user)->postJson('/sertifikasi', $this->confirmation([$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('certificate_orders', 0);
    }

    public function test_eligibility_is_rechecked_after_opening_checkout(): void
    {
        $user = $this->student();
        $this->actingAs($user)->get('/sertifikasi')->assertOk();
        FinalExamAttempt::where('user_id', $user->id)->update(['status' => 'pending_review']);
        $this->post('/sertifikasi', $this->confirmation())->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('certificate_orders', 0);
    }

    public function test_csrf_missing_or_wrong_token_rejects_mutation(): void
    {
        $user = $this->student();
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->actingAs($user)->post('/sertifikasi', $this->confirmation())->assertStatus(419);
        $this->post('/sertifikasi', $this->confirmation(['_token' => 'incorrect']))->assertStatus(419);
        $this->post('/sertifikasi/checkout', $this->confirmation())->assertStatus(419);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_other_users_cannot_use_progress_or_read_private_certificate_and_pdf(): void
    {
        $owner = $this->student();
        $certificate = $this->issue($owner);
        $other = User::factory()->create();
        $this->actingAs($other)->get('/sertifikasi?user_id='.$owner->id)->assertViewHas('eligibility', fn ($data) => ! $data['eligible']);
        $this->get('/sertifikasi?user_id='.$owner->id)->assertDontSee($certificate->certificate_number);
        $this->get('/sertifikat-saya?user_id='.$owner->id)->assertRedirect('/sertifikasi#sertifikat');
        $this->get('/sertifikasi/'.$certificate->public_id.'/pdf')->assertNotFound();
        foreach (['', '/pdf'] as $suffix) {
            $this->get('/sertifikat-saya/'.$certificate->public_id.$suffix)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    public function test_pdf_is_valid_landscape_downloadable_again_and_private(): void
    {
        $user = $this->student();
        $certificate = $this->issue($user);
        foreach (range(1, 2) as $retry) {
            $response = $this->actingAs($user)->get('/sertifikasi/'.$certificate->public_id.'/pdf')
                ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
            $pdf = $response->getContent();
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertStringContainsString('%%EOF', $pdf);
            $this->assertSame(1, preg_match('/\/MediaBox\s*\[([\d.\s]+)\]/', $pdf, $box));
            $dimensions = preg_split('/\s+/', trim($box[1]));
            $this->assertEqualsWithDelta(841.89, (float) $dimensions[2], .02);
            $this->assertEqualsWithDelta(595.28, (float) $dimensions[3], .02);
            $this->assertGreaterThan(15000, strlen($pdf));
        }
        $html = view('certification.pdf', ['certificate' => $certificate, 'qr' => app(CertificatePdfService::class)->qrDataUri($certificate), 'logo' => ''])->render();
        $this->assertStringContainsString($user->name, $html);
        $this->assertStringContainsString($certificate->certificate_number, $html);
        $this->assertStringContainsString('SIMULASI / DEMO', $html);
        $this->get('/sertifikasi')->assertOk()->assertSee('QR Code menuju verifikasi');
        $this->get('/sertifikat-saya/'.$certificate->public_id)->assertRedirect('/sertifikasi#sertifikat');
    }

    public function test_public_verification_is_read_only_escapes_names_and_hides_private_data(): void
    {
        $user = $this->student();
        $user->update(['name' => '<script>alert("x")</script>']);
        $certificate = $this->issue($user);
        $before = $certificate->fresh()->getAttributes();
        $this->get('/sertifikat/verifikasi/'.$certificate->public_id)->assertOk()
            ->assertSee($user->name)->assertDontSee($user->name, false)->assertSee('Valid sebagai sertifikat simulasi')
            ->assertDontSee($user->email)->assertDontSee($certificate->order->order_number)->assertDontSee('user_id');
        $this->assertSame($before, $certificate->fresh()->getAttributes());
        $this->get('/sertifikat/verifikasi/missing')->assertNotFound()->assertSee('Sertifikat tidak ditemukan');
        $this->post('/sertifikat/verifikasi/'.$certificate->public_id)->assertStatus(405);
    }

    public function test_revoked_certificate_is_visible_but_cannot_be_downloaded_or_reissued(): void
    {
        $user = $this->student();
        $certificate = $this->issue($user);
        $certificate->update(['status' => 'revoked']);
        $this->get('/sertifikat/verifikasi/'.$certificate->public_id)->assertOk()->assertSee('Dibatalkan');
        $this->actingAs($user)->get('/sertifikasi/'.$certificate->public_id.'/pdf')->assertStatus(409);
        $this->post('/sertifikasi', $this->confirmation())->assertStatus(409);
        $this->get('/sertifikasi')->assertSee('Dibatalkan')->assertDontSee('Unduh Sertifikat PDF');
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_transaction_rolls_back_order_when_certificate_insert_fails(): void
    {
        $user = $this->student();
        DB::unprepared("CREATE TRIGGER certificate_failure BEFORE INSERT ON certificates BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        $this->actingAs($user)->post('/sertifikasi', $this->confirmation())->assertStatus(500);
        $this->assertDatabaseCount('certificate_orders', 0);
        $this->assertDatabaseCount('certificates', 0);
        DB::unprepared('DROP TRIGGER certificate_failure');
        $this->post('/sertifikasi', $this->confirmation())->assertRedirect('/sertifikasi#sertifikat');
    }

    public function test_database_constraint_rejects_duplicate_user_program_even_with_different_tokens(): void
    {
        $certificate = $this->issue($this->student());
        $this->expectException(QueryException::class);
        $certificate->replicate()->fill(['public_id' => (string) Str::uuid(), 'certificate_number' => 'another-number'])->save();
    }

    public function test_account_deletion_removes_certificates_orders_and_public_participant_data_only_for_target(): void
    {
        $user = $this->student();
        $certificate = $this->issue($user);
        $other = User::factory()->create();
        CertificationFixture::complete($other);
        $otherCertificate = $this->issue($other);
        app(UserDeletionService::class)->delete($user);
        $this->assertDatabaseMissing('certificates', ['public_id' => $certificate->public_id]);
        $this->assertDatabaseMissing('certificate_orders', ['user_id' => $user->id]);
        $this->assertModelExists($otherCertificate);
        $this->get('/sertifikat/verifikasi/'.$certificate->public_id)->assertNotFound();
    }
}
