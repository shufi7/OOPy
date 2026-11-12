<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Services\Admin\QuestionBankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefreshFinalExam extends Command
{
    protected $signature = 'oopy:refresh-final-exam {--quiz= : ID kuis} {--apply : Terbitkan revisi} {--revision= : Sidik isi paket dari preview}';

    protected $description = 'Preview revisi akademis Evaluasi Akhir; lindungi suntingan Admin dan snapshot percobaan';

    public function handle(QuestionBankService $bank): int
    {
        try {
            return DB::transaction(function () use ($bank) {
                $query = Quiz::where('type', 'final_exam');
                if ($this->option('quiz')) {
                    $query->whereKey($this->option('quiz'));
                }
                $quizzes = $query->lockForUpdate()->get();
                if ($quizzes->count() !== 1) {
                    $this->error('Pilih tepat satu Evaluasi Akhir dengan --quiz=ID.');

                    return self::FAILURE;
                }
                $quiz = $quizzes->first();
                $questions = $quiz->questions()->lockForUpdate()->get();
                $target = (require resource_path('materi/evaluasi-akhir.php'))['questions'];
                $baseline = (require resource_path('evaluasi/baseline-2026-11-12.php'))['questions'];
                if ($questions->count() !== 20) {
                    $this->error('Paket harus berisi 20 soal. Tinjau melalui Bank Soal Admin.');

                    return self::FAILURE;
                }
                $revision = hash('sha256', json_encode([
                    $questions->map(fn ($q) => [$q->id, $q->question_order, $bank->questionVersion($q)])->all(), $target,
                ], JSON_THROW_ON_ERROR));
                $this->info('Kuis #'.$quiz->id.' | revision: '.$revision);
                $changes = [];
                $conflicts = [];
                foreach ($questions->values() as $index => $question) {
                    $current = $this->fields($target[$index]);
                    if ($question->only(array_keys($current)) === $current) {
                        continue;
                    }
                    if ($question->only(array_keys($current)) !== $this->fields($baseline[$index])) {
                        $conflicts[] = $question->id;

                        continue;
                    }
                    $changes[] = [$question, $current];
                }
                if ($conflicts !== []) {
                    $this->error('Suntingan Admin atau urutan berbeda terdeteksi pada soal #'.implode(', #', $conflicts).'. Tidak ada perubahan; tinjau revisi melalui Bank Soal Admin.');

                    return self::FAILURE;
                }
                $this->table(['Soal asal', 'Redaksi baru', 'Kunci server'], array_map(fn ($change) => [
                    $change[0]->id, $change[1]['question_text'], $change[1]['correct_answer'],
                ], $changes));
                if ($changes === []) {
                    $this->info('Paket sudah sesuai revisi akademis. Tidak ada perubahan.');

                    return self::SUCCESS;
                }
                if (! $this->option('apply')) {
                    $this->info('Preview saja; tidak ada data ditulis. Setelah backup dan tinjauan, gunakan --apply --quiz='.$quiz->id.' --revision='.$revision);

                    return self::SUCCESS;
                }
                if (! is_string($this->option('revision')) || ! hash_equals($revision, $this->option('revision'))) {
                    $this->error('Sidik revisi tidak cocok. Jalankan ulang preview sebelum menerbitkan.');

                    return self::FAILURE;
                }
                $packageVersion = $bank->packageVersion($questions);
                $ids = $questions->pluck('id')->all();
                foreach ($changes as [$question, $fields]) {
                    $draft = $bank->save($fields + [
                        'quiz_id' => $quiz->id,
                        'display_order' => $question->display_order ?? $question->question_order,
                    ], $question, $bank->questionVersion($question));
                    $ids[array_search($question->id, $ids, true)] = $draft->id;
                }
                $bank->publish($quiz, $ids, $packageVersion);
                $this->info('Revisi diterbitkan. Soal lama diarsipkan; snapshot, jawaban, dan nilai percobaan tetap utuh.');

                return self::SUCCESS;
            }, 3);
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }
    }

    private function fields(array $question): array
    {
        return [
            'question_type' => $question['type'], 'question_text' => $question['question'],
            'code_snippet' => $question['code'] ?? null, 'options' => $question['options'] ?? null,
            'correct_answer' => (string) ($question['answer'] ?? $question['correct']),
            'explanation' => $question['explanation'] ?? null, 'essay_rubric' => null,
        ];
    }
}
