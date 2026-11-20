<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificatePaymentAttempt;
use App\Models\User;
use App\Services\Admin\UserDeletionService;
use App\Services\CertificateIssuanceService;
use App\Services\CertificatePdfService;
use App\Services\MidtransGateway;
use App\Services\MidtransPaymentService;
use Database\Seeders\CodingExerciseSeeder;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CertificationFixture;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'SB-Mid-server-test-fixture-only';

    protected function setUp(): void
    {
        parent::setUp();
        config(['midtrans.is_production' => false, 'midtrans.server_key' => self::KEY,
            'midtrans.client_key' => 'SB-Mid-client-test-fixture-only', 'midtrans.signature_secret_key' => 'different-test-signature-secret',
            'midtrans.merchant_id' => 'TEST-MERCHANT']);
        Http::preventStrayRequests();
    }

    private function student(bool $complete = true): User
    {
        $this->seed([OopyContentSeeder::class, CodingExerciseSeeder::class]);
        $user = User::factory()->create();
        if ($complete) {
            CertificationFixture::complete($user);
        }

        return $user;
    }

    private function snap(): void
    {
        $this->fake([MidtransGateway::SNAP_URL => Http::response(['token' => 'sandbox-fixture-token-123456',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/sandbox-fixture-token-123456'])]);
    }

    private function fake(array|callable $callbacks): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($callbacks);
    }

    private function start(User $user): CertificatePaymentAttempt
    {
        $this->snap();
        $id = $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertOk()->json('payment.id');

        return CertificatePaymentAttempt::where('public_id', $id)->sole();
    }

    private function data(CertificatePaymentAttempt $attempt, string $status = 'settlement', array $changes = []): array
    {
        return array_replace(['order_id' => $attempt->order_number, 'gross_amount' => '24999.00', 'currency' => 'IDR',
            'transaction_id' => 'transaction-fixture-123', 'merchant_id' => 'TEST-MERCHANT', 'payment_type' => 'qris',
            'status_code' => $status === 'pending' ? '201' : '200', 'transaction_status' => $status, 'fraud_status' => 'accept'], $changes);
    }

    private function mockStatus(CertificatePaymentAttempt $attempt, array $data): void
    {
        $this->fake([MidtransGateway::STATUS_URL.'*' => Http::response($data)]);
    }

    private function notify(CertificatePaymentAttempt $attempt, array $data, ?string $key = null)
    {
        $data['signature_key'] = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].($key ?? 'different-test-signature-secret'));

        return $this->postJson('/api/payments/midtrans/notification', $data);
    }

    public function test_guest_and_ineligible_accounts_cannot_create_payments(): void
    {
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertUnauthorized();
        $this->actingAs($this->student(false))->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('eligibility');
        Http::assertNothingSent();
        $this->assertDatabaseCount('certificate_orders', 0);
    }

    public function test_server_price_identity_sandbox_host_and_qris_channel_are_fixed_and_pending_is_reused(): void
    {
        $user = $this->student();
        $attempt = $this->start($user);
        $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])
            ->assertOk()->assertJsonPath('payment.id', $attempt->public_id)->assertHeader('Cache-Control', 'no-store, private');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === MidtransGateway::SNAP_URL
            && $request['transaction_details']['gross_amount'] === 24999
            && $request['transaction_details']['order_id'] === $attempt->order_number
            && $request['customer_details']['email'] === $user->email && $request['item_details'][0]['quantity'] === 1
            && $request['enabled_payments'] === ['gopay']);
        $this->assertDatabaseCount('certificate_orders', 1);
        $this->assertDatabaseCount('certificate_payment_attempts', 1);
        $this->assertDatabaseCount('certificates', 0);
        $this->assertStringNotContainsString($attempt->snap_token, DB::table('certificate_payment_attempts')->value('snap_token'));
        $this->assertArrayNotHasKey('snap_token', $attempt->toArray());
        $this->get('/sertifikasi')->assertSee('SB-Mid-client-test-fixture-only')->assertDontSee(self::KEY)->assertDontSee('different-test-signature-secret');
    }

    public static function hostileInput(): array
    {
        return [['amount', 1], ['gross_amount', 1], ['currency', 'USD'], ['user_id', 999], ['order_id', 'forged'],
            ['status', 'paid'], ['transaction_status', 'settlement'], ['mode', 'production'], ['program_key', 'forged'], ['sandbox_confirmed', false]];
    }

    #[DataProvider('hostileInput')]
    public function test_browser_cannot_forge_payment_fields(string $field, mixed $value): void
    {
        $this->actingAs($this->student())->postJson('/sertifikasi/payments', array_replace(['sandbox_confirmed' => true], [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        Http::assertNothingSent();
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_production_flag_and_production_keys_fail_closed_without_any_api_call(): void
    {
        $user = $this->student();
        config(['midtrans.is_production' => true]);
        $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(503);
        config(['midtrans.is_production' => false, 'midtrans.server_key' => 'Mid-server-production-fixture']);
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_client_key_prefix_does_not_block_sandbox_but_missing_client_key_does(): void
    {
        $user = $this->student();
        config(['midtrans.client_key' => 'client-fixture-without-old-prefix']);
        $this->start($user);
        Http::assertSentCount(1);
        config(['midtrans.client_key' => '']);
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(503);
        Http::assertSentCount(1);
    }

    public function test_current_snap_v4_redirect_response_is_saved_and_reused_without_another_transaction(): void
    {
        $user = $this->student();
        $url = 'https://app.sandbox.midtrans.com/snap/v4/redirection/sandbox-fixture-token-123456';
        $this->fake([MidtransGateway::SNAP_URL => Http::response(['token' => 'sandbox-fixture-token-123456', 'redirect_url' => $url], 201)]);
        $id = $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])
            ->assertOk()->assertJsonPath('redirect_url', $url)->json('payment.id');
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertOk()->assertJsonPath('payment.id', $id);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('certificate_payment_attempts', 1);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_redirect_validation_accepts_supported_sandbox_paths_and_rejects_untrusted_urls(): void
    {
        $gateway = app(MidtransGateway::class);
        foreach (['vtweb/token-123456789', 'v2/vtweb/token-123456789', 'v4/redirection/token-123456789'] as $path) {
            $this->assertTrue($gateway->safeRedirect('https://app.sandbox.midtrans.com/snap/'.$path));
        }
        foreach (['https://app.midtrans.com/snap/v4/redirection/token-123456789',
            'http://app.sandbox.midtrans.com/snap/v4/redirection/token-123456789',
            'https://app.sandbox.midtrans.com.example.test/snap/v4/redirection/token-123456789',
            'https://user@app.sandbox.midtrans.com/snap/v4/redirection/token-123456789',
            'https://app.sandbox.midtrans.com:8443/snap/v4/redirection/token-123456789',
            'https://app.sandbox.midtrans.com/snap/v4/redirection/token-123456789?url=https://example.test',
            'https://app.sandbox.midtrans.com/snap/v4/redirection/../other',
            'https://app.sandbox.midtrans.com/other/token-123456789'] as $url) {
            $this->assertFalse($gateway->safeRedirect($url));
        }
    }

    public function test_missing_signature_secret_rejects_webhook_without_falling_back_to_server_key(): void
    {
        $attempt = $this->start($this->student());
        config(['midtrans.signature_secret_key' => null]);
        $this->notify($attempt, $this->data($attempt), self::KEY)->assertStatus(503);
        $this->assertDatabaseCount('certificates', 0);
        Http::assertSentCount(1);
    }

    public function test_timeout_keeps_the_same_provider_reference_for_explicit_retry(): void
    {
        $user = $this->student();
        $this->fake(fn () => throw new ConnectionException('Fixture timeout'));
        $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(503);
        $first = CertificatePaymentAttempt::sole();
        $this->snap();
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertOk()->assertJsonPath('payment.id', $first->public_id);
        $this->assertDatabaseCount('certificate_payment_attempts', 1);
        $this->assertSame($first->order_number, $first->fresh()->order_number);
    }

    public function test_api_error_bad_token_and_untrusted_redirect_do_not_issue_certificates(): void
    {
        $user = $this->student();
        foreach ([[500, []], [201, ['token' => 'short', 'redirect_url' => 'https://example.test/']],
            [201, ['token' => 'sandbox-fixture-token-123456', 'redirect_url' => 'https://app.midtrans.com/snap/vtweb/test']]] as [$code, $data]) {
            $this->fake([MidtransGateway::SNAP_URL => Http::response($data, $code)]);
            $response = $this->actingAs($user)->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true]);
            $this->assertContains($response->status(), [502, 503]);
        }
        $this->assertDatabaseCount('certificate_payment_attempts', 1);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_invalid_signature_different_signature_secret_and_unknown_order_are_rejected(): void
    {
        $attempt = $this->start($this->student());
        $data = $this->data($attempt);
        $this->notify($attempt, $data, self::KEY)->assertForbidden();
        $data['order_id'] = 'unknown-fixture';
        $this->notify($attempt, $data)->assertNotFound();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('certificates', 0);
    }

    public static function invalidStatuses(): array
    {
        return [['gross_amount', '1.00'], ['gross_amount', '2499900'], ['currency', 'USD'],
            ['merchant_id', 'OTHER-MERCHANT'], ['order_id', 'other-order'], ['transaction_status', 'unknown']];
    }

    #[DataProvider('invalidStatuses')]
    public function test_mismatched_server_status_cannot_issue_a_certificate(string $field, string $value): void
    {
        $attempt = $this->start($this->student());
        $this->mockStatus($attempt, $this->data($attempt, changes: [$field => $value]));
        $this->notify($attempt, $this->data($attempt))->assertStatus(409);
        $this->assertDatabaseCount('certificates', 0);
        $this->assertSame('pending', $attempt->order->fresh()->status);
    }

    public static function paymentStates(): array
    {
        return [['pending', 'accept', 'qris', false], ['settlement', 'accept', 'qris', true],
            ['capture', 'accept', 'credit_card', true], ['capture', 'challenge', 'credit_card', false],
            ['settlement', 'deny', 'qris', false], ['deny', 'accept', 'qris', false], ['expire', 'accept', 'qris', false],
            ['cancel', 'accept', 'qris', false], ['failure', 'accept', 'qris', false]];
    }

    #[DataProvider('paymentStates')]
    public function test_only_verified_success_issues_a_sandbox_certificate(string $status, string $fraud, string $type, bool $paid): void
    {
        $attempt = $this->start($this->student());
        $data = $this->data($attempt, $status, ['fraud_status' => $fraud, 'payment_type' => $type]);
        $this->mockStatus($attempt, $data);
        $this->notify($attempt, $data)->assertOk();
        $this->assertDatabaseCount('certificates', $paid ? 1 : 0);
        $this->assertSame($paid ? 'paid' : 'pending', $attempt->order->fresh()->status);
        if ($paid) {
            $this->assertSame('SANDBOX / DEMO', Certificate::sole()->demoLabel());
        }
    }

    public function test_frontend_callback_and_missing_core_status_cannot_mark_paid(): void
    {
        $attempt = $this->start($this->student());
        $this->fake([MidtransGateway::STATUS_URL.'*' => Http::response(['status_code' => '404'], 404)]);
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertOk()->assertJsonPath('payment.ready', false)
            ->assertJsonPath('payment.label', 'Belum Dibayar');
        $this->postJson(route('midtrans.status', $attempt->public_id), ['status' => 'paid', 'transaction_status' => 'settlement'])->assertUnprocessable();
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_an_active_creation_lease_rejects_double_click_without_another_provider_request(): void
    {
        $attempt = $this->start($this->student());
        $attempt->update(['snap_token' => null, 'redirect_url' => null, 'status' => 'creating', 'request_started_at' => now()]);
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(409);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('certificate_payment_attempts', 1);
    }

    public function test_status_timeout_and_authentication_error_do_not_mark_paid_or_mask_as_unprocessed(): void
    {
        $attempt = $this->start($this->student());
        $this->fake(fn () => throw new ConnectionException('Fixture status timeout'));
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertStatus(503);
        $this->fake([MidtransGateway::STATUS_URL.'*' => Http::response(['status_code' => '404'], 401)]);
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertStatus(503);
        $this->assertDatabaseCount('certificates', 0);
        $this->assertSame('pending', $attempt->order->fresh()->status);
    }

    public function test_only_the_webhook_bypasses_csrf_and_mismatched_notification_amount_is_rejected(): void
    {
        $attempt = $this->start($this->student());
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertStatus(419);
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertStatus(419);
        $this->mockStatus($attempt, $this->data($attempt));
        $this->notify($attempt, $this->data($attempt, changes: ['gross_amount' => '1.00']))->assertStatus(409);
        Http::assertNothingSent();
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_partial_refund_revokes_download_and_account_deletion_cascades_payment_attempts(): void
    {
        $user = $this->student();
        $attempt = $this->start($user);
        $this->mockStatus($attempt, $this->data($attempt));
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->mockStatus($attempt, $this->data($attempt, 'partial_refund'));
        $this->notify($attempt, $this->data($attempt, 'partial_refund'))->assertOk();
        $this->assertFalse(Certificate::sole()->isDownloadable());
        app(UserDeletionService::class)->delete($user);
        $this->assertDatabaseCount('certificate_payment_attempts', 0);
        $this->assertDatabaseCount('certificate_orders', 0);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_certificate_insert_failure_rolls_back_verified_payment_and_webhook_retry_issues_once(): void
    {
        $attempt = $this->start($this->student());
        $this->mockStatus($attempt, $this->data($attempt));
        DB::unprepared("CREATE TRIGGER reject_sandbox_certificate BEFORE INSERT ON certificates BEGIN SELECT RAISE(ABORT, 'Fixture insert failure'); END");
        $this->notify($attempt, $this->data($attempt))->assertStatus(500);
        $this->assertSame('pending', $attempt->order->fresh()->status);
        $this->assertSame('snap_ready', $attempt->fresh()->status);
        $this->assertDatabaseCount('certificates', 0);
        DB::unprepared('DROP TRIGGER reject_sandbox_certificate');
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_paid_transaction_identity_is_pinned_and_an_existing_paid_certificate_does_not_charge_again(): void
    {
        $attempt = $this->start($this->student());
        $this->mockStatus($attempt, $this->data($attempt));
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertOk()->assertJsonPath('payment.ready', true)->assertJsonPath('snap_token', null);
        Http::assertSentCount(1);
        $this->mockStatus($attempt, $this->data($attempt, changes: ['transaction_id' => 'changed-after-paid']));
        $this->notify($attempt, $this->data($attempt))->assertStatus(409);
        $this->assertSame('paid', $attempt->order->fresh()->status);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_duplicate_out_of_order_webhooks_and_refund_preserve_one_certificate_and_revoke_safely(): void
    {
        $attempt = $this->start($this->student());
        $this->mockStatus($attempt, $this->data($attempt));
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $certificate = Certificate::sole();
        $before = $certificate->getAttributes();
        $this->notify($attempt, $this->data($attempt, 'pending'))->assertOk();
        $this->assertSame($before, $certificate->fresh()->getAttributes());
        $this->mockStatus($attempt, $this->data($attempt, 'pending'));
        $this->notify($attempt, $this->data($attempt, 'pending'))->assertOk();
        $this->assertSame('paid', $attempt->order->fresh()->status);
        $this->mockStatus($attempt, $this->data($attempt, 'refund'));
        $this->notify($attempt, $this->data($attempt, 'refund'))->assertOk();
        $this->assertSame('revoked', $certificate->fresh()->status);
        $this->mockStatus($attempt, $this->data($attempt));
        $this->notify($attempt, $this->data($attempt))->assertOk();
        $this->assertSame('refunded', $attempt->order->fresh()->status);
        $this->assertDatabaseCount('certificates', 1);
        $this->get(route('certificates.download', $certificate->public_id))->assertStatus(409);
    }

    public function test_expired_attempt_can_retry_with_a_new_provider_reference_and_old_attempt_is_retained(): void
    {
        $user = $this->student();
        $attempt = $this->start($user);
        $this->mockStatus($attempt, $this->data($attempt, 'expire'));
        $this->notify($attempt, $this->data($attempt, 'expire'))->assertOk();
        $this->snap();
        $this->fake([MidtransGateway::STATUS_URL.'*' => Http::response($this->data($attempt, 'expire')),
            MidtransGateway::SNAP_URL => Http::response(['token' => 'new-sandbox-fixture-token-123', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/new-sandbox-fixture-token-123'])]);
        $this->postJson('/sertifikasi/payments', ['sandbox_confirmed' => true])->assertOk();
        $this->assertDatabaseCount('certificate_orders', 1);
        $this->assertDatabaseCount('certificate_payment_attempts', 2);
        $this->assertNotSame($attempt->order_number, CertificatePaymentAttempt::latest('id')->first()->order_number);
    }

    public function test_legacy_simulation_is_preserved_and_sandbox_pdf_verification_and_owner_access_work(): void
    {
        $user = $this->student();
        $legacy = app(CertificateIssuanceService::class)->issue($user, 'e_wallet');
        $legacyBefore = $legacy->fresh()->getAttributes();
        $attempt = $this->start($user);
        $this->mockStatus($attempt, $this->data($attempt));
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertOk()->assertJsonPath('payment.ready', true);
        $sandbox = Certificate::where('program_key', MidtransPaymentService::PROGRAM)->sole();
        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
        $this->assertTrue($legacy->fresh()->isDownloadable());
        $this->get('/sertifikasi')->assertSee('Unduh sertifikat simulasi lama')->assertSee('Unduh Sertifikat PDF')->assertSee('SANDBOX / DEMO');
        $response = $this->get(route('certificates.download', $sandbox->public_id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $html = view('certification.pdf', ['certificate' => $sandbox, 'qr' => '', 'logo' => ''])->render();
        $this->assertStringNotContainsString('SANDBOX / DEMO', $html);
        $this->assertStringNotContainsString('Sertifikat demo penyelesaian pembelajaran OOPy', $html);
        $this->assertStringContainsString('data:image/png;base64,', app(CertificatePdfService::class)->qrDataUri($sandbox));
        $this->get(route('certificates.verify', $sandbox->public_id))->assertOk()->assertSee('Sertifikat Sandbox OOPy')->assertDontSee($user->email);
        $this->actingAs(User::factory()->create())->get(route('certificates.download', $sandbox->public_id))->assertNotFound();
        $this->postJson(route('midtrans.status', $attempt->public_id))->assertNotFound();
    }
}
