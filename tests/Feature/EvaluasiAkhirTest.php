<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class EvaluasiAkhirTest extends TestCase
{
    public function test_evaluation_routes_render_independent_pages_with_safe_assets_and_links(): void
    {
        foreach (['index' => 'intro', 'exam' => 'exam', 'results' => 'results'] as $name => $page) {
            $response = $this->get(route('evaluasi.'.$name))->assertOk();
            $html = $response->getContent();
            $response->assertSee('data-exam-page="'.$page.'"', false)
                ->assertSee(route('materi.show', 'kelas-abstrak'))
                ->assertSee(route('materi.index'))
                ->assertSee('js/evaluasi/exam.js')
                ->assertDontSee('js/oopy-quiz.js')
                ->assertDontSee('js/oopy-material.js');
            $this->assertSame(1, substr_count($html, 'css/oopy/evaluasi/evaluasi.css'));
            $this->assertSame(0, substr_count($html, 'data-live-code'));
            $response->assertDontSee('Mini Project')->assertDontSee('mini-project');
            preg_match_all('/\sid="([^"]+)"/', $html, $ids);
            $this->assertSame($ids[1], array_values(array_unique($ids[1])));
            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($dom);
            $this->assertSame(0, $xpath->query('//a[@rel="next"]')->length);
        }
        $this->get('/materi/evaluasi-akhir/tidak-ada')->assertNotFound();
        $this->get('/materi/evaluasi-akhir.php')->assertNotFound();
    }

    public function test_twenty_questions_match_the_supplied_evaluation_bank(): void
    {
        $content = require resource_path('materi/evaluasi-akhir.php');
        $questions = $content['questions'];
        $this->assertCount(20, $questions);
        $this->assertSame(array_fill(0, 10, 'multiple_choice'), array_column(array_slice($questions, 0, 10), 'type'));
        $this->assertSame(array_fill(0, 5, 'code_fill'), array_column(array_slice($questions, 10, 5), 'type'));
        $this->assertSame(array_fill(0, 5, 'essay'), array_column(array_slice($questions, 15), 'type'));
        $this->assertSame([2, 3, 0, 2, 0, 2, 3, 0, 1, 3], array_column(array_slice($questions, 0, 10), 'correct'));
        $this->assertSame(['self.nilai = nilai', 'return self._ph', 'super().__init__(nama, lokasi)', 'baca_data', '@abstractmethod'], array_column(array_slice($questions, 10, 5), 'answer'));
        $ids = array_column($questions, 'id');
        $this->assertSame($ids, array_values(array_unique($ids)));
        foreach ($questions as $question) {
            $this->assertNotEmpty($question['question']);
            if ($question['type'] === 'multiple_choice') {
                $this->assertCount(4, $question['options']);
                $this->assertArrayHasKey($question['correct'], $question['options']);
            } elseif ($question['type'] === 'code_fill') {
                $this->assertNotEmpty($question['code']);
                $this->assertNotEmpty($question['answer']);
            } else {
                $this->assertArrayNotHasKey('answer', $question);
                $this->assertArrayNotHasKey('correct', $question);
            }
        }
        $this->get(route('evaluasi.exam'))->assertOk()->assertSee('Navigasi Soal')->assertSee('Jawaban uraian');
    }

    public function test_intro_uses_central_settings_and_never_seeds_fake_history(): void
    {
        config(['evaluasi.duration_seconds' => 1800, 'evaluasi.pass_threshold' => 75]);
        $response = $this->get(route('evaluasi.index'))->assertOk();
        $response->assertSee('30 menit')->assertSee('75')->assertSee('langsung diulang')
            ->assertSee('Belum ada riwayat evaluasi.')->assertSee('bukan pengamanan ujian resmi');
        preg_match('/data-exam="config">(.*?)<\/script>/s', $response->getContent(), $matches);
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1800, $config['settings']['duration_seconds']);
        $this->assertSame(route('evaluasi.exam'), $config['urls']['exam']);
        $this->assertCount(20, $config['questions']);
        $this->assertStringNotContainsString('<tbody data-exam="history"><tr>', $response->getContent());
        $this->get(route('evaluasi.exam'))->assertOk()->assertSee('30:00');
    }

    public function test_removed_mini_project_is_unavailable_and_code_answer_is_inside_the_snippet(): void
    {
        $this->get('/materi/evaluasi-akhir/mini-project')->assertNotFound();
        $content = require resource_path('materi/evaluasi-akhir.php');
        $this->assertArrayNotHasKey('project', $content);
        $this->assertArrayNotHasKey('cooldown_seconds', config('evaluasi'));
        $html = $this->get(route('evaluasi.exam'))->assertOk()->getContent();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//pre/code/input[@id="code-answer"]')->length);
        $this->assertSame(1, $xpath->query('//input[@data-exam="code-answer"]')->length);
        $this->assertSame(2, $xpath->query('//pre/code/span[contains(@class,"language-python")]')->length);
    }
}
