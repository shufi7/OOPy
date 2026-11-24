<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleLoginService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleLoginController extends Controller
{
    use RedirectsAfterLogin;

    public function redirect(Request $request): RedirectResponse
    {
        if (! self::configured()) {
            return $this->unavailable();
        }

        return Socialite::driver('google')->setRequest($request)->enablePKCE()->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, GoogleLoginService $login, GoogleCertificatePasswordController $password): RedirectResponse
    {
        $flow = $request->session()->pull(GoogleCertificatePasswordController::FLOW);
        if ($flow === null && $request->user()) {
            return redirect()->route('materi.index');
        }
        $error = fn (string $message) => $flow === null
            ? redirect()->route('login')->withErrors(['google' => $message])
            : $password->error($message);

        if (! self::configured()) {
            return $error('Login Google belum tersedia. Silakan masuk menggunakan email dan password.');
        }
        if ($request->has('error') || ! is_string($request->query('code')) || $request->query('code') === ''
            || ! is_string($request->query('state'))) {
            $request->session()->forget(['state', 'code_verifier']);

            return $error('Login Google dibatalkan atau tidak selesai. Silakan coba lagi.');
        }

        try {
            $profile = Socialite::driver('google')->setRequest($request)->enablePKCE()->user();
        } catch (InvalidStateException $exception) {
            return $error('Sesi Login Google sudah tidak valid. Silakan mulai kembali.');
        } catch (GuzzleException $exception) {
            return $error('Google belum dapat dihubungi. Silakan coba lagi atau masuk dengan password.');
        } finally {
            $request->session()->forget(['state', 'code_verifier']);
        }

        if ($flow !== null) {
            return $password->authorizePassword($request, $flow, $profile);
        }

        try {
            $user = $login->resolve($profile);
        } catch (ValidationException $exception) {
            return redirect()->route('login')->withErrors($exception->errors());
        } catch (UniqueConstraintViolationException $exception) {
            return redirect()->route('login')->withErrors(['google' => 'Akun sedang diperbarui. Silakan mulai Login Google kembali.']);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to($this->intendedUrl($request));
    }

    public static function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function unavailable(): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => 'Login Google belum tersedia. Silakan masuk menggunakan email dan password.']);
    }
}
