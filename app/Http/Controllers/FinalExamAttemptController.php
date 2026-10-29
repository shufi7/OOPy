<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinalExamAnswerRequest;
use App\Services\FinalExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinalExamAttemptController extends Controller
{
    public function state(Request $request, FinalExamService $exam): JsonResponse
    {
        return response()->json($exam->state($request))->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request, FinalExamService $exam): JsonResponse
    {
        return response()->json(['attempt' => $exam->start($request), 'serverNow' => now()->getTimestampMs()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function save(FinalExamAnswerRequest $request, string $id, FinalExamService $exam): JsonResponse
    {
        return response()->json(['attempt' => $exam->saveAnswers($request, $id, $request->validated('answers'), false)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function submit(FinalExamAnswerRequest $request, string $id, FinalExamService $exam): JsonResponse
    {
        return response()->json(['attempt' => $exam->saveAnswers($request, $id, $request->validated('answers'), true)])
            ->header('Cache-Control', 'private, no-store');
    }
}
