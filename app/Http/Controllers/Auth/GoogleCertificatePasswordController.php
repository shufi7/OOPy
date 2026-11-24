<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleCertificatePasswordController extends Controller
{
    public const FLOW = 'certification.google_password_flow';

    private const GRANT = 'certification.google_password_grant';

    public function start(Request $request): RedirectResponse
    {
        abort_unless($request->user()->google_id, 403);
        abort_unless(GoogleLoginController::configured(), 503, 'Verifikasi Google belum tersedia.');

        $request->session()->forget(self::GRANT);
        $request->session()->put(self::FLOW, $this->identity($request->user(), 10));

        return Socialite::driver('google')->setRequest($request)->enablePKCE()->with(['prompt' => 'select_account'])->redirect();
    }

    public function authorizePassword(Request $request, array $flow, GoogleUser $profile): RedirectResponse
    {
        if (! $this->matches($request->user(), $flow) || $profile->getId() !== $flow['google_id']
            || ($profile->user['email_verified'] ?? false) !== true) {
            return $this->error('Pilih akun Google terverifikasi yang tertaut ke akun OOPy ini.');
        }

        $request->session()->regenerate(true);
        $request->session()->put(self::GRANT, $this->identity($request->user(), 5));

        return redirect()->to(route('certification.index').'#password-oopy');
    }

    public function ready(Request $request): bool
    {
        return $this->matches($request->user(), $request->session()->get(self::GRANT, []));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->ready($request)) {
            return $this->error('Verifikasi Google telah berakhir. Verifikasi akun Google Anda kembali.');
        }

        $data = $request->validateWithBag('certificatePassword', [
            'password' => ['bail', 'required', 'string', 'confirmed', Password::min(8), function ($attribute, $value, $fail) {
                if (strlen($value) > 72 || str_contains($value, "\0")) {
                    $fail('Password harus dapat diproses dan panjangnya paling banyak 72 byte.');
                }
            }],
        ]);
        $grant = $request->session()->pull(self::GRANT);
        $user = DB::transaction(function () use ($request, $grant, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! $this->matches($user, $grant)) {
                return null;
            }
            $user->password = $data['password'];
            $user->setRememberToken(Str::random(60));
            $user->save();

            return $user;
        }, 5);
        if (! $user) {
            return $this->error('Verifikasi Google telah berakhir. Verifikasi akun Google Anda kembali.');
        }

        Auth::guard('web')->setUser($user);
        $request->session()->regenerate(true);

        return redirect()->to(route('certification.index').'#nama-sertifikat')
            ->with('certificate_status', 'Password OOPy berhasil disimpan. Gunakan password ini untuk mengganti nama sertifikat.');
    }

    public function error(string $message): RedirectResponse
    {
        return redirect()->to(route('certification.index').'#nama-sertifikat')->withErrors(['google_password' => $message]);
    }

    private function identity(User $user, int $minutes): array
    {
        return ['user_id' => $user->id, 'google_id' => $user->google_id, 'expires_at' => now()->addMinutes($minutes)->timestamp];
    }

    private function matches(?User $user, array $identity): bool
    {
        return $user?->google_id && ($identity['user_id'] ?? null) === $user->id
            && ($identity['google_id'] ?? null) === $user->google_id && now()->timestamp < $identity['expires_at'];
    }
}
