<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\Admin\QuestionBankService;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::with('chapter')->orderBy('chapter_id')->get();
        $quiz = $quizzes->firstWhere('id', (int) $request->query('quiz_id')) ?? $quizzes->first();
        $questions = $quiz?->allQuestions()->get() ?? collect();

        return view('admin.questions.index', compact('quizzes', 'quiz', 'questions'));
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
        $question = $bank->save($request->validated());

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
        $draft = $bank->save($request->validated(), $question);

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
            'question_ids' => ['required', 'array', 'min:1', 'max:20'],
            'question_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $bank->publish($quiz, $data['question_ids']);

        return redirect()->route('admin.questions.index', ['quiz_id' => $quiz->id])->with('success', 'Paket soal diterbitkan. Percobaan yang sedang berlangsung tetap menggunakan versi sebelumnya.');
    }
}
