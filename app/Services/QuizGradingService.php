<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuizGradingService
{
    public function __construct(private ChapterQuizService $quizzes) {}

    public function submit(Quiz $quiz, QuizAttempt $attempt, User $user, array $answers): array
    {
        return DB::transaction(function () use ($quiz, $attempt, $user, $answers) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('submit', $attempt);
            abort_unless($attempt->quiz_id === $quiz->id, 404);
            $questions = $quiz->questions()->lockForUpdate()->get()->keyBy('id');
            $graded = [];
            foreach ($answers as $index => $entry) {
                $question = $questions->get($entry['question_id']);
                if (! $question) {
                    throw ValidationException::withMessages(["answers.$index.question_id" => 'Soal tidak termasuk kuis ini.']);
                }
                $answer = $entry['answer'];
                if ($question->question_type === 'multiple_choice') {
                    if (! is_int($answer) || ! array_key_exists($answer, $question->options ?? [])) {
                        throw ValidationException::withMessages(["answers.$index.answer" => 'Pilih indeks jawaban yang valid.']);
                    }
                    $text = (string) $answer;
                } else {
                    $text = is_string($answer) ? $this->trimCodeAnswer($answer) : null;
                    if ($text === null || $text === '' || mb_strlen($answer) > config('quiz.maximum_answer_length')) {
                        throw ValidationException::withMessages(["answers.$index.answer" => 'Isian kode wajib berupa teks yang tidak kosong, maksimal '.config('quiz.maximum_answer_length').' karakter.']);
                    }
                    // Chapter quizzes use exact, case-sensitive comparison; internal spacing is preserved.
                }
                $graded[$question->id] = ['answer_text' => $text, 'is_correct' => $text === $question->correct_answer];
            }
            if (count($graded) !== config('quiz.total_questions') || $questions->count() !== count($graded)) {
                throw ValidationException::withMessages(['answers' => 'Jawab seluruh soal tepat satu kali.']);
            }

            // A retry or another tab cannot regrade a completed submission.
            if ($attempt->status === 'completed') {
                return $this->result($quiz, $attempt, $user);
            }
            abort_unless($attempt->status === 'in_progress', 409, 'Percobaan ini tidak dapat dikumpulkan.');

            foreach ($graded as $questionId => $attributes) {
                $attempt->answers()->create(['question_id' => $questionId] + $attributes);
            }
            $correct = count(array_filter($graded, fn ($answer) => $answer['is_correct']));
            $completedAt = now();
            $attempt->update([
                'status' => 'completed',
                'score' => round($correct / config('quiz.total_questions') * 100),
                'completed_at' => $completedAt,
            ]);

            $progress = $user->progress()->firstOrNew(['material_id' => $this->quizzes->material($quiz)->id]);
            if ($progress->status !== 'completed') {
                $passed = $correct >= config('quiz.minimum_correct');
                $progress->status = $passed ? 'completed' : 'in_progress';
                $progress->completed_at = $passed ? $completedAt : null;
                $progress->save();
            }

            return $this->result($quiz, $attempt, $user);
        }, 3);
    }

    private function trimCodeAnswer(string $answer): string
    {
        // ECMAScript String.trim: Unicode whitespace, without deleting NUL/zero-width characters.
        return preg_replace('/\A[\x09-\x0D\p{Zs}\x{2028}\x{2029}\x{FEFF}]+|[\x09-\x0D\p{Zs}\x{2028}\x{2029}\x{FEFF}]+\z/u', '', $answer) ?? $answer;
    }

    private function result(Quiz $quiz, QuizAttempt $attempt, User $user): array
    {
        $correct = $attempt->answers()->where('is_correct', true)->count();

        return [
            'result' => [
                'attempt_id' => $attempt->id,
                'score' => (float) $attempt->score,
                'correct_count' => $correct,
                'incorrect_count' => config('quiz.total_questions') - $correct,
                'passed' => $correct >= config('quiz.minimum_correct'),
            ],
            'progress' => $this->quizzes->progress($quiz, $user),
        ];
    }
}
