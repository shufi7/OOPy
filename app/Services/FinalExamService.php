<?php

namespace App\Services;

use App\Models\FinalExamAttempt;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinalExamService
{
    public const ESSAY_MAX_POINTS = 5;

    public function state(Request $request): array
    {
        if ($request->user()) {
            $records = FinalExamAttempt::where('user_id', $request->user()->id)->latest('started_at')->limit(100)->get();
        } else {
            $records = collect($request->session()->get('final_exam_practice', []))->map(fn ($row) => $this->fromSession($row));
        }

        return [
            'official' => (bool) $request->user(),
            'serverNow' => now()->getTimestampMs(),
            'active' => ($record = $records->firstWhere('status', 'in_progress')) ? $this->publicRecord($record, (bool) $request->user()) : null,
            'history' => $records->filter(fn ($record) => $record->submitted_at !== null)
                ->map(fn ($record) => $this->publicRecord($record, (bool) $request->user()))->values()->all(),
        ];
    }

    public function start(Request $request): array
    {
        if ($request->user()) {
            return DB::transaction(function () use ($request) {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $active = FinalExamAttempt::where('user_id', $request->user()->id)->where('status', 'in_progress')->lockForUpdate()->first();
                if ($active && $active->deadline_at->isPast()) {
                    $this->finish($active);
                    $active->save();
                    $active = null;
                }
                if (! $active) {
                    [$questions, $quiz] = $this->catalog(true);
                    $active = new FinalExamAttempt($this->newAttributes($questions, $quiz));
                    $active->user_id = $request->user()->id;
                    $active->save();
                }

                return $this->publicRecord($active, true);
            }, 3);
        }

        $state = $this->state($request);
        if ($state['active'] && $state['active']['deadline'] > now()->getTimestampMs()) {
            return $state['active'];
        }
        if ($state['active']) {
            $expired = $this->fromSession($request->session()->get('final_exam_practice.'.$state['active']['id']));
            $this->finish($expired);
            $this->storePractice($request, $expired);
        }
        [$questions, $quiz] = $this->catalog(false);
        $attempt = new FinalExamAttempt($this->newAttributes($questions, $quiz));
        $attempt->id = (string) Str::uuid();
        $this->storePractice($request, $attempt);

        return $this->publicRecord($attempt, false);
    }

    public function saveAnswers(Request $request, string $id, array $answers, bool $submit): array
    {
        if ($request->user()) {
            return DB::transaction(function () use ($request, $id, $answers, $submit) {
                $attempt = FinalExamAttempt::whereKey($id)->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
                $this->applyAnswers($attempt, $answers, $submit);
                $attempt->save();

                return $this->publicRecord($attempt, true);
            }, 3);
        }

        $row = $request->session()->get('final_exam_practice.'.$id);
        abort_unless(is_array($row), 404);
        $attempt = $this->fromSession($row);
        $this->applyAnswers($attempt, $answers, $submit);
        $this->storePractice($request, $attempt);

        return $this->publicRecord($attempt, false);
    }

    private function applyAnswers(FinalExamAttempt $attempt, array $answers, bool $submit): void
    {
        // A retry cannot overwrite submitted answers or trigger a second grade.
        if ($attempt->status !== 'in_progress') {
            return;
        }
        if (now()->lt($attempt->deadline_at)) {
            $validated = $this->validateAnswers($attempt->snapshot, $answers);
            $attempt->answers = array_replace($attempt->answers, $validated);
        } elseif (! $submit) {
            abort(409, 'Waktu evaluasi habis. Kumpulkan jawaban yang sudah tersimpan.');
        }
        // Late submissions use only answers accepted by the server before the deadline.
        if ($submit) {
            $this->finish($attempt);
        }
    }

    private function finish(FinalExamAttempt $attempt): void
    {
        $questions = collect($attempt->snapshot);
        $objective = $questions->where('type', '!=', 'essay');
        $correct = $objective->filter(fn ($question) => $this->isCorrect($question, $attempt->answers[$question['id']] ?? null))->count();
        $attempt->objective_score = $correct / $objective->count() * 100;
        $attempt->objective_points = $correct * 5;
        $attempt->submitted_at = now()->min($attempt->deadline_at);
        $attempt->timed_out = now()->gte($attempt->deadline_at);
        $attempt->status = 'pending_review';
    }

    public function grade(FinalExamAttempt $attempt, User $admin, array $scores, array $feedback): void
    {
        DB::transaction(function () use ($attempt, $admin, $scores, $feedback) {
            $locked = FinalExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->submitted_at === null, 409, 'Evaluasi belum dikumpulkan.');
            $ids = collect($locked->snapshot)->where('type', 'essay')->pluck('id')->all();
            if (count($scores) !== count($ids) || array_diff(array_keys($scores), $ids) || array_diff($ids, array_keys($scores))) {
                throw ValidationException::withMessages(['scores' => 'Berikan skor untuk seluruh lima uraian.']);
            }
            foreach ($scores as $id => $score) {
                if (! is_numeric($score) || $score < 0 || $score > self::ESSAY_MAX_POINTS) {
                    throw ValidationException::withMessages(['scores.'.$id => 'Skor harus antara 0 dan 5 poin.']);
                }
            }
            $points = array_sum($scores);
            $locked->update([
                'essay_scores' => $scores, 'essay_feedback' => array_intersect_key($feedback, $scores),
                'essay_points' => $points, 'essay_score' => $points / 25 * 100,
                'total_score' => (float) $locked->objective_points + $points,
                'status' => 'completed', 'graded_at' => now(), 'graded_by' => $admin->id,
            ]);
        }, 3);
    }

    public function publicRecord(FinalExamAttempt $attempt, bool $official): array
    {
        $questions = collect($attempt->snapshot)->map(function ($question) {
            return array_intersect_key($question, array_flip(['id', 'type', 'question', 'options', 'code']));
        })->values()->all();
        $record = [
            'id' => $attempt->id, 'official' => $official, 'status' => $attempt->status === 'in_progress' ? 'active' : 'completed',
            'startedAt' => $attempt->started_at->getTimestampMs(), 'deadline' => $attempt->deadline_at->getTimestampMs(),
            'policy' => ['durationSeconds' => $attempt->duration_seconds, 'passThreshold' => (float) $attempt->passing_score],
            'answers' => $attempt->answers, 'questions' => $questions, 'current' => 0, 'review' => [],
            'contentVersion' => 1,
        ];
        if ($attempt->submitted_at !== null) {
            $objective = collect($attempt->snapshot)->where('type', '!=', 'essay');
            $correct = $objective->filter(fn ($q) => $this->isCorrect($q, $attempt->answers[$q['id']] ?? null))->count();
            $record += [
                'finishedAt' => $attempt->submitted_at->getTimestampMs(), 'timedOut' => (bool) $attempt->timed_out,
                'result' => [
                    'score' => (float) $attempt->objective_score, 'correct' => $correct,
                    'incorrect' => $objective->count() - $correct, 'objectiveTotal' => $objective->count(),
                    'metThreshold' => $correct / $objective->count() * 100 >= (float) $attempt->passing_score,
                    'essaysFilled' => collect($attempt->snapshot)->where('type', 'essay')
                        ->filter(fn ($q) => trim($attempt->answers[$q['id']] ?? '') !== '')->count(),
                    'essayTotal' => 5, 'durationMs' => $attempt->submitted_at->diffInMilliseconds($attempt->started_at, true),
                    'objectivePoints' => (float) $attempt->objective_points,
                    'essayPoints' => $attempt->essay_points === null ? null : (float) $attempt->essay_points,
                    'totalScore' => $attempt->total_score === null ? null : (float) $attempt->total_score,
                    'passed' => $attempt->total_score === null ? null : (float) $attempt->total_score >= (float) $attempt->passing_score,
                ],
                // Feedback reports correctness, never the reusable bank answer key.
                'feedback' => collect($attempt->snapshot)->mapWithKeys(fn ($q) => [$q['id'] => [
                    'correct' => $q['type'] === 'essay' ? null : $this->isCorrect($q, $attempt->answers[$q['id']] ?? null),
                    'essayScore' => $attempt->essay_scores[$q['id']] ?? null,
                    'feedback' => $q['type'] === 'essay' ? ($attempt->essay_feedback[$q['id']] ?? '') : '',
                ]])->all(),
            ];
        }

        return $record;
    }

    private function catalog(bool $official): array
    {
        $quiz = Schema::hasTable('quizzes') ? Quiz::where('type', 'final_exam')->with('questions')->first() : null;
        if ($official) {
            abort_unless($quiz, 503, 'Evaluasi belum tersedia. Jalankan seeder konten terlebih dahulu.');
        }
        if ($quiz) {
            $questions = $quiz->questions->map(fn ($q) => [
                'id' => 'q'.$q->id, 'type' => $q->question_type, 'question' => $q->question_text,
                'options' => $q->options, 'code' => $q->code_snippet,
                'correct' => $q->question_type === 'multiple_choice' ? (int) $q->correct_answer : null,
                'answer' => $q->question_type === 'code_fill' ? $q->correct_answer : null,
                'rubric' => $q->essay_rubric,
            ])->all();
        } else {
            $questions = (require resource_path('materi/evaluasi-akhir.php'))['questions'];
        }
        $counts = array_count_values(array_column($questions, 'type'));
        abort_unless(count($questions) === 20 && ($counts['multiple_choice'] ?? 0) === 10
            && ($counts['code_fill'] ?? 0) === 5 && ($counts['essay'] ?? 0) === 5, 503, 'Susunan evaluasi belum valid.');

        return [$questions, $quiz];
    }

    private function newAttributes(array $questions, ?Quiz $quiz): array
    {
        $duration = $quiz?->duration_seconds ?? config('evaluasi.duration_seconds');

        return [
            'quiz_id' => $quiz?->id, 'snapshot' => $questions,
            'answers' => array_fill_keys(array_column($questions, 'id'), null),
            'status' => 'in_progress', 'passing_score' => $quiz?->passing_score ?? config('evaluasi.pass_threshold'),
            'duration_seconds' => $duration, 'started_at' => now(), 'deadline_at' => now()->addSeconds($duration),
        ];
    }

    private function validateAnswers(array $questions, array $answers): array
    {
        $bank = collect($questions)->keyBy('id');
        foreach ($answers as $id => $answer) {
            $q = $bank->get($id);
            if (! $q || ($answer !== null && ($q['type'] === 'multiple_choice'
                ? ! is_int($answer) || ! array_key_exists($answer, $q['options'])
                : ! is_string($answer) || mb_strlen($answer) > ($q['type'] === 'essay' ? 10000 : 500)))) {
                throw ValidationException::withMessages(['answers.'.$id => 'Jawaban tidak valid untuk soal ini.']);
            }
        }

        return $answers;
    }

    private function isCorrect(array $q, mixed $answer): bool
    {
        if ($q['type'] === 'multiple_choice') {
            return is_int($answer) && $answer === $q['correct'];
        }
        if ($q['type'] !== 'code_fill' || ! is_string($answer)) {
            return false;
        }
        // Admin-authored expressions outside the tolerant token grammar must still
        // accept their exact answer; Python is never executed by this service.
        if (trim($answer) !== '' && trim($answer) === trim($q['answer'])) {
            return true;
        }
        $expected = $this->codeTokens($q['answer']);

        return $expected !== null && $this->codeTokens($answer) === $expected;
    }

    private function codeTokens(string $source): ?array
    {
        $rest = trim($source);
        if (str_contains($rest, "\n") || str_contains($rest, "\r")) {
            return null;
        }
        $tokens = [];
        while ($rest !== '') {
            if (! preg_match('/^(?:[A-Za-z_]\w*|[.@=(),])/', $rest, $matches)) {
                return null;
            }
            $tokens[] = $matches[0];
            $rest = ltrim(substr($rest, strlen($matches[0])));
        }

        return $tokens ?: null;
    }

    private function storePractice(Request $request, FinalExamAttempt $attempt): void
    {
        $records = $request->session()->get('final_exam_practice', []);
        $records[$attempt->id] = $attempt->getAttributes();
        // Bound session size; official learning records are never pruned this way.
        $request->session()->put('final_exam_practice', array_slice($records, -10, null, true));
    }

    private function fromSession(array $row): FinalExamAttempt
    {
        $attempt = new FinalExamAttempt;
        $attempt->setRawAttributes($row);

        return $attempt;
    }
}
