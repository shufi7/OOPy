<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function submit(User $user, QuizAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }
}
