<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class EvaluasiAkhirController extends Controller
{
    public function index(): View
    {
        return $this->page('index', 'intro');
    }

    public function exam(): View
    {
        return $this->page('ujian', 'exam');
    }

    public function results(): View
    {
        return $this->page('hasil', 'results');
    }

    private function page(string $view, string $page): View
    {
        $content = require resource_path('materi/evaluasi-akhir.php');
        $settings = config('evaluasi');
        $counts = array_count_values(array_column($content['questions'], 'type'));
        $config = [
            'settings' => $settings,
            // Only public fields cross into HTML; reusable bank keys stay on the server.
            'questions' => array_map(fn ($q) => array_intersect_key($q, array_flip(['id', 'type', 'question', 'options', 'code'])), $content['questions']),
            'official' => (bool) auth()->user(),
            'urls' => [
                'intro' => route('evaluasi.index'),
                'exam' => route('evaluasi.exam'),
                'results' => route('evaluasi.results'),
                'state' => route('evaluasi.attempts.state'),
                'start' => route('evaluasi.attempts.start'),
            ],
        ];

        return view('evaluasi.'.$view, compact('page', 'content', 'settings', 'config', 'counts'));
    }
}
