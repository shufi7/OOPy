<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

trait RedirectsAfterLogin
{
    private function intendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');
        $fallback = route('materi.index');

        if (! is_string($intended) || preg_match('/[\\\\\x00-\x20\x7f]/', $intended)) {
            return $fallback;
        }

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
}
