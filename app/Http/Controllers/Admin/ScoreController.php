<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Services\Admin\LearningReportService;

class ScoreController extends Controller
{
    public function index(ReportRequest $request, LearningReportService $reports)
    {
        $filters = $request->validated();
        $users = $reports->users($filters)->paginate(20)->withQueryString();
        $chapters = $reports->chapters();

        return response()->view('admin.scores.index', compact('users', 'reports', 'filters', 'chapters'))->header('Cache-Control', 'no-store, private');
    }

    public function export(ReportRequest $request, LearningReportService $reports)
    {
        $query = $reports->users($request->validated());

        return response()->streamDownload(function () use ($query, $reports) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Nama Pengguna', 'Email', 'BAB 1', 'BAB 2', 'BAB 3', 'BAB 4', 'BAB 5', 'BAB 6', 'Evaluasi Akhir', 'Status Evaluasi', 'Kuis Selesai', 'Percobaan Selesai', 'Rata-rata Kuis Terbaik', 'Status Kuis', 'Pengerjaan Terakhir'], ';', '"', '');
            foreach ($query->cursor() as $user) {
                $row = $reports->row($user);
                $cells = [$user->name, $user->email, ...$row['scores']->map(fn ($score) => $reports::score($score))->values(),
                    $reports::score($user->final_score), $user->final_pending > 0 ? 'Menunggu penilaian uraian' : ($user->final_score === null ? 'Belum mengerjakan' : 'Dinilai'),
                    $row['completed_quizzes'], $user->completed_attempts, $reports::score($row['average']), $row['status'], $reports::date($row['last_at'])];
                fputcsv($stream, array_map([$reports, 'csvCell'], $cells), ';', '"', '');
            }
            fclose($stream);
        }, 'rekap-nilai-oopy-'.now('Asia/Makassar')->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
