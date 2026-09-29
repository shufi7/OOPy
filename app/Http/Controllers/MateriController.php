<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MateriController extends Controller
{
    public function index(): View
    {
        return view('materi.index', [
            'materiList' => require resource_path('materi/chapters.php'),
        ]);
    }

    public function show(string $slug)
    {
        $chapters = require resource_path('materi/chapters.php');

        if (!isset($chapters[$slug])) {
            abort(404);
        }

        $chapter = $chapters[$slug];

        if (empty($chapter['content'])) {
            abort(404);
        }

        $content = require resource_path(
            'materi/' . $chapter['content']
        );

        $slugs = array_keys($chapters);

        $currentIndex = array_search($slug, $slugs);

        $previousChapter = null;
        $nextChapter = null;

        if ($currentIndex !== false) {

            // BAB sebelumnya
            if ($currentIndex > 0) {
                $previousSlug = $slugs[$currentIndex - 1];

                if (!empty($chapters[$previousSlug]['content'])) {
                    $previousChapter = $chapters[$previousSlug];

                    $previousChapter['slug'] = $previousSlug;
                }
            }

            // BAB berikutnya
            if ($currentIndex < count($slugs) - 1) {
                $nextSlug = $slugs[$currentIndex + 1];

                if (!empty($chapters[$nextSlug]['content'])) {
                    $nextChapter = $chapters[$nextSlug];

                    $nextChapter['slug'] = $nextSlug;
                }
            }
        }

        return view('materi.show', compact(
            'chapter',
            'content',
            'previousChapter',
            'nextChapter'
        ));
    }
}
