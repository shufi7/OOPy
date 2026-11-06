<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\FinalExamAttempt;
use App\Models\User;
use App\Services\Admin\LearningReportService;
use App\Services\Admin\UserDeletionService;
use App\Services\CodingProgressService;
use App\Services\DashboardProgressService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function confirmDelete(User $user)
    {
        abort_unless($user->role === 'user', 403);

        return view('admin.users.delete', compact('user'));
    }

    public function destroy(Request $request, User $user, UserDeletionService $deletion)
    {
        abort_unless($user->role === 'user', 403);
        $request->validate(['email_confirmation' => ['required', 'string', Rule::in([$user->email])]], [
            'email_confirmation.required' => 'Ketik email pengguna untuk mengonfirmasi penghapusan.',
            'email_confirmation.string' => 'Email konfirmasi tidak valid.',
            'email_confirmation.in' => 'Email konfirmasi harus sesuai dengan akun yang akan dihapus.',
        ]);
        $deletion->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna beserta progres dan riwayat nilainya telah dihapus.');
    }

    public function index(ReportRequest $request, LearningReportService $reports)
    {
        $users = $reports->users($request->validated())->paginate(20)->withQueryString();

        return response()->view('admin.users.index', compact('users', 'reports'))->header('Cache-Control', 'no-store, private');
    }

    public function show(User $user, LearningReportService $reports, DashboardProgressService $progress, CodingProgressService $coding)
    {
        abort_unless($user->role === 'user', 404);
        $dashboard = $progress->forUser($user);
        $history = $reports->completedAttempts()->where('user_id', $user->id)->with('quiz:id,title,passing_score')
            ->orderByDesc('completed_at')->paginate(20, ['*'], 'history_page')->withQueryString();
        $finals = FinalExamAttempt::where('user_id', $user->id)->whereNotNull('submitted_at')->orderByDesc('submitted_at')
            ->paginate(10, ['*'], 'final_page')->withQueryString();
        $codingHistory = $coding->history($user)->paginate(10, ['*'], 'coding_page')->withQueryString();

        return response()->view('admin.users.show', compact('user', 'dashboard', 'history', 'finals', 'codingHistory', 'coding'))->header('Cache-Control', 'no-store, private');
    }
}
