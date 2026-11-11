<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\Admin\QuestionBankService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function confirmDelete(Question $question, QuestionBankService $bank)
    {
        $deletionError = $bank->deletionError($question);

        return view('admin.questions.delete', compact('question', 'deletionError'));
    }

    public function destroy(Request $request, Question $question, QuestionBankService $bank)
    {
        $request->validate(['confirmation' => ['accepted']], [
            'confirmation.accepted' => 'Centang konfirmasi penghapusan terlebih dahulu.',
        ]);
        $quizId = $question->quiz_id;
        $bank->delete($question);

        return redirect()->route('admin.questions.index', ['quiz_id' => $quizId])->with('success', 'Soal telah dihapus dari Bank Soal.');
    }

    public function index(Request $request, QuestionBankService $bank)
    {
        $quizzes = Quiz::with('chapter')->orderBy('chapter_id')->get();
        $quiz = $quizzes->firstWhere('id', (int) $request->query('quiz_id')) ?? $quizzes->first();
        $questions = $quiz?->allQuestions()->get() ?? collect();
        $packageVersion = $bank->packageVersion($questions);

        return view('admin.questions.index', compact('quizzes', 'quiz', 'questions', 'packageVersion'));
    }

    public function create(Request $request)
    {
        return view('admin.questions.form', [
            'question' => new Question(['quiz_id' => (int) $request->query('quiz_id'), 'display_order' => 1]),
            'quizzes' => Quiz::orderBy('chapter_id')->get(),
        ]);
    }

    public function store(QuestionRequest $request, QuestionBankService $bank)
    {
        $question = $bank->save($request->safe()->except('question_version'));

        return redirect()->route('admin.questions.show', $question)->with('success', 'Draft soal tersimpan. Terbitkan paket soal lengkap untuk menggunakannya.');
    }

    public function show(Question $question)
    {
        return view('admin.questions.show', compact('question'));
    }

    public function edit(Question $question)
    {
        return view('admin.questions.form', ['question' => $question, 'quizzes' => Quiz::orderBy('chapter_id')->get()]);
    }

    public function update(QuestionRequest $request, Question $question, QuestionBankService $bank)
    {
        $draft = $bank->save($request->safe()->except('question_version'), $question, $request->validated('question_version'));

        return redirect()->route('admin.questions.show', $draft)->with('success', 'Revisi disimpan sebagai draft. Versi aktif dan riwayat jawaban tetap tersedia.');
    }

    public function archive(Question $question, QuestionBankService $bank)
    {
        $bank->archive($question);

        return redirect()->route('admin.questions.index', ['quiz_id' => $question->quiz_id])->with('success', 'Soal diarsipkan.');
    }

    public function publish(Request $request, Quiz $quiz, QuestionBankService $bank)
    {
        $data = $request->validate([
            'package_version' => [Rule::requiredIf($quiz->type === 'chapter_quiz'), 'nullable', 'string', 'size:64'],
            'question_ids' => ['required', 'array', 'list', 'min:'.($quiz->type === 'final_exam' ? 20 : config('quiz.minimum_questions')), 'max:'.($quiz->type === 'final_exam' ? 20 : config('quiz.maximum_questions'))],
            'question_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'question_ids.required' => 'Pilih soal untuk paket yang akan diterbitkan. Paket kosong tidak diperbolehkan.',
            'question_ids.min' => $quiz->type === 'final_exam' ? 'Evaluasi Akhir harus berisi 20 soal.' : 'Pilih minimal '.config('quiz.minimum_questions').' soal.',
            'question_ids.max' => $quiz->type === 'final_exam' ? 'Evaluasi Akhir harus berisi 20 soal.' : 'Pilih maksimal '.config('quiz.maximum_questions').' soal.',
            'question_ids.*.distinct' => 'Setiap soal hanya boleh dipilih sekali.',
            'package_version.required' => 'Muat ulang Bank Soal untuk memeriksa paket aktif sebelum menerbitkan.',
        ]);
        $bank->publish($quiz, $data['question_ids'], $data['package_version'] ?? null);

        return redirect()->route('admin.questions.index', ['quiz_id' => $quiz->id])->with('success', 'Paket soal diterbitkan. Percobaan yang sedang berlangsung tetap menggunakan versi sebelumnya.');
    }
}
