<?php

namespace Tests\Support;

use App\Models\Exercise;
use App\Models\FinalExamAttempt;
use App\Models\User;
use App\Services\ChapterQuizService;
use App\Services\CodingProgressService;
use App\Services\FinalExamService;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;

class CertificationFixture
{
    public static function complete(User $user): void
    {
        $quizzes = app(ChapterQuizService::class);
        foreach (require resource_path('materi/chapters.php') as $slug => $metadata) {
            if (! isset((require resource_path('materi/'.$metadata['content']))['quiz'])) {
                continue;
            }
            $quiz = $quizzes->quiz($slug);
            $attempt = $quizzes->start($quiz, $user);
            app(QuizGradingService::class)->submit($quiz, $attempt, $user, $quiz->questions->map(fn ($question) => [
                'question_id' => $question->id,
                'answer' => $question->question_type === 'multiple_choice' ? (int) $question->correct_answer : $question->correct_answer,
            ])->all());
        }
        foreach (CodingProgressService::EXERCISES as $exercise) {
            $user->exerciseSubmissions()->create([
                'exercise_id' => Exercise::where('exercise_key', $exercise['key'])->sole()->id,
                'code' => 'test-fixture-only', 'status' => 'passed', 'score' => 100,
            ]);
        }
        $request = Request::create('/evaluasi/attempts', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));
        $service = app(FinalExamService::class);
        $record = $service->start($request);
        $attempt = FinalExamAttempt::findOrFail($record['id']);
        $answers = collect($attempt->snapshot)->mapWithKeys(fn ($question) => [
            $question['id'] => $question['type'] === 'multiple_choice' ? $question['correct'] : $question['answer'],
        ])->all();
        $service->saveAnswers($request, $attempt->id, $answers, true);
    }
}
