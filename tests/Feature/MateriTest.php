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
        $this->get('/materi/kelas-dan-objek')->assertOk();
        $this->assertSame(4, substr_count($response->getContent(), 'Segera hadir'));

        foreach (['enkapsulasi', 'pewarisan', 'polimorfisme', 'kelas-abstrak'] as $slug) {
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

    public function test_both_chapters_share_the_template_and_have_one_dynamic_footer_navigation(): void
    {
        $chapters = require resource_path('materi/chapters.php');
        foreach (['dasar-pemrograman-oop', 'kelas-dan-objek'] as $slug) {
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
            $isFirst = $slug === 'dasar-pemrograman-oop';
            $this->assertSame($isFirst ? 0 : 1, $xpath->query($navigation.'/a[@rel="prev"]')->length);
            $this->assertSame($isFirst ? 1 : 0, $xpath->query($navigation.'/a[@rel="next"]')->length);
            $target = $isFirst ? 'kelas-dan-objek' : 'dasar-pemrograman-oop';
            $this->assertSame(1, $xpath->query($navigation.'/a[@href="'.route('materi.show', $target).'"]')->length);

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

    public function test_both_quizzes_embed_questions_with_valid_answers(): void
    {
        foreach (['dasar-pemrograman-oop' => 5, 'kelas-dan-objek' => 8] as $slug => $count) {
            $html = $this->get('/materi/'.$slug)->assertOk()->getContent();
            preg_match('/data-quiz="questions">(.*?)<\/script>/s', $html, $matches);
            $questions = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

            $this->assertCount($count, $questions);
            foreach ($questions as $question) {
                $this->assertCount(4, $question['options']);
                $this->assertArrayHasKey($question['correct'], $question['options']);
                $this->assertNotEmpty($question['explanation']);
            }
            $this->assertStringContainsString('js/oopy-quiz.js', $html);
        }
    }

    public function test_home_and_editor_remain_available(): void
    {
        $this->get('/')->assertOk()->assertSee(route('materi.index'));
        $this->get('/editor')->assertOk()->assertSee('Live Coding Component Demo');
    }
}
