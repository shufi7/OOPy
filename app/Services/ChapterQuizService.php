<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChapterQuizService
{
    public function quiz(string $slug): Quiz
    {
        $registry = require resource_path('materi/chapters.php');
        abort_unless(isset($registry[$slug]) && $slug !== 'evaluasi-akhir', 404);

        $quiz = Quiz::with(['chapter.materials', 'questions'])
            ->where('type', 'chapter_quiz')->whereHas('chapter', fn ($query) => $query->where('slug', $slug))->first();
        abort_unless($quiz, 503, 'Kuis belum tersedia. Silakan coba lagi nanti.');
        $types = $quiz->questions->countBy('question_type');
        $passingScore = round(config('quiz.minimum_correct') / config('quiz.total_questions') * 100);
        abort_unless(
            $quiz->questions->count() === config('quiz.total_questions')
            && $types->get('multiple_choice', 0) === config('quiz.multiple_choice')
            && $types->get('code_fill', 0) === config('quiz.code_fill')
            && (float) $quiz->passing_score === (float) $passingScore,
            503, 'Konfigurasi kuis belum sesuai. Hubungi pengelola OOPy.'
        );

        return $quiz;
    }

    public function material(Quiz $quiz): Material
    {
        $material = $quiz->chapter->materials->firstWhere('slug', $quiz->chapter->slug);
        abort_unless($material, 503, 'Materi kuis belum tersedia.');

        return $material;
    }

    public function publicQuestions(Quiz $quiz, ?User $user = null): array
    {
        $attempt = $user?->quizAttempts()->where('quiz_id', $quiz->id)->where('status', 'in_progress')->first();
        $questions = $attempt ? $this->attemptQuestions($quiz, $attempt) : $quiz->questions;

        return $questions->map(fn ($question) => [
            'question_id' => $question->id,
            'question_order' => $question->display_order ?? $question->question_order,
            'type' => $question->question_type,
            'question' => $question->question_text,
            'options' => $question->options,
            'code' => $question->code_snippet,
        ])->all();
    }

    public function progress(Quiz $quiz, User $user): array
    {
        $progress = $user->progress()->where('material_id', $this->material($quiz)->id)->first();
        $passed = $progress?->status === 'completed';

        return [
            'status' => $progress?->status ?? 'not_started',
            'completed_at' => $progress?->completed_at?->toIso8601String(),
            'passed' => $passed,
            'next_unlocked' => $passed,
            'best_score' => (float) ($user->quizAttempts()->where('quiz_id', $quiz->id)
                ->where('status', 'completed')->max('score') ?? 0),
        ];
    }

    public function nextChapter(Quiz $quiz): ?array
    {
        $registry = require resource_path('materi/chapters.php');
        $next = Chapter::where('chapter_order', '>', $quiz->chapter->chapter_order)->orderBy('chapter_order')->first();

        return $next && isset($registry[$next->slug]) ? $registry[$next->slug] + ['slug' => $next->slug] : null;
    }

    public function start(Quiz $quiz, User $user): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $user) {
            // All starts/submits for one account share this lock, including an absent progress row.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $attempt = $user->quizAttempts()->where('quiz_id', $quiz->id)->where('status', 'in_progress')
                ->lockForUpdate()->first();
            if ($attempt) {
                $this->pinQuestions($quiz, $attempt);

                return $attempt;
            }

            $attempt = $user->quizAttempts()->create(['quiz_id' => $quiz->id, 'started_at' => now()]);
            $this->pinQuestions($quiz, $attempt);

            return $attempt;
        }, 3);
    }

    public function attemptQuestions(Quiz $quiz, QuizAttempt $attempt)
    {
        $questions = Question::query()->select('questions.*', 'quiz_attempt_questions.position as pinned_position')
            ->join('quiz_attempt_questions', 'questions.id', '=', 'quiz_attempt_questions.question_id')
            ->where('quiz_attempt_questions.attempt_id', $attempt->id)->where('questions.quiz_id', $quiz->id)
            ->orderBy('quiz_attempt_questions.position')->get();

        // Legacy/imported attempts without a set can still use the current published questions.
        return $questions->isEmpty() ? $quiz->questions : $questions->map(function ($question) {
            $question->display_order = $question->pinned_position;

            return $question;
        });
    }

    private function pinQuestions(Quiz $quiz, QuizAttempt $attempt): void
    {
        if (DB::table('quiz_attempt_questions')->where('attempt_id', $attempt->id)->exists()) {
            return;
        }
        foreach ($quiz->questions()->get() as $index => $question) {
            DB::table('quiz_attempt_questions')->insert([
                'attempt_id' => $attempt->id, 'question_id' => $question->id, 'position' => $index + 1,
            ]);
        }
    }
}
