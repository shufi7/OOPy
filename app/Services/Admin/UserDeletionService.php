<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->role === 'user', 403, 'Akun Admin tidak dapat dihapus melalui Manajemen Pengguna.');

            if (DB::table('final_exam_attempts')->where('graded_by', $user->id)->where('user_id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages(['deletion' => 'Pengguna ini tercatat sebagai penilai evaluasi pengguna lain dan tidak dapat dihapus.']);
            }

            DB::table('final_exam_attempts')->where('user_id', $user->id)->delete();
            // Answers and pinned questions cascade from their owning attempts.
            DB::table('quiz_attempts')->where('user_id', $user->id)->delete();
            DB::table('exercise_submissions')->where('user_id', $user->id)->delete();
            DB::table('user_progress')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        }, 3);
    }
}
