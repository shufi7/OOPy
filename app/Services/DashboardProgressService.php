<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\User;

class DashboardProgressService
{
    public function forUser(User $user): array
    {
        $registry = require resource_path('materi/chapters.php');
        $core = collect($registry)->filter(function ($metadata) {
            return ! empty($metadata['content']) && isset((require resource_path('materi/'.$metadata['content']))['quiz']);
        });
        $chapters = Chapter::whereIn('slug', $core->keys())->with('materials:id,chapter_id,slug')
            ->get(['id', 'slug', 'description'])->keyBy('slug');
        $materials = $chapters->flatMap(fn ($chapter) => $chapter->materials->where('slug', $chapter->slug))->keyBy('chapter_id');
        $progress = $user->progress()->whereIn('material_id', $materials->pluck('id'))
            ->get(['material_id', 'status', 'completed_at'])->keyBy('material_id');

        $attempts = $user->quizAttempts()->getQuery()->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quizzes.type', 'chapter_quiz')->whereIn('quizzes.chapter_id', $chapters->pluck('id'));
        $completed = (clone $attempts)->where('quiz_attempts.status', 'completed')
            ->whereNotNull('quiz_attempts.completed_at')->whereColumn('quiz_attempts.completed_at', '>=', 'quiz_attempts.started_at')
            ->whereBetween('quiz_attempts.score', [0, 100]);
        $scores = (clone $completed)->selectRaw('quizzes.chapter_id, MAX(quiz_attempts.score) AS best_score, COUNT(*) AS attempts_count')
            ->groupBy('quizzes.chapter_id')->get()->keyBy('chapter_id');
        $active = (clone $attempts)->where('quiz_attempts.status', 'in_progress')->whereNull('quiz_attempts.completed_at')
            ->distinct()->pluck('quizzes.chapter_id');

        $cards = $core->map(function ($metadata, $slug) use ($chapters, $materials, $progress, $scores, $active) {
            $chapter = $chapters->get($slug);
            $material = $chapter ? $materials->get($chapter->id) : null;
            $record = $material ? $progress->get($material->id) : null;
            $summary = $chapter ? $scores->get($chapter->id) : null;
            $done = $record?->status === 'completed' && $record->completed_at !== null;
            $started = $record?->status === 'in_progress' || $summary !== null || ($chapter && $active->contains($chapter->id));
            $status = $done ? 'completed' : ($started ? 'in_progress' : 'not_started');

            return [
                'slug' => $slug,
                'bab' => $metadata['bab'],
                'title' => $metadata['judul'],
                'description' => $chapter?->description ?: ($metadata['poin'][0] ?? null),
                'status' => $status,
                'status_label' => match ($status) {
                    'completed' => 'Selesai', 'in_progress' => 'Sedang Dipelajari', default => 'Belum Dimulai',
                },
                'best_score' => $summary ? (float) $summary->best_score : null,
                'best_score_label' => $this->formatScore($summary ? (float) $summary->best_score : null),
                'url' => route('materi.show', $slug),
                'action' => match ($status) {
                    'completed' => 'Pelajari Kembali', 'in_progress' => 'Lanjutkan Belajar', default => 'Mulai Belajar',
                },
            ];
        })->values();
        $done = $cards->where('status', 'completed')->count();
        $total = $cards->count();
        $next = $cards->first(fn ($card) => $card['status'] !== 'completed');
        $recommendation = $next ? [
            'title' => $next['bab'].' — '.$next['title'],
            'description' => $next['description'],
            'url' => $next['url'],
            'action' => ($done === 0 && $next['status'] === 'not_started' ? 'Mulai Belajar ' : 'Lanjutkan ').$next['bab'],
        ] : [
            'title' => 'Evaluasi Akhir OOPy',
            'description' => 'Uji pemahamanmu tentang pemrograman berorientasi objek setelah mempelajari BAB 1–6.',
            'url' => route('evaluasi.index'),
            'action' => 'Lanjut ke Evaluasi Akhir',
        ];

        $recent = (clone $completed)->orderByDesc('quiz_attempts.completed_at')->orderByDesc('quiz_attempts.id')->limit(5)
            ->get(['quiz_attempts.id', 'quiz_attempts.score', 'quiz_attempts.completed_at', 'quizzes.chapter_id'])
            ->map(function ($attempt) use ($chapters, $core) {
                $slug = $chapters->firstWhere('id', $attempt->chapter_id)->slug;
                $time = $attempt->completed_at->copy()->timezone('Asia/Makassar')->locale('id');

                return [
                    'title' => 'Kuis '.$core[$slug]['bab'],
                    'url' => route('materi.show', $slug).'#kuis',
                    'score' => $this->formatScore((float) $attempt->score),
                    'passed' => (float) $attempt->score >= round(config('quiz.minimum_correct') / config('quiz.total_questions') * 100),
                    'datetime' => $time->toIso8601String(),
                    'date_label' => $time->translatedFormat('j M Y, H:i').' WITA',
                ];
            })->all();

        return [
            'chapters' => $cards->all(),
            'completed_chapters' => $done,
            'total_chapters' => $total,
            'progress_percent' => $total ? (int) round($done / $total * 100) : 0,
            'average_score' => $scores->isEmpty() ? null : round($scores->avg('best_score'), 1),
            'average_score_label' => $this->formatScore($scores->isEmpty() ? null : round($scores->avg('best_score'), 1)),
            'completed_attempts' => (int) $scores->sum('attempts_count'),
            'recommendation' => $recommendation,
            'recent_activity' => $recent,
        ];
    }

    private function formatScore(?float $score): string
    {
        return $score === null ? '—' : number_format($score, $score === round($score) ? 0 : 1, ',', '.');
    }
}
