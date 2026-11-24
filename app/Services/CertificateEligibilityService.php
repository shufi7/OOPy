<?php

namespace App\Services;

use App\Models\ExerciseSubmission;
use App\Models\FinalExamAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;

class CertificateEligibilityService
{
    public function __construct(private DashboardProgressService $progress) {}

    public function forUser(User $user): array
    {
        $dashboard = $this->progress->forUser($user);
        $chapters = collect($dashboard['chapters']);
        $coding = collect($dashboard['coding']['exercises']);
        $quizzesPassed = $chapters->filter(fn ($chapter) => $chapter['best_score'] !== null
            && $chapter['best_score'] >= ChapterQuizScoring::passingScore())->count();
        $finals = FinalExamAttempt::where('user_id', $user->id)->where('status', 'completed')
            ->whereNotNull('submitted_at')->whereColumn('submitted_at', '>=', 'started_at')
            ->whereBetween('total_score', [0, 100])->whereBetween('passing_score', [0, 100])->get();
        // Each official attempt retains the passing threshold used by its server-side grading.
        $passedFinals = $finals->filter(fn ($attempt) => (float) $attempt->total_score >= (float) $attempt->passing_score);
        $transcriptFinal = ($passedFinals->isNotEmpty() ? $passedFinals : $finals)
            ->sortBy([['total_score', 'desc'], ['id', 'desc']])->first();
        $missing = [];
        foreach ($chapters as $chapter) {
            if ($chapter['status'] !== 'completed') {
                $missing[] = ['label' => 'Selesaikan '.$chapter['bab'].': '.$chapter['title'], 'url' => $chapter['url']];
            }
            if ($chapter['best_score'] === null || $chapter['best_score'] < ChapterQuizScoring::passingScore()) {
                $missing[] = ['label' => 'Lulus kuis '.$chapter['bab'].' (minimal '.ChapterQuizScoring::format(ChapterQuizScoring::passingScore()).')', 'url' => $chapter['url'].'#kuis'];
            }
        }
        foreach ($coding as $exercise) {
            if ($exercise['status'] !== 'completed') {
                $missing[] = ['label' => 'Lulus '.$exercise['title'], 'url' => $exercise['url']];
            }
        }
        if ($passedFinals->isEmpty()) {
            $missing[] = ['label' => 'Lulus Evaluasi Akhir resmi (minimal '.config('evaluasi.pass_threshold').'; hasil harus selesai dinilai)', 'url' => route('evaluasi.index')];
        }

        $eligible = $missing === [] && $chapters->count() === 6 && $coding->count() === count(CodingProgressService::EXERCISES);

        return [
            'total_chapters' => $chapters->count(),
            'completed_chapters' => $dashboard['completed_chapters'],
            'passed_quizzes' => $quizzesPassed,
            'total_quizzes' => $chapters->count(),
            'passed_coding' => $dashboard['coding']['completed_exercises'],
            'total_coding' => $dashboard['coding']['total_exercises'],
            'best_final_score' => $finals->isEmpty() ? null : (float) $finals->max('total_score'),
            'transcript_final_score' => $transcriptFinal ? (float) $transcriptFinal->total_score : null,
            'transcript_final_threshold' => $transcriptFinal ? (float) $transcriptFinal->passing_score : null,
            'final_passed' => $passedFinals->isNotEmpty(),
            'final_status' => $passedFinals->isNotEmpty() ? 'Lulus' : ($finals->isNotEmpty() ? 'Belum Lulus'
                : (FinalExamAttempt::where('user_id', $user->id)->exists() ? 'Dalam Proses' : 'Belum Dimulai')),
            'eligible' => $eligible,
            'missing' => $missing,
            'completed_at' => $eligible ? $this->completionDate($user, $passedFinals
                ->map(fn ($attempt) => $attempt->graded_at?->max($attempt->submitted_at) ?? $attempt->submitted_at)->min(), $chapters->pluck('slug')->all()) : null,
            'chapters' => $chapters->all(),
            'coding' => $coding->all(),
        ];
    }

    private function completionDate(User $user, Carbon $finalCompletedAt, array $slugs): Carbon
    {
        $dates = collect([$finalCompletedAt]);
        $dates->push($user->progress()->join('materials', 'materials.id', '=', 'user_progress.material_id')
            ->whereIn('materials.slug', $slugs)->where('user_progress.status', 'completed')->max('completed_at'));
        $quizDates = $user->quizAttempts()->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('chapters', 'chapters.id', '=', 'quizzes.chapter_id')->whereIn('chapters.slug', $slugs)
            ->where('quizzes.type', 'chapter_quiz')->where('quiz_attempts.status', 'completed')
            ->whereBetween('quiz_attempts.score', [ChapterQuizScoring::passingScore(), 100])
            ->whereColumn('quiz_attempts.completed_at', '>=', 'quiz_attempts.started_at')
            ->selectRaw('MIN(quiz_attempts.completed_at) AS completed_at')->groupBy('quizzes.chapter_id')->pluck('completed_at');
        $codingDates = ExerciseSubmission::where('exercise_submissions.user_id', $user->id)
            ->join('exercises', 'exercises.id', '=', 'exercise_submissions.exercise_id')
            ->whereIn('exercises.exercise_key', array_column(CodingProgressService::EXERCISES, 'key'))
            ->where('exercise_submissions.status', 'passed')->selectRaw('MIN(submitted_at) AS completed_at')
            ->groupBy('exercises.exercise_key')->pluck('completed_at');

        return Carbon::parse($dates->merge($quizDates)->merge($codingDates)->filter()->map(fn ($date) => Carbon::parse($date))->max());
    }
}
