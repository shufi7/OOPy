<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quiz\StartQuizRequest;
use App\Http\Requests\Quiz\SubmitQuizRequest;
use App\Models\QuizAttempt;
use App\Services\ChapterQuizService;
use App\Services\QuizGradingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private ChapterQuizService $quizzes, private QuizGradingService $grading) {}

    public function start(StartQuizRequest $request, string $slug): JsonResponse
    {
        return $this->respond(function () use ($request, $slug) {
            $attempt = $this->quizzes->start($this->quizzes->quiz($slug), $request->user());

            return [
                'attempt_id' => $attempt->id,
                'submit_url' => route('quiz.submit', [$slug, $attempt->id]),
            ];
        });
    }

    public function submit(SubmitQuizRequest $request, string $slug, QuizAttempt $attempt): JsonResponse
    {
        return $this->respond(fn () => $this->grading->submit(
            $this->quizzes->quiz($slug), $attempt, $request->user(), $request->validated('answers')
        ));
    }

    public function progress(Request $request, string $slug): JsonResponse
    {
        return $this->respond(fn () => $this->quizzes->progress($this->quizzes->quiz($slug), $request->user()));
    }

    private function respond(callable $operation): JsonResponse
    {
        try {
            return response()->json($operation())->header('Cache-Control', 'no-store, private');
        } catch (QueryException $exception) {
            report($exception);

            return response()->json(['message' => 'Penyimpanan kuis sedang tidak tersedia. Jawaban belum dikonfirmasi tersimpan; silakan coba lagi.'], 503);
        }
    }
}
