<?php

namespace App\Services\Admin;

use App\Models\Question;
use App\Models\Quiz;
use App\Services\ChapterQuizService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionBankService
{
    public function questionVersion(Question $question): string
    {
        return hash('sha256', json_encode($question->only([
            'quiz_id', 'question_type', 'question_text', 'code_snippet', 'options', 'correct_answer',
            'explanation', 'display_order', 'publication_status', 'essay_rubric',
        ]), JSON_THROW_ON_ERROR));
    }

    public function packageVersion(Collection $questions): string
    {
        return hash('sha256', $questions->where('publication_status', 'published')->sortBy('id')
            ->map(fn ($question) => [$question->id, $question->display_order ?? $question->question_order])->values()->toJson());
    }

    public function deletionError(Question $question): ?string
    {
        if ($question->publication_status === 'published') {
            return 'Soal aktif tidak dapat dihapus. Terbitkan paket pengganti yang lengkap terlebih dahulu.';
        }
        if ($question->answers()->exists() || DB::table('quiz_attempt_questions')->where('question_id', $question->id)->exists()) {
            return 'Soal ini digunakan dalam riwayat atau percobaan kuis. Simpan sebagai arsip agar riwayat tetap utuh.';
        }
        if (Question::where('revision_of_id', $question->id)->exists()) {
            return 'Soal ini masih menjadi acuan revisi lain. Hapus revisi yang tidak digunakan terlebih dahulu.';
        }

        return null;
    }

    public function delete(Question $question): void
    {
        DB::transaction(function () use ($question) {
            Quiz::whereKey($question->quiz_id)->lockForUpdate()->firstOrFail();
            $question = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();
            if ($error = $this->deletionError($question)) {
                throw ValidationException::withMessages(['deletion' => $error]);
            }
            $question->delete();
        }, 3);
    }

    public function save(array $data, ?Question $question = null, ?string $expectedVersion = null): Question
    {
        return DB::transaction(function () use ($data, $question, $expectedVersion) {
            $quiz = Quiz::whereKey($data['quiz_id'])->lockForUpdate()->firstOrFail();
            if ($question) {
                $question = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();
                if ($question->quiz_id !== $quiz->id) {
                    throw ValidationException::withMessages(['quiz_id' => 'Kuis revisi harus sama dengan soal asal.']);
                }
                if ($expectedVersion !== null && ! hash_equals($this->questionVersion($question), $expectedVersion)) {
                    throw ValidationException::withMessages(['question_text' => 'Soal telah diubah Admin lain. Muat ulang soal dan tinjau perubahan terbaru sebelum menyimpan.']);
                }
            }
            $data['publication_status'] = 'draft';
            if ($question && $question->publication_status === 'draft') {
                $question->update($data);

                return $question;
            }
            // Published content is immutable, including questions in an open attempt.
            $data['question_order'] = (int) Question::where('quiz_id', $quiz->id)->max('question_order') + 1;
            $data['revision_of_id'] = $question?->id;

            return Question::create($data);
        }, 3);
    }

    public function archive(Question $question): void
    {
        DB::transaction(function () use ($question) {
            Quiz::whereKey($question->quiz_id)->lockForUpdate()->firstOrFail();
            $question = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();
            if ($question->publication_status === 'published') {
                throw ValidationException::withMessages(['publication' => 'Soal aktif hanya dapat diarsipkan dengan menerbitkan paket pengganti yang lengkap.']);
            }
            $question->update(['publication_status' => 'archived']);
        }, 3);
    }

    public function publish(Quiz $quiz, array $ids, ?string $expectedVersion = null): void
    {
        DB::transaction(function () use ($quiz, $ids, $expectedVersion) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            if ($expectedVersion !== null && ! hash_equals($this->packageVersion($quiz->questions()->get()), $expectedVersion)) {
                throw ValidationException::withMessages(['publication' => 'Paket aktif telah diubah Admin lain. Tinjau paket terbaru dan pilihan soal sebelum menerbitkan kembali.']);
            }
            $questions = Question::where('quiz_id', $quiz->id)->whereIn('id', $ids)
                ->whereIn('publication_status', $quiz->type === 'chapter_quiz' ? ['draft', 'published', 'archived'] : ['draft', 'published'])
                ->lockForUpdate()->get();
            if (count(array_unique($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['question_ids' => 'Setiap soal hanya boleh dipilih sekali.']);
            }
            if ($questions->count() !== count($ids)) {
                throw ValidationException::withMessages(['question_ids' => $quiz->type === 'chapter_quiz'
                    ? 'Pilih hanya soal dari kuis ini. Soal kuis lain atau ID yang tidak tersedia tidak dapat diterbitkan.'
                    : 'Pilih hanya soal draft atau aktif dari kuis ini. Soal arsip dan soal kuis lain tidak dapat diterbitkan.']);
            }
            if ($quiz->type === 'final_exam') {
                $expected = collect(['multiple_choice' => 10, 'code_fill' => 10]);
                $actual = $questions->countBy('question_type');
                if (count($ids) !== 20 || $expected->contains(fn ($count, $type) => $actual->get($type, 0) !== $count)) {
                    throw ValidationException::withMessages(['question_ids' => 'Pilih tepat 10 pilihan ganda dan 10 isian kode dari kuis ini.']);
                }
            } elseif ($questions->count() < config('quiz.minimum_questions') || $questions->count() > config('quiz.maximum_questions')
                || $questions->contains(fn ($question) => ! in_array($question->question_type, ['multiple_choice', 'code_fill'], true))) {
                throw ValidationException::withMessages(['question_ids' => 'Pilih '.config('quiz.minimum_questions').' sampai '.config('quiz.maximum_questions').' soal pilihan ganda atau isian kode dari kuis ini. Uraian hanya untuk Evaluasi Akhir.']);
            }
            foreach ($questions as $question) {
                try {
                    $question->validateForPublication();
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['question_ids' => 'Soal #'.$question->id.' belum valid: '.collect($exception->errors())->flatten()->first()]);
                }
            }
            if ($questions->map(fn ($question) => $question->display_order ?? $question->question_order)->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['question_ids' => 'Urutan soal terpilih harus berbeda. Edit urutan draft sebelum menerbitkan.']);
            }
            if ($quiz->type === 'chapter_quiz') {
                app(ChapterQuizService::class)->pinLegacyAttempts($quiz);
            }
            // One transaction changes the whole published set; existing attempts keep their IDs.
            Question::where('quiz_id', $quiz->id)->where('publication_status', 'published')
                ->whereNotIn('id', $ids)->update(['publication_status' => 'archived', 'updated_at' => now()]);
            Question::where('quiz_id', $quiz->id)->whereIn('id', $ids)
                ->update(['publication_status' => 'published', 'updated_at' => now()]);
        }, 3);
    }
}
