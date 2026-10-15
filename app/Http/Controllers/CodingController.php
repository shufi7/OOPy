<?php

namespace App\Http\Controllers;

use App\Http\Requests\Coding\SubmitCodingRequest;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Services\CodingExerciseCatalog;
use App\Services\CodingGraderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CodingController extends Controller
{
    public function __construct(private CodingExerciseCatalog $catalog) {}

    public function index(Request $request): View
    {
        $exercises = $this->catalog->all();
        $completed = $request->user()
            ? Exercise::whereIn('exercise_key', array_column($exercises, 'key'))
                ->whereHas('submissions', fn ($query) => $query->where('user_id', $request->user()->id)->where('status', 'passed'))
                ->pluck('exercise_key')->all()
            : [];

        return view('coding.index', compact('exercises', 'completed'));
    }

    public function show(Request $request, string $slug): View
    {
        $exercise = $this->catalog->find($slug);
        $record = Exercise::where('exercise_key', $exercise['key'])->first();
        $completed = $record && $request->user()
            ? $record->submissions()->where('user_id', $request->user()->id)->where('status', 'passed')->exists()
            : false;
        $config = [
            'id' => $exercise['key'], 'exercise_key' => $exercise['key'], 'exercise_id' => $record?->id,
            'title' => 'Editor Python', 'description' => '', 'files' => $exercise['files'],
            'entry_file' => 'main.py', 'checker' => $exercise['checker'],
            'submit_url' => route('coding.submit', $slug), 'persistence_enabled' => true,
            'authenticated' => $request->user() !== null, 'completed' => $completed,
            'exercise_number' => $exercise['number'],
            'require_code' => true, 'show_activity_label' => false,
        ];

        return view('coding.show', compact('exercise', 'slug', 'config', 'completed'));
    }

    public function submit(SubmitCodingRequest $request, string $slug, CodingGraderService $grader): JsonResponse
    {
        $exercise = $this->catalog->find($slug);
        $record = Exercise::where('exercise_key', $exercise['key'])->first();
        if (! $record) {
            return response()->json(['message' => 'Latihan belum disiapkan di database. Hubungi pengelola.'], 503);
        }
        $files = $request->validated('files');
        if (strlen($files['main.py']) > 60000) {
            return response()->json(['message' => 'Kode terlalu panjang (maksimal 60 KB).'], 422);
        }
        try {
            $report = $grader->grade($exercise['key'], $files);
        } catch (RuntimeException) {
            return response()->json(['message' => 'Verifikasi resmi belum tersedia atau mengalami gangguan. Progres belum disimpan. Run Code dan pemeriksaan lokal tetap dapat digunakan.'], 503);
        }
        $submission = ExerciseSubmission::create([
            'exercise_id' => $record->id, 'user_id' => $request->user()->id,
            'code' => json_encode(['entry_file' => 'main.py', 'files' => $files], JSON_THROW_ON_ERROR),
            'output' => $report['output'], 'status' => $report['status'], 'score' => $report['score'], 'submitted_at' => now(),
        ]);
        $completed = $record->submissions()->where('user_id', $request->user()->id)->where('status', 'passed')->exists();

        return response()->json([
            'submission_id' => $submission->id, 'status' => $report['status'], 'score' => $report['score'],
            'checks' => $report['checks'], 'completed' => $completed,
            'message' => match ($report['status']) {
                'passed' => "Selamat! Jawaban kamu benar. Latihan {$exercise['number']} berhasil diselesaikan dan progres telah tersimpan.",
                'failed' => 'Jawaban belum tepat. Periksa kembali kode kamu dan coba lagi.',
                default => 'Program mengalami error atau melewati batas waktu. Perbaiki kode dan coba lagi. Latihan ini belum dinyatakan lulus.',
            },
        ]);
    }
}
