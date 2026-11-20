<?php

namespace App\Services;

use App\Models\User;

class ChapterAccessService
{
    public function forUser(?User $user): array
    {
        $registry = require resource_path('materi/chapters.php');
        $completed = $user ? $user->progress()
            ->join('materials', 'materials.id', '=', 'user_progress.material_id')
            ->join('chapters', 'chapters.id', '=', 'materials.chapter_id')
            ->whereColumn('materials.slug', 'chapters.slug')
            ->where('user_progress.status', 'completed')
            ->whereNotNull('user_progress.completed_at')
            ->pluck('materials.slug')->all() : [];

        $required = null;
        $access = [];
        foreach ($registry as $slug => $chapter) {
            $done = in_array($slug, $completed, true);
            $access[$slug] = [
                'locked' => $required !== null,
                'completed' => $done,
                'required_chapter' => $required,
            ];
            if ($required === null && ! $done) {
                $required = $chapter + ['slug' => $slug];
            }
        }

        return $access;
    }

    public function lockedMessage(array $required): string
    {
        return 'Selesaikan kuis '.$required['bab'].' ('.$required['judul'].') terlebih dahulu untuk membuka bab ini.';
    }
}
