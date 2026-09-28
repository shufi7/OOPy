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

    public function show(string $slug): View
    {
        $chapters = require resource_path('materi/chapters.php');
        $chapter = $chapters[$slug] ?? null;

        abort_unless(isset($chapter['content']), 404);

        // Resolve only filenames in our chapter registry, never a user-supplied path.
        $content = require resource_path('materi/'.$chapter['content']);

        return view('materi.show', compact('chapter', 'content'));
    }
}
