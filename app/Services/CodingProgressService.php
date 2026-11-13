<?php

namespace App\Services;

use App\Models\ExerciseSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CodingProgressService
{
    public const EXERCISES = [
        1 => ['key' => 'coding-latihan-1', 'slug' => 'latihan-1', 'title' => 'Latihan 1 — Class dan Object'],
        2 => ['key' => 'coding-latihan-2-multifile-v1', 'slug' => 'latihan-2', 'title' => 'Latihan 2: OOP Multi-File'],
    ];

    private const VALID_SCORE = "CASE WHEN exercise_submissions.status IN ('passed', 'failed', 'error')
        AND exercise_submissions.score BETWEEN 0 AND 100 THEN exercise_submissions.score END";

    private function submissions(bool $includeLegacy = false): Builder
    {
        return ExerciseSubmission::query()->join('exercises', 'exercises.id', '=', 'exercise_submissions.exercise_id')
            ->whereIn('exercises.exercise_key', [...array_column(self::EXERCISES, 'key'), ...($includeLegacy ? ['coding-latihan-2'] : [])]);
    }

    public function summaries(): Builder
    {
        $query = $this->submissions()->select('exercise_submissions.user_id')
            ->selectRaw('COUNT(*) AS coding_attempts, MAX(exercise_submissions.submitted_at) AS coding_last_at')
            ->selectRaw('MAX('.self::VALID_SCORE.') AS coding_best_score')
            ->groupBy('exercise_submissions.user_id');
        foreach (self::EXERCISES as $number => $exercise) {
            $query->selectRaw("COUNT(CASE WHEN exercises.exercise_key = ? THEN 1 END) AS coding_{$number}_attempts", [$exercise['key']])
                ->selectRaw("MAX(CASE WHEN exercises.exercise_key = ? AND exercise_submissions.status = 'passed' THEN 1 ELSE 0 END) AS coding_{$number}_passed", [$exercise['key']])
                ->selectRaw('MAX(CASE WHEN exercises.exercise_key = ? THEN '.self::VALID_SCORE." END) AS coding_{$number}_best_score", [$exercise['key']])
                ->selectRaw("MAX(CASE WHEN exercises.exercise_key = ? THEN exercise_submissions.submitted_at END) AS coding_{$number}_last_at", [$exercise['key']]);
        }

        return $query;
    }

    public function summary(?object $record, string $notStartedLabel = 'Belum Dimulai'): array
    {
        $cards = [];
        foreach (self::EXERCISES as $number => $exercise) {
            $attempts = (int) ($record?->{'coding_'.$number.'_attempts'} ?? 0);
            $passed = (int) ($record?->{'coding_'.$number.'_passed'} ?? 0) === 1;
            $status = $passed ? 'completed' : ($attempts > 0 ? 'in_progress' : 'not_started');
            $value = $record?->{'coding_'.$number.'_best_score'};
            $score = $value !== null ? (float) $value : null;
            $cards[] = [
                'key' => $exercise['key'],
                'title' => $exercise['title'],
                'status' => $status,
                'status_label' => match ($status) {
                    'completed' => 'Selesai', 'in_progress' => 'Belum Lulus', default => $notStartedLabel,
                },
                'best_score' => $score,
                'best_score_label' => self::score($score),
                'attempts_count' => $attempts,
                'last_at' => $record?->{'coding_'.$number.'_last_at'},
                'url' => route('coding.show', $exercise['slug']),
                'action' => match ($status) {
                    'completed' => 'Kerjakan Kembali', 'in_progress' => 'Lanjutkan Latihan', default => 'Mulai Latihan',
                },
            ];
        }
        $done = count(array_filter($cards, fn ($card) => $card['status'] === 'completed'));

        return [
            'exercises' => $cards,
            'completed_exercises' => $done,
            'total_exercises' => count(self::EXERCISES),
            'progress_percent' => (int) ($done / count(self::EXERCISES) * 100),
        ];
    }

    public function history(?User $user = null): Builder
    {
        return $this->submissions(true)->when($user, fn ($query) => $query->where('exercise_submissions.user_id', $user->id))
            ->select(['exercise_submissions.id', 'exercise_submissions.user_id', 'exercise_submissions.status',
                'exercise_submissions.submitted_at', 'exercises.exercise_key'])
            ->selectRaw(self::VALID_SCORE.' AS score')
            ->orderByDesc('exercise_submissions.submitted_at')->orderByDesc('exercise_submissions.id');
    }

    public function activity(ExerciseSubmission $submission): array
    {
        $exercise = $submission->exercise_key === 'coding-latihan-2'
            ? ['title' => 'Latihan 2: Pewarisan (versi lama)', 'slug' => 'latihan-2']
            : collect(self::EXERCISES)->firstWhere('key', $submission->exercise_key);
        $time = $submission->submitted_at->copy()->timezone('Asia/Makassar')->locale('id');

        return [
            'id' => $submission->id,
            'title' => $exercise['title'],
            'url' => route('coding.show', $exercise['slug']),
            'score' => self::score($submission->score),
            'status' => $submission->status,
            'status_label' => match ($submission->status) {
                'passed' => 'Lulus', 'error' => 'Error', default => 'Belum Lulus',
            },
            'datetime' => $time->toIso8601String(),
            'date_label' => $time->translatedFormat('j M Y, H:i').' WITA',
        ];
    }

    public function forUser(User $user): array
    {
        $summary = $this->summary($this->summaries()->where('exercise_submissions.user_id', $user->id)->first());
        $summary['recent_activity'] = $this->history($user)->limit(5)->get()->map(fn ($submission) => $this->activity($submission))->all();

        return $summary;
    }

    public static function score(mixed $score): string
    {
        return $score === null ? '—' : number_format((float) $score, (float) $score === round((float) $score) ? 0 : 1, ',', '.');
    }
}
