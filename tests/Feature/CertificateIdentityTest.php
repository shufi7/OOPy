<?php

namespace Tests\Feature;

use App\Models\CertificateIdentity;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CertificateIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_name_can_be_confirmed_only_with_consent_and_a_matching_name(): void
    {
        $user = User::factory()->create(['name' => 'Nadia Dwi']);
        $this->actingAs($user)->get('/sertifikasi')->assertOk()->assertSee('Konfirmasi Nama Sertifikat')
            ->assertSee('Nadia Dwi')->assertSee('PRATINJAU')->assertDontSee('QR Code menuju verifikasi');
        $this->postJson('/sertifikasi/nama/konfirmasi', ['confirmed_name' => 'Nadia Dwi'])
            ->assertUnprocessable()->assertJsonValidationErrors('name_confirmed');
        $this->postJson('/sertifikasi/nama/konfirmasi', ['confirmed_name' => 'Nama Palsu', 'name_confirmed' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('confirmed_name');
        $this->assertDatabaseCount('certificate_identities', 0);
        $this->post('/sertifikasi/nama/konfirmasi', ['confirmed_name' => 'Nadia Dwi', 'name_confirmed' => '1'])
            ->assertRedirect('/sertifikasi#nama-sertifikat')->assertSessionHasNoErrors();
        $identity = CertificateIdentity::findOrFail($user->id);
        $this->assertSame('Nadia Dwi', $identity->participant_name);
        $this->assertNotNull($identity->confirmed_at);
    }

    public function test_name_change_preserves_profile_and_other_accounts_and_clears_confirmation(): void
    {
        $owner = User::factory()->create(['name' => 'Nadia Dwi']);
        $other = User::factory()->create(['name' => 'Nama Akun Lain']);
        $this->actingAs($other)->post('/sertifikasi/nama/konfirmasi', [
            'confirmed_name' => $other->name, 'name_confirmed' => '1',
        ])->assertSessionHasNoErrors();
        $originalOther = CertificateIdentity::findOrFail($other->id)->getAttributes();
        $this->actingAs($owner)->post('/sertifikasi/nama/konfirmasi', [
            'confirmed_name' => $owner->name, 'name_confirmed' => '1',
        ])->assertSessionHasNoErrors();

        $name = 'Nadía O’Rana-Sari W.';
        $this->post('/sertifikasi/nama', ['participant_name' => '  Nadía   O’Rana-Sari  W.  ',
            'password' => 'password', 'user_id' => $other->id])->assertRedirect('/sertifikasi#nama-sertifikat')
            ->assertSessionHas('certificate_status', 'Nama sertifikat berhasil diperbarui. Silakan periksa dan konfirmasi kembali nama Anda.');
        $identity = CertificateIdentity::findOrFail($owner->id);
        $this->assertSame($name, $identity->participant_name);
        $this->assertNull($identity->confirmed_at);
        $this->assertSame($owner->name, $owner->fresh()->name);
        $this->assertSame($originalOther, CertificateIdentity::findOrFail($other->id)->getAttributes());
        $this->get('/sertifikasi')->assertSee($name)->assertSee('Konfirmasi Nama')->assertDontSee('Nama sudah dikonfirmasi');
    }

    public static function invalidNames(): array
    {
        return [[''], [' '], ['A'], ['Nama 123'], ['<script>Nama</script>'], ["\tNama Peserta"], ["Nama\u{202E} Peserta"], [str_repeat('A', 101)]];
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected(string $name): void
    {
        $this->actingAs(User::factory()->create())->postJson('/sertifikasi/nama', [
            'participant_name' => $name, 'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('participant_name');
        $this->assertDatabaseCount('certificate_identities', 0);
    }

    public function test_wrong_and_missing_passwords_are_rejected_without_password_flash(): void
    {
        $this->actingAs(User::factory()->create());
        $this->from('/sertifikasi')->post('/sertifikasi/nama', ['participant_name' => 'Nama Valid', 'password' => 'secret-wrong'])
            ->assertRedirect('/sertifikasi')->assertSessionHasErrorsIn('certificateName', [
                'password' => 'Password yang Anda masukkan salah. Silakan coba kembali.',
            ])->assertSessionMissing('_old_input.password');
        $this->postJson('/sertifikasi/nama', ['participant_name' => 'Nama Valid'])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('certificate_identities', 0);
    }

    public function test_name_update_requires_login_csrf_and_limits_password_attempts(): void
    {
        $payload = ['participant_name' => 'Nama Valid', 'password' => 'secret-wrong'];
        $this->postJson('/sertifikasi/nama', $payload)->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->postJson('/sertifikasi/nama', $payload)->assertStatus(419);
        $this->withSession(['_token' => 'csrf-fixture']);
        $this->withHeader('X-CSRF-TOKEN', 'csrf-fixture');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/sertifikasi/nama', $payload)->assertUnprocessable();
        }
        $this->postJson('/sertifikasi/nama', $payload)->assertTooManyRequests();
    }
}
