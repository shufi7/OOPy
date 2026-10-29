<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeFinalExamRequest;
use App\Models\FinalExamAttempt;
use App\Services\FinalExamService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FinalExamController extends Controller
{
    public function show(FinalExamAttempt $evaluation): View
    {
        abort_if($evaluation->submitted_at === null, 404);
        $evaluation->load('user', 'grader');

        return view('admin.evaluations.show', compact('evaluation'));
    }

    public function update(GradeFinalExamRequest $request, FinalExamAttempt $evaluation, FinalExamService $exam): RedirectResponse
    {
        $exam->grade($evaluation, $request->user(), $request->validated('scores'), $request->validated('feedback') ?? []);

        return redirect()->route('admin.evaluations.show', $evaluation)->with('status', 'Penilaian uraian tersimpan. Nilai total sudah dapat direkap.');
    }
}
