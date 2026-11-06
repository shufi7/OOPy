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

    public function redirect(): RedirectResponse
    {
        if (! $this->configured()) {
            return $this->unavailable();
        }

        return Socialite::driver('google')->enablePKCE()->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, GoogleLoginService $login): RedirectResponse
    {
        if (! $this->configured()) {
            return $this->unavailable();
        }
        if ($request->has('error') || ! is_string($request->query('code')) || $request->query('code') === ''
            || ! is_string($request->query('state'))) {
            $request->session()->forget(['state', 'code_verifier']);

            return redirect()->route('login')->withErrors(['google' => 'Login Google dibatalkan atau tidak selesai. Silakan coba lagi.']);
        }

        try {
            $profile = Socialite::driver('google')->enablePKCE()->user();
        } catch (InvalidStateException $exception) {
            return redirect()->route('login')->withErrors(['google' => 'Sesi Login Google sudah tidak valid. Silakan mulai kembali.']);
        } catch (GuzzleException $exception) {
            return redirect()->route('login')->withErrors(['google' => 'Google belum dapat dihubungi. Silakan coba lagi atau masuk dengan password.']);
        } finally {
            $request->session()->forget(['state', 'code_verifier']);
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

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function unavailable(): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => 'Login Google belum tersedia. Silakan masuk menggunakan email dan password.']);
    }
}
