<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalExamAttempt extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $hidden = ['snapshot'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array', 'answers' => 'array', 'essay_scores' => 'array', 'essay_feedback' => 'array',
            'objective_score' => 'decimal:2', 'objective_points' => 'decimal:2',
            'essay_score' => 'decimal:2', 'essay_points' => 'decimal:2', 'total_score' => 'decimal:2',
            'passing_score' => 'decimal:2', 'duration_seconds' => 'integer', 'timed_out' => 'boolean',
            'started_at' => 'datetime', 'deadline_at' => 'datetime', 'submitted_at' => 'datetime', 'graded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
