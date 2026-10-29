<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Services\ChapterQuizService;
use App\Services\Admin\MaterialContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MateriController extends Controller
{
    public function index(MaterialContentService $contents): View
    {
        return view('materi.index', [
            'materiList' => $contents->publishedRegistry(require resource_path('materi/chapters.php')),
        ]);
    }

    public function show(Request $request, string $slug, ChapterQuizService $quizzes, MaterialContentService $contents)
    {
        $chapters = require resource_path('materi/chapters.php');

        if (! isset($chapters[$slug])) {
            abort(404);
        }

        $chapter = $chapters[$slug];
        $chapter['slug'] = $slug;

        if (empty($chapter['content'])) {
            abort(404);
        }

        $content = require resource_path(
            'materi/'.$chapter['content']
        );
        [$content, $publishedTitle] = $contents->published($slug, $content);
        if ($publishedTitle !== null) {
            $chapter['judul'] = $publishedTitle;
        }

        $slugs = array_keys($chapters);

        $currentIndex = array_search($slug, $slugs);

        $previousChapter = null;
        $nextChapter = null;

        if ($currentIndex !== false) {

            // BAB sebelumnya
            if ($currentIndex > 0) {
                $previousSlug = $slugs[$currentIndex - 1];

                if (! empty($chapters[$previousSlug]['content'])) {
                    $previousChapter = $chapters[$previousSlug];

                    $previousChapter['slug'] = $previousSlug;
                }
            }

            // BAB berikutnya
            if ($currentIndex < count($slugs) - 1) {
                $nextSlug = $slugs[$currentIndex + 1];

                if (! empty($chapters[$nextSlug]['content'])) {
                    $nextChapter = $chapters[$nextSlug];

                    $nextChapter['slug'] = $nextSlug;
                }
            }
        }

        // Never serialize answer keys. Original content remains available if the database is unavailable.
        $quizQuestions = collect($content['quiz'] ?? [])->map(fn ($question, $index) => [
            'question_id' => null,
            'question_order' => $index + 1,
            'type' => $question['type'] ?? 'multiple_choice',
            'question' => $question['question'],
            'options' => $question['options'] ?? null,
            'code' => $question['code'] ?? null,
        ])->all();
        $quizProgress = ['passed' => false, 'next_unlocked' => false];
        if ($request->user() && $quizQuestions) {
            $quiz = $quizzes->quiz($slug);
            $quizQuestions = $quizzes->publicQuestions($quiz, $request->user());
            $quizProgress = $quizzes->progress($quiz, $request->user());
            $nextChapter = $quizzes->nextChapter($quiz);
        } elseif ($quizQuestions) {
            try {
                $quiz = Quiz::with('questions')->where('type', 'chapter_quiz')
                    ->whereHas('chapter', fn ($query) => $query->where('slug', $slug))->first();
                if ($quiz && $quiz->questions->isNotEmpty()) {
                    $quizQuestions = $quizzes->publicQuestions($quiz);
                }
            } catch (QueryException) {
                // Guests may still read the file-backed material before database setup.
            }
        }
        $quizConfig = [
            'authenticated' => $request->user() !== null,
            'total_questions' => config('quiz.total_questions'),
            'minimum_correct' => config('quiz.minimum_correct'),
            'start_url' => route('quiz.start', $slug),
            'progress_url' => route('quiz.progress', $slug),
            'progress' => $quizProgress,
        ];

        return response()->view('materi.show', compact(
            'chapter',
            'content',
            'previousChapter',
            'nextChapter',
            'quizQuestions',
            'quizConfig',
            'quizProgress'
        ))->header('Cache-Control', 'no-store, private');
    }
}
