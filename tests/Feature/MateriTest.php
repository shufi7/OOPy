<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class MateriTest extends TestCase
{
    public function test_material_list_links_only_to_the_available_chapters(): void
    {
        $response = $this->get('/materi')->assertOk();
        $response->assertSee(route('materi.show', 'dasar-pemrograman-oop'));
        $response->assertSee(route('materi.show', 'kelas-dan-objek'));
        $response->assertSee(route('materi.show', 'enkapsulasi'))->assertSee('Pelajari BAB 3');
        $this->get('/materi/kelas-dan-objek')->assertOk();
        $this->get('/materi/enkapsulasi')->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'Segera hadir'));

        foreach (['pewarisan', 'polimorfisme', 'kelas-abstrak'] as $slug) {
            $response->assertDontSee(route('materi.show', $slug));
            $this->get('/materi/'.$slug)->assertNotFound();
        }
    }

    public function test_chapter_has_breadcrumb_sections_and_safe_navigation(): void
    {
        $response = $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        $response->assertSee('Dasar Pemrograman Python &amp; OOP', false);
        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSee(route('home'))->assertSee(route('materi.index'));

        foreach (['tujuan', 'python', 'variabel', 'tipe-data', 'input-output', 'operator', 'percabangan', 'perulangan', 'fungsi', 'oop', 'rangkuman', 'kuis'] as $id) {
            $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
        }

        $response->assertDontSee('Latihan BAB')->assertSee('Kuis BAB 1');
        $response->assertDontSee('id="latihan"', false)->assertDontSee('href="#latihan"', false);
        $response->assertSee('Instruksi Pengerjaan')->assertSee('Coba Lagi');
        $response->assertSee(route('materi.show', 'kelas-dan-objek'));
        preg_match_all('/\sid="([^"]+)"/', $response->getContent(), $matches);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function test_chapter_embeds_one_existing_live_code_component(): void
    {
        $html = $this->get('/materi/dasar-pemrograman-oop')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'data-live-code'));
        $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
        $this->assertSame(1, substr_count($html, 'css/oopy-live-code.css'));
        preg_match('/data-role="config">(.*?)<\/script>/s', $html, $matches);
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('bab1-variabel', $config['id']);
        $this->assertSame('main.py', $config['entry_file']);
        $this->assertStringContainsString('assert nama_ekosistem', $config['checker']);
    }

    public function test_unknown_chapter_is_not_found(): void
    {
        $this->get('/materi/tidak-ada')->assertNotFound();
        $this->get('/materi/dasar-pemrograman-oop.php')->assertNotFound();
    }

    public function test_available_chapters_share_the_template_and_have_one_dynamic_footer_navigation(): void
    {
        $chapters = require resource_path('materi/chapters.php');
        $neighbors = [
            'dasar-pemrograman-oop' => [null, 'kelas-dan-objek'],
            'kelas-dan-objek' => ['dasar-pemrograman-oop', 'enkapsulasi'],
            'enkapsulasi' => ['kelas-dan-objek', null],
        ];
        foreach ($neighbors as $slug => [$previous, $next]) {
            $content = require resource_path('materi/'.$chapters[$slug]['content']);
            $response = $this->get('/materi/'.$slug)->assertOk()->assertViewIs('materi.show');
            $response->assertDontSee('Materi BAB berikutnya segera hadir.')
                ->assertDontSee('material-bottom-nav')
                ->assertDontSee('Progres belajar belum dicatat atau disimpan.')
                ->assertSee('Kuis '.$chapters[$slug]['bab']);
            $html = $response->getContent();
            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($dom);
            $navigation = '//nav[@class="material-navigation"]';
            $this->assertSame(1, $xpath->query($navigation)->length);
            $this->assertSame('Navigasi antar BAB', $xpath->query($navigation)->item(0)->getAttribute('aria-label'));
            $this->assertSame(1, $xpath->query($navigation.'/a[@href="'.route('materi.index').'"]')->length);
            $this->assertSame($previous ? 1 : 0, $xpath->query($navigation.'/a[@rel="prev"]')->length);
            $this->assertSame($next ? 1 : 0, $xpath->query($navigation.'/a[@rel="next"]')->length);
            foreach (['prev' => $previous, 'next' => $next] as $relation => $target) {
                if ($target) {
                    $this->assertSame(route('materi.show', $target), $xpath->query($navigation.'/a[@rel="'.$relation.'"]')->item(0)->getAttribute('href'));
                }
            }
            $response->assertDontSee(route('materi.show', 'pewarisan'));

            $expectedIds = ['tujuan', ...array_column($content['sections'], 'id'), 'rangkuman'];
            if (! empty($content['reflection'])) {
                $expectedIds[] = 'refleksi';
            }
            $expectedIds[] = 'kuis';
            $this->assertSame($expectedIds, array_map(fn ($node) => $node->getAttribute('id'), iterator_to_array($xpath->query('//*[@data-material-section]'))));
            $this->assertSame($expectedIds, array_map(fn ($node) => substr($node->getAttribute('href'), 1), iterator_to_array($xpath->query('//*[@id="chapter-navigation"]//nav//a'))));
            preg_match_all('/\sid="([^"]+)"/', $html, $ids);
            $this->assertSame($ids[1], array_values(array_unique($ids[1])));
            $response->assertSee('js/vendor/prism/prism.min.js')->assertSee('js/oopy-syntax.js');
        }
    }

    public function test_template_omits_empty_optional_content_and_keeps_navigation_safe(): void
    {
        foreach ([[], ['reflection' => []], ['reflection' => ['Apa yang dipelajari?']]] as $optional) {
            $html = view('materi.show', [
                'chapter' => ['bab' => 'BAB Contoh', 'judul' => 'Template materi'],
                'content' => $optional + ['sections' => [['id' => 'konsep', 'title' => 'Konsep']]],
                'previousChapter' => null,
                'nextChapter' => null,
            ])->render();
            $this->assertStringContainsString('id="konsep"', $html);
            foreach (['rangkuman', 'kuis'] as $id) {
                $this->assertStringNotContainsString('id="'.$id.'"', $html);
                $this->assertStringNotContainsString('href="#'.$id.'"', $html);
            }
            $this->assertSame(! empty($optional['reflection']), str_contains($html, 'href="#refleksi"'));
            $this->assertSame(! empty($optional['reflection']), str_contains($html, 'id="refleksi"'));
            $this->assertStringContainsString('Kembali ke Daftar Materi', $html);
        }
    }

    public function test_middle_chapter_can_render_previous_and_next_links(): void
    {
        $chapters = require resource_path('materi/chapters.php');
        $html = view('materi.show', [
            'chapter' => ['bab' => 'BAB Contoh', 'judul' => 'Template BAB tengah'],
            'content' => [],
            'previousChapter' => $chapters['dasar-pemrograman-oop'] + ['slug' => 'dasar-pemrograman-oop'],
            'nextChapter' => $chapters['kelas-dan-objek'] + ['slug' => 'kelas-dan-objek'],
        ])->render();

        $this->assertSame(1, substr_count($html, 'rel="prev"'));
        $this->assertSame(1, substr_count($html, 'rel="next"'));
        $this->assertSame(1, substr_count($html, 'class="material-navigation"'));
    }

    public function test_all_available_quizzes_embed_questions_with_valid_answers(): void
    {
        foreach (['dasar-pemrograman-oop' => 5, 'kelas-dan-objek' => 8, 'enkapsulasi' => 8] as $slug => $count) {
            $html = $this->get('/materi/'.$slug)->assertOk()->getContent();
            preg_match('/data-quiz="questions">(.*?)<\/script>/s', $html, $matches);
            $questions = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

            $this->assertCount($count, $questions);
            foreach ($questions as $question) {
                $this->assertCount(4, $question['options']);
                $this->assertIsInt($question['correct']);
                $this->assertArrayHasKey($question['correct'], $question['options']);
                $this->assertNotEmpty($question['explanation']);
            }
            $this->assertStringContainsString('js/oopy-quiz.js', $html);
        }
    }

    public function test_encapsulation_chapter_has_objectives_reflection_and_code_questions(): void
    {
        $response = $this->get('/materi/enkapsulasi')->assertOk()->assertViewIs('materi.show');
        $content = $response->viewData('content');
        $this->assertCount(5, $content['objectives']);
        $this->assertCount(9, $content['sections']);
        $this->assertCount(7, $content['summary']);
        $this->assertCount(3, $content['reflection']);
        foreach ($content['reflection'] as $question) {
            $response->assertSee($question);
        }
        $this->assertGreaterThanOrEqual(3, count(array_filter($content['quiz'], fn ($question) => ! empty($question['code']))));
        $response->assertSee('Bedah Kode SensorAir')->assertSee('name mangling')
            ->assertSee('non-public by convention')->assertSee('@property')
            ->assertSee('bukan data hasil pengukuran lapangan');
        $this->assertNull($response->viewData('nextChapter'));
        $this->assertSame('kelas-dan-objek', $response->viewData('previousChapter')['slug']);
    }

    public function test_home_and_editor_remain_available(): void
    {
        $this->get('/')->assertOk()->assertSee(route('materi.index'));
        $this->get('/editor')->assertOk()->assertSee('Live Coding Component Demo');
    }
}
