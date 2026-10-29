<?php

namespace App\Services\Admin;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionBankService
{
    public function save(array $data, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($data, $question) {
            $quiz = Quiz::whereKey($data['quiz_id'])->lockForUpdate()->firstOrFail();
            if ($question) {
                $question = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();
                if ($question->quiz_id !== $quiz->id) {
                    throw ValidationException::withMessages(['quiz_id' => 'Kuis revisi harus sama dengan soal asal.']);
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

    public function publish(Quiz $quiz, array $ids): void
    {
        DB::transaction(function () use ($quiz, $ids) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $questions = Question::where('quiz_id', $quiz->id)->whereIn('id', $ids)
                ->whereIn('publication_status', ['draft', 'published'])->lockForUpdate()->get();
            $expected = $quiz->type === 'final_exam'
                ? ['multiple_choice' => 10, 'code_fill' => 5, 'essay' => 5]
                : ['multiple_choice' => (int) config('quiz.multiple_choice'), 'code_fill' => (int) config('quiz.code_fill'), 'essay' => 0];
            $actual = $questions->countBy('question_type');
            if (count(array_unique($ids)) !== count($ids) || $questions->count() !== count($ids)
                || count($ids) !== array_sum($expected)
                || collect($expected)->contains(fn ($count, $type) => $actual->get($type, 0) !== $count)) {
                throw ValidationException::withMessages(['question_ids' => $quiz->type === 'final_exam'
                    ? 'Pilih tepat 10 pilihan ganda, 5 isian kode, dan 5 uraian dari kuis ini.'
                    : 'Pilih tepat 3 pilihan ganda dan 2 isian kode dari kuis ini.']);
            }
            if ($questions->map(fn ($question) => $question->display_order ?? $question->question_order)->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['question_ids' => 'Urutan soal terpilih harus berbeda. Edit urutan draft sebelum menerbitkan.']);
            }
            // One transaction changes the whole published set; existing attempts keep their IDs.
            Question::where('quiz_id', $quiz->id)->where('publication_status', 'published')
                ->whereNotIn('id', $ids)->update(['publication_status' => 'archived', 'updated_at' => now()]);
            Question::where('quiz_id', $quiz->id)->whereIn('id', $ids)
                ->update(['publication_status' => 'published', 'updated_at' => now()]);
        }, 3);
    }
}
