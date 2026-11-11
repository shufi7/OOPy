<?php

namespace App\Services\Admin;

use App\Models\Chapter;
use App\Models\FinalExamAttempt;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\ChapterQuizScoring;
use App\Services\CodingProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class LearningReportService
{
    private ?Collection $chapterCache = null;

    public function __construct(private CodingProgressService $coding) {}

    public function chapters()
    {
        if ($this->chapterCache !== null) {
            return $this->chapterCache;
        }
        // Data files can have whitespace before PHP; it must not precede the CSV BOM.
        ob_start();
        try {
            $registry = require resource_path('materi/chapters.php');
            $slugs = array_keys(array_filter($registry, fn ($item) => isset((require resource_path('materi/'.$item['content']))['quiz'])));
        } finally {
            ob_end_clean();
        }

        return $this->chapterCache = Chapter::whereIn('slug', $slugs)->orderBy('chapter_order')->get();
    }

    public function passingScore(): float
    {
        return ChapterQuizScoring::passingScore();
    }

    public function completedAttempts(): Builder
    {
        return QuizAttempt::query()->where('status', 'completed')->whereNotNull('completed_at')
            ->whereColumn('completed_at', '>=', 'started_at')->whereBetween('score', [0, 100])
            ->whereHas('quiz', fn ($query) => $query->where('type', 'chapter_quiz')->whereIn('chapter_id', $this->chapters()->pluck('id')));
    }

    private function userAttempts(): Builder
    {
        return $this->completedAttempts()->whereColumn('quiz_attempts.user_id', 'users.id');
    }

    private function chapterAttempts(int $chapterId): Builder
    {
        return $this->userAttempts()->whereHas('quiz', fn ($query) => $query->where('chapter_id', $chapterId));
    }

    public function users(array $filters = []): Builder
    {
        $chapters = $this->chapters();
        $query = User::query()->select(['users.id', 'users.name', 'users.email', 'users.created_at'])->where('users.role', 'user')
            ->leftJoinSub($this->coding->summaries(), 'coding', 'coding.user_id', '=', 'users.id')
            ->addSelect(['coding.coding_attempts', 'coding.coding_last_at', 'coding.coding_best_score']);
        foreach (CodingProgressService::EXERCISES as $number => $exercise) {
            $query->addSelect(["coding.coding_{$number}_attempts", "coding.coding_{$number}_passed",
                "coding.coding_{$number}_best_score", "coding.coding_{$number}_last_at"]);
        }
        $search = $filters['q'] ?? null;
        if ($search !== null && $search !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($query) => $query->whereRaw("users.name LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("users.email LIKE ? ESCAPE '!'", [$pattern]));
        }
        $query->addSelect([
            'completed_attempts' => $this->userAttempts()->selectRaw('COUNT(*)'),
            'best_score' => $this->userAttempts()->selectRaw('MAX(score)'),
            'last_attempt_at' => $this->userAttempts()->selectRaw('MAX(completed_at)'),
            'final_score' => FinalExamAttempt::query()->selectRaw('MAX(total_score)')->whereColumn('user_id', 'users.id')
                ->where('status', 'completed')->whereNotNull('submitted_at')->whereNotNull('total_score'),
            'final_pending' => FinalExamAttempt::query()->selectRaw('COUNT(*)')->whereColumn('user_id', 'users.id')
                ->where('status', 'pending_review')->whereNotNull('submitted_at'),
            'final_submitted_at' => FinalExamAttempt::query()->selectRaw('MAX(submitted_at)')->whereColumn('user_id', 'users.id')
                ->whereNotNull('submitted_at'),
        ])->withCount(['progress as completed_chapters' => fn ($query) => $query->where('status', 'completed')->whereNotNull('completed_at')
            ->whereHas('material', fn ($query) => $query->whereIn('chapter_id', $chapters->pluck('id')))]);
        foreach ($chapters as $chapter) {
            $query->addSelect(['chapter_'.$chapter->chapter_order => $this->chapterAttempts($chapter->id)->selectRaw('MAX(score)')]);
        }
        $selected = isset($filters['chapter']) ? $chapters->firstWhere('chapter_order', (int) $filters['chapter']) : null;
        if (! empty($filters['chapter']) && ! $selected) {
            $query->whereRaw('1 = 0');
        }
        $attempts = $selected ? $this->chapterAttempts($selected->id) : $this->userAttempts();
        switch ($filters['status'] ?? '') {
            case 'attempted':
                $query->whereExists((clone $attempts)->selectRaw('1')->toBase());
                break;
            case 'not_started':
                $query->whereNotExists((clone $attempts)->selectRaw('1')->toBase());
                break;
            case 'passed':
                if ($selected) {
                    $query->where((clone $attempts)->selectRaw('CAST(MAX(score) AS DECIMAL(5,2))'), '>=', $this->passingScore());
                } else {
                    foreach ($chapters as $chapter) {
                        $query->where($this->chapterAttempts($chapter->id)->selectRaw('CAST(MAX(score) AS DECIMAL(5,2))'), '>=', $this->passingScore());
                    }
                    if ($chapters->isEmpty()) {
                        $query->whereRaw('1 = 0');
                    }
                }
                break;
            case 'not_passed':
                if ($selected) {
                    $query->where((clone $attempts)->selectRaw('CAST(MAX(score) AS DECIMAL(5,2))'), '<', $this->passingScore());
                } else {
                    $query->where(function ($query) use ($chapters) {
                        if ($chapters->isEmpty()) {
                            $query->whereRaw('1 = 0');
                        }
                        foreach ($chapters as $chapter) {
                            $query->orWhere($this->chapterAttempts($chapter->id)->selectRaw('CAST(MAX(score) AS DECIMAL(5,2))'), '<', $this->passingScore());
                        }
                    });
                }
                break;
        }

        $codingNumber = array_search($filters['coding_exercise'] ?? '', array_column(CodingProgressService::EXERCISES, 'key'), true);
        $codingPrefix = $codingNumber === false ? 'coding' : 'coding_'.($codingNumber + 1);
        if (! empty($filters['coding_exercise']) && $codingNumber === false) {
            $query->whereRaw('1 = 0');
        }
        $codingAttempts = "COALESCE(coding.{$codingPrefix}_attempts, 0)";
        $codingPassed = $codingNumber === false
            ? 'COALESCE(coding.coding_1_passed, 0) + COALESCE(coding.coding_2_passed, 0)'
            : "COALESCE(coding.{$codingPrefix}_passed, 0)";
        $codingTotal = $codingNumber === false ? 2 : 1;
        switch ($filters['coding_status'] ?? '') {
            case 'not_started':
                $query->whereRaw($codingAttempts.' = 0');
                break;
            case 'in_progress':
                $query->whereRaw($codingAttempts.' > 0')->whereRaw('('.$codingPassed.') < ?', [$codingTotal]);
                break;
            case 'completed':
                $query->whereRaw('('.$codingPassed.') = ?', [$codingTotal]);
                break;
        }

        return match ($filters['sort'] ?? 'name') {
            'coding_latest' => $query->orderByDesc('coding.'.$codingPrefix.'_last_at')->orderBy('users.id'),
            'coding_best' => $query->orderByDesc('coding.'.$codingPrefix.'_best_score')->orderBy('users.id'),
            'latest' => $query->orderByDesc($selected ? (clone $attempts)->selectRaw('MAX(completed_at)') : 'last_attempt_at')->orderBy('users.id'),
            'best' => $query->orderByDesc($selected ? 'chapter_'.$selected->chapter_order : 'best_score')->orderBy('users.id'),
            default => $query->orderBy('users.name')->orderBy('users.id'),
        };
    }

    public function row(User $user): array
    {
        $scores = collect(range(1, 6))->mapWithKeys(fn ($number) => [$number => $user->getAttribute('chapter_'.$number) === null ? null : (float) $user->getAttribute('chapter_'.$number)]);
        $available = $scores->filter(fn ($score) => $score !== null);
        $last = collect([$user->last_attempt_at, $user->final_submitted_at])->filter()->sort()->last();

        return [
            'user' => $user, 'scores' => $scores, 'completed_quizzes' => $available->count(),
            'average' => $available->isEmpty() ? null : round($available->avg(), 1),
            'status' => $available->count() === 0 ? 'Belum mengerjakan' : ($available->count() === 6 && $available->min() >= $this->passingScore() ? 'Lulus semua BAB' : 'Belum lulus semua BAB'),
            'last_at' => $last,
            'coding' => $this->coding->summary($user, 'Belum Dikerjakan'),
        ];
    }

    public function dashboard(): array
    {
        $attempts = $this->completedAttempts()->whereHas('user', fn ($query) => $query->where('role', 'user'));
        $students = User::where('role', 'user');
        $finals = FinalExamAttempt::whereNotNull('submitted_at')->whereHas('user', fn ($query) => $query->where('role', 'user'));

        return [
            'users' => (clone $students)->count(),
            'quiz_users' => (clone $students)->whereHas('quizAttempts')->count(),
            'active_users' => (clone $students)->where(fn ($query) => $query->whereHas('quizAttempts')
                ->orWhereHas('progress', fn ($query) => $query->where('status', '<>', 'not_started'))
                ->orWhereHas('exerciseSubmissions')->orWhereIn('id', FinalExamAttempt::select('user_id')))->count(),
            'chapters' => $this->chapters()->count(),
            'questions' => Question::where('publication_status', 'published')->count(),
            'attempts' => (clone $attempts)->count(),
            'average' => (clone $attempts)->avg('score'),
            'finals' => (clone $finals)->count(),
            'pending' => (clone $finals)->where('status', 'pending_review')->count(),
            'final_average' => (clone $finals)->where('status', 'completed')->avg('total_score'),
            'recent' => (clone $attempts)->with(['user:id,name', 'quiz:id,title,passing_score'])->orderByDesc('completed_at')->limit(10)->get(),
            'recent_finals' => (clone $finals)->with('user:id,name')->orderByDesc('submitted_at')->limit(5)->get(),
            'coding' => $this->codingStatistics(),
            'recent_coding' => $this->coding->history()->whereHas('user', fn ($query) => $query->where('role', 'user'))
                ->with('user:id,name')->limit(10)->get()->map(fn ($submission) => $this->coding->activity($submission) + [
                    'user_name' => $submission->user->name,
                    'user_url' => route('admin.users.show', $submission->user_id),
                ]),
        ];
    }

    private function codingStatistics(): array
    {
        $totals = User::query()->joinSub($this->coding->summaries(), 'coding', 'coding.user_id', '=', 'users.id')
            ->where('users.role', 'user')
            ->selectRaw('COUNT(*) AS attempted_users,
                COALESCE(SUM(coding.coding_1_passed), 0) AS exercise_1_completed,
                COALESCE(SUM(coding.coding_2_passed), 0) AS exercise_2_completed,
                COALESCE(SUM(CASE WHEN coding.coding_1_passed + coding.coding_2_passed = 2 THEN 1 ELSE 0 END), 0) AS both_completed,
                COALESCE(SUM(CASE WHEN coding.coding_1_passed + coding.coding_2_passed = 0 THEN 1 ELSE 0 END), 0) AS none_passed')
            ->first();

        return collect(['attempted_users', 'exercise_1_completed', 'exercise_2_completed', 'both_completed', 'none_passed'])
            ->mapWithKeys(fn ($key) => [$key => (int) $totals->$key])->all();
    }

    public static function score(mixed $score): string
    {
        return ChapterQuizScoring::format($score === null ? null : (float) $score);
    }

    public static function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Makassar')->format('d/m/Y H:i').' WITA' : '—';
    }

    public static function csvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text) ? "'".$text : $text;
    }
}
