<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateIdentity;
use App\Models\CertificateNameRevision;
use App\Models\CertificatePaymentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CertificateIdentityService
{
    public function installed(): bool
    {
        return Schema::hasTable('certificate_identities');
    }

    public function forUser(User $user, ?CertificatePaymentAttempt $attempt, ?Certificate $issued): array
    {
        $installed = $this->installed();
        $identity = $installed ? CertificateIdentity::find($user->id) : null;
        $locked = $issued !== null || ($attempt && ! $attempt->canBeReplaced());
        $name = $issued?->currentParticipantName()
            ?? ($locked ? $attempt?->participant_name : null)
            ?? $identity?->participant_name
            ?? (self::normalize($user->name) ?? $user->name);

        return [
            'name' => $name,
            'confirmed' => $identity?->confirmed_at !== null,
            'valid' => self::normalize($name) !== null,
            'locked' => $locked,
            'pre_upgrade_order' => $locked && ! $issued && $attempt?->participant_name === null,
            'available' => $installed,
        ];
    }

    public function confirm(User $user, string $displayedName): void
    {
        $this->update($user, function (User $user, CertificateIdentity $identity) use ($displayedName) {
            $name = $identity->participant_name ?? self::normalize($user->name);
            if ($name === null || $name !== $displayedName) {
                throw ValidationException::withMessages([
                    'confirmed_name' => 'Nama pada halaman sudah berubah atau belum valid. Periksa nama Anda dan konfirmasi kembali.',
                ]);
            }

            $identity->fill(['participant_name' => $name, 'confirmed_at' => $identity->confirmed_at ?? now()]);
        });
    }

    public function change(User $user, string $name, string $password): void
    {
        $this->update($user, function (User $user, CertificateIdentity $identity) use ($name, $password) {
            $this->verifyPassword($user, $password);
            $identity->fill(['participant_name' => $name, 'confirmed_at' => null]);
        });
    }

    public function correct(User $user, string $publicId, string $name, string $password, int $nameRevision): bool
    {
        abort_unless(CertificateNameRevision::installed(), 503, 'Koreksi nama sertifikat belum tersedia.');

        return DB::transaction(function () use ($user, $publicId, $name, $password, $nameRevision) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $certificate = Certificate::where('user_id', $user->id)->where('program_key', MidtransPaymentService::PROGRAM)
                ->where('public_id', $publicId)->with('order')->lockForUpdate()->firstOrFail();
            abort_unless($certificate->isDownloadable(), 409, 'Koreksi nama hanya tersedia untuk sertifikat aktif.');
            $this->verifyPassword($user, $password);

            if (($certificate->currentNameRevision()?->id ?? 0) !== $nameRevision) {
                throw ValidationException::withMessages([
                    'name_revision' => 'Nama sertifikat telah diperbarui. Periksa nama yang tampil sebelum menyimpan koreksi.',
                ]);
            }
            if ($certificate->currentParticipantName() === $name) {
                return false;
            }
            $certificate->nameRevisions()->create(['participant_name' => $name, 'created_at' => now()]);

            return true;
        }, 5);
    }

    public static function normalize(string $name): ?string
    {
        $name = preg_replace('/\p{Zs}+/u', ' ', $name);
        if ($name === null) {
            return null;
        }
        $name = trim($name, ' ');

        return mb_strlen($name) >= 2 && mb_strlen($name) <= 100
            && preg_match("/^(?=.*\\p{L})[\\p{L}\\p{M} .'‘’‐‑-]+$/uD", $name) === 1 ? $name : null;
    }

    private function verifyPassword(User $user, string $password): void
    {
        if (! Hash::check($password, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => 'Password yang Anda masukkan salah. Silakan coba kembali.',
            ]);
        }
    }

    private function update(User $user, callable $apply): void
    {
        abort_unless($this->installed(), 503, 'Konfirmasi nama sertifikat belum tersedia.');

        DB::transaction(function () use ($user, $apply) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $issued = Certificate::where('user_id', $user->id)->where('program_key', MidtransPaymentService::PROGRAM)->exists();
            $attempt = app(MidtransPaymentService::class)->latest($user);
            abort_if($issued || ($attempt && ! $attempt->canBeReplaced()), 409,
                'Nama sertifikat terkunci selama pembayaran berlangsung atau setelah diterbitkan.');

            $identity = CertificateIdentity::firstOrNew(['user_id' => $user->id]);
            $apply($user, $identity);
            $identity->save();
        }, 5);
    }
}
