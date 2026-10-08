<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->to($this->intendedUrl($request));
    }

    private function intendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');
        $fallback = route('materi.index');

        if (! is_string($intended) || preg_match('/[\\\\\x00-\x20\x7f]/', $intended)) {
            return $fallback;
        }

        // Only relative paths or absolute URLs on this application's origin are accepted.
        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return $intended;
        }

        $target = parse_url($intended);
        $origin = parse_url($request->getSchemeAndHttpHost());

        if ($target === false || isset($target['user']) || isset($target['pass'])) {
            return $fallback;
        }

        foreach (['scheme', 'host', 'port'] as $part) {
            if (($target[$part] ?? null) !== ($origin[$part] ?? null)) {
                return $fallback;
            }
        }

        return $intended;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
