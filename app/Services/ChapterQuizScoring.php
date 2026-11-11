<?php

namespace App\Services;

class ChapterQuizScoring
{
    public static function passingScore(): float
    {
        return (float) config('quiz.passing_score');
    }

    public static function minimumCorrect(int $total): int
    {
        return (int) ceil($total * self::passingScore() / 100);
    }

    public static function passed(int $correct, int $total): bool
    {
        return $total > 0 && $correct * 100 >= $total * self::passingScore();
    }

    public static function score(int $correct, int $total): float
    {
        return $total > 0 ? round(min($total, max(0, $correct)) * 100 / $total, 2) : 0.0;
    }

    public static function summary(int $total): array
    {
        return [
            'total_questions' => $total,
            'points_per_question' => $total > 0 ? 100 / $total : null,
            'minimum_correct' => self::minimumCorrect($total),
            'passing_score' => self::passingScore(),
            'maximum_score' => 100,
        ];
    }

    public static function format(?float $score): string
    {
        return $score === null ? '—' : rtrim(rtrim(number_format($score, 2, ',', '.'), '0'), ',');
    }
}
