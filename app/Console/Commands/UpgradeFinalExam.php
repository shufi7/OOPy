<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Services\Admin\QuestionBankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpgradeFinalExam extends Command
{
    protected $signature = 'oopy:upgrade-final-exam {--quiz= : ID kuis Evaluasi Akhir} {--apply : Terbitkan perubahan} {--package-version= : Versi paket dari preview}';

    protected $description = 'Preview atau terbitkan transisi 10 PG + 5 kode + 5 uraian menjadi 10 PG + 10 kode tanpa mengubah riwayat';

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
                    $this->error('Pilih tepat satu kuis Evaluasi Akhir menggunakan --quiz=ID.');

                    return self::FAILURE;
                }
                $quiz = $quizzes->first();
                $questions = $quiz->questions()->lockForUpdate()->get();
                $counts = $questions->countBy('question_type');
                $version = $bank->packageVersion($questions);
                $this->info('Kuis #'.$quiz->id.' | versi paket: '.$version);
                $this->line('Aktif: '.$questions->count().' soal | PG: '.$counts->get('multiple_choice', 0).' | kode: '.$counts->get('code_fill', 0).' | uraian: '.$counts->get('essay', 0));
                if ($questions->count() === 20 && $counts->get('multiple_choice', 0) === 10 && $counts->get('code_fill', 0) === 10) {
                    $this->info('Paket sudah menggunakan format baru. Tidak ada perubahan.');

                    return self::SUCCESS;
                }
                if ($questions->count() !== 20 || $counts->get('multiple_choice', 0) !== 10
                    || $counts->get('code_fill', 0) !== 5 || $counts->get('essay', 0) !== 5) {
                    $this->error('Komposisi bukan paket lama 10/5/5. Tinjau dan terbitkan paket melalui Bank Soal Admin.');

                    return self::FAILURE;
                }
                $replacements = array_slice((require resource_path('materi/evaluasi-akhir.php'))['questions'], 15);
                $essays = $questions->where('question_type', 'essay')->values();
                $this->table(['Arsip soal', 'Isian kode pengganti', 'Kunci (server)'], $essays->map(fn ($question, $index) => [
                    $question->id, $replacements[$index]['question'], $replacements[$index]['answer'],
                ])->all());
                if (! $this->option('apply')) {
                    $this->info('Preview saja. Tidak ada data yang ditulis. Gunakan --apply --quiz='.$quiz->id.' --package-version='.$version.' setelah paket ditinjau dan backup dibuat.');

                    return self::SUCCESS;
                }
                if (! is_string($this->option('package-version')) || ! hash_equals($version, $this->option('package-version'))) {
                    $this->error('Versi paket wajib sesuai preview terbaru. Jalankan ulang tanpa --apply.');

                    return self::FAILURE;
                }
                $ids = $questions->where('question_type', '!=', 'essay')->pluck('id')->all();
                foreach ($essays as $index => $essay) {
                    $replacement = $replacements[$index];
                    $draft = $bank->save([
                        'quiz_id' => $quiz->id, 'question_type' => 'code_fill',
                        'question_text' => $replacement['question'], 'code_snippet' => $replacement['code'],
                        'options' => null, 'correct_answer' => $replacement['answer'],
                        'display_order' => $essay->display_order ?? $essay->question_order,
                        'essay_rubric' => null,
                    ], $essay, $bank->questionVersion($essay));
                    $ids[] = $draft->id;
                }
                $bank->publish($quiz, $ids, $version);
                $this->info('Paket 10 PG + 10 isian kode diterbitkan. Lima uraian diarsipkan; snapshot dan nilai percobaan lama tetap utuh.');

                return self::SUCCESS;
            }, 3);
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }
    }
}
