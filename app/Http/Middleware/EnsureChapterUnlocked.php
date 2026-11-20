<?php

namespace App\Http\Middleware;

use App\Models\QuizAttempt;
use App\Services\ChapterAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChapterUnlocked
{
    public function __construct(private ChapterAccessService $access) {}

    public function handle(Request $request, Closure $next, ?string $chapterSlug = null): Response
    {
        $slug = $chapterSlug ?? $request->route('slug') ?? 'evaluasi-akhir';
        $chapters = $this->access->forUser($request->user());
        abort_unless(isset($chapters[$slug]), 404);
        abort_if($slug === 'evaluasi-akhir' && $request->routeIs('quiz.*'), 404);
        $attempt = $request->route('attempt');
        if ($attempt instanceof QuizAttempt) {
            abort_unless($attempt->quiz->chapter->slug === $slug, 404);
        }

        if ($chapters[$slug]['locked']) {
            $required = $chapters[$slug]['required_chapter'];
            $message = $this->access->lockedMessage($required);
            $response = $request->expectsJson()
                ? response()->json([
                    'message' => $message,
                    'required_chapter' => $required['slug'],
                    'continue_url' => route('materi.show', $required['slug']),
                ], 403)
                : redirect()->route('materi.index')->with('chapter_locked', $message);
        } else {
            $response = $next($request);
        }

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
