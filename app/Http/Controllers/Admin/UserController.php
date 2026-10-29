<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\FinalExamAttempt;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\DashboardProgressService;

class UserController extends Controller
{
    public function index(ReportRequest $request, LearningReportService $reports)
    {
        $users = $reports->users($request->validated())->paginate(20)->withQueryString();

        return response()->view('admin.users.index', compact('users', 'reports'))->header('Cache-Control', 'no-store, private');
    }

    public function show(User $user, LearningReportService $reports, DashboardProgressService $progress)
    {
        abort_unless($user->role === 'user', 404);
        $dashboard = $progress->forUser($user);
        $history = $reports->completedAttempts()->where('user_id', $user->id)->with('quiz:id,title,passing_score')
            ->orderByDesc('completed_at')->paginate(20, ['*'], 'history_page')->withQueryString();
        $finals = FinalExamAttempt::where('user_id', $user->id)->whereNotNull('submitted_at')->orderByDesc('submitted_at')
            ->paginate(10, ['*'], 'final_page')->withQueryString();

        return response()->view('admin.users.show', compact('user', 'dashboard', 'history', 'finals'))->header('Cache-Control', 'no-store, private');
    }
}
