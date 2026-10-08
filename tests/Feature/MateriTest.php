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
        foreach (['pewarisan', 'polimorfisme', 'kelas-abstrak', 'evaluasi-akhir'] as $slug) {
            $response->assertSee(route('materi.show', $slug));
            $this->get('/materi/'.$slug)->assertOk();
        }
        $this->assertSame(7, substr_count($response->getContent(), '<span>Pelajari BAB'));
        $this->assertSame(0, substr_count($response->getContent(), 'Segera hadir'));
    }

    public function test_chapter_has_breadcrumb_sections_and_safe_navigation(): void
    {
        $response = $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        $response->assertSee('Dasar Pemrograman Python dan OOP');
        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSee(route('home'))->assertSee(route('materi.index'));

        foreach (['tujuan', 'apersepsi', 'nilai-tipe-data-variabel', 'operator-ekspresi', 'input-output', 'percabangan', 'perulangan-list', 'fungsi', 'prosedural-ke-oop', 'rangkuman', 'refleksi', 'kuis'] as $id) {
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
        $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
        preg_match('/data-role="config">(.*?)<\/script>/s', $html, $matches);
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('bab1-status-air', $config['id']);
        $this->assertSame('main.py', $config['entry_file']);
        $this->assertStringContainsString('def status_air(tinggi):', $config['files']['main.py']);
        $this->assertStringContainsString('pass', $config['files']['main.py']);
        $this->assertNotEmpty($config['checker']);
    }

    public function test_unknown_chapter_is_not_found(): void
    {
        $this->get('/materi/tidak-ada')->assertNotFound();
        $this->get('/materi/dasar-pemrograman-oop.php')->assertNotFound();
        $this->get('/materi/kelas-abstrak.php')->assertNotFound();
    }

    public function test_available_chapters_share_the_template_and_have_one_dynamic_footer_navigation(): void
    {
        $chapters = require resource_path('materi/chapters.php');
        $neighbors = [
            'dasar-pemrograman-oop' => [null, 'kelas-dan-objek'],
            'kelas-dan-objek' => ['dasar-pemrograman-oop', 'enkapsulasi'],
            'enkapsulasi' => ['kelas-dan-objek', 'pewarisan'],
            'pewarisan' => ['enkapsulasi', 'polimorfisme'],
            'polimorfisme' => ['pewarisan', 'kelas-abstrak'],
            'kelas-abstrak' => ['polimorfisme', 'evaluasi-akhir'],
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
            $this->assertSame($next ? 1 : 0, $xpath->query($navigation.'//*[@data-quiz-next-locked]')->length);
            $this->assertSame($next ? 1 : 0, $xpath->query($navigation.'/a[@rel="next" and @hidden and @data-quiz-next-link]')->length);
            foreach (['prev' => $previous, 'next' => $next] as $relation => $target) {
                if ($target) {
                    $this->assertSame(route('materi.show', $target), $xpath->query($navigation.'/a[@rel="'.$relation.'"]')->item(0)->getAttribute('href'));
                }
            }
            $response->assertDontSee(route('materi.show', 'bab-7'));
            $response->assertSee($chapters[$slug]['judul']);
            foreach ([...$content['objectives'], ...$content['summary'], ...$content['reflection']] as $text) {
                $response->assertSee($text);
            }
            $this->assertSame($previous, $response->viewData('previousChapter')['slug'] ?? null);
            $this->assertSame($next, $response->viewData('nextChapter')['slug'] ?? null);

            $expectedIds = ['tujuan', ...array_column($content['sections'], 'id'), 'rangkuman'];
            if (! empty($content['reflection'])) {
                $expectedIds[] = 'refleksi';
            }
            $expectedIds[] = 'kuis';
            $this->assertSame($expectedIds, array_map(fn ($node) => $node->getAttribute('id'), iterator_to_array($xpath->query('//*[@data-material-section]'))));
            $this->assertSame($expectedIds, array_map(fn ($node) => substr($node->getAttribute('href'), 1), iterator_to_array($xpath->query('//*[@id="chapter-navigation"]//nav//a'))));
            $groups = '//*[@id="chapter-navigation"]//details';
            $mainGroup = $xpath->query($groups.'[@data-toc-group="materi"]')->item(0);
            $closingGroup = $xpath->query($groups.'[@data-toc-group="penutup"]')->item(0);
            $this->assertSame(2, $xpath->query($groups)->length);
            $this->assertFalse($mainGroup->hasAttribute('open'));
            $this->assertFalse($closingGroup->hasAttribute('open'));
            $this->assertSame('Materi '.$chapters[$slug]['bab'], trim($mainGroup->getElementsByTagName('summary')->item(0)->textContent));
            $this->assertSame(['#rangkuman', '#refleksi'], array_map(fn ($node) => $node->getAttribute('href'), iterator_to_array($closingGroup->getElementsByTagName('a'))));
            $this->assertSame(1, $xpath->query('//*[@class="material-toc-introduction"]//a[@href="#apersepsi"]')->length);
            foreach ($content['sections'] as $section) {
                $link = $xpath->query('//*[@id="chapter-navigation"]//a[@href="#'.$section['id'].'"]')->item(0);
                $heading = $xpath->query('//*[@id="'.$section['id'].'"]/h2')->item(0);
                $this->assertSame($section['nav_title'] ?? $section['title'], trim($link->textContent));
                $this->assertSame($section['title'], trim($heading->textContent));
            }
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
            $this->assertStringNotContainsString('material-toc-introduction', $html);
            $this->assertSame(! empty($optional['reflection']), str_contains($html, 'data-toc-group="penutup"'));
            $this->assertStringContainsString('href="#konsep">Konsep</a>', $html);
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
        $answers = [
            'dasar-pemrograman-oop' => ['return', 'elif'],
            'kelas-dan-objek' => ['self.nama = nama', 'Ekosistem'],
            'enkapsulasi' => ['@property', '@tinggi_air.setter'],
            'pewarisan' => ['Ekosistem', 'super().__init__(nama, lokasi)'],
            'polimorfisme' => ['status', 'info'],
            'kelas-abstrak' => ['@abstractmethod', 'abc'],
        ];
        foreach ($answers as $slug => $codeAnswers) {
            $response = $this->get('/materi/'.$slug)->assertOk();
            $html = $response->getContent();
            preg_match('/data-quiz="questions">(.*?)<\/script>/s', $html, $matches);
            $questions = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

            $this->assertCount(5, $questions);
            $this->assertSame(['multiple_choice', 'multiple_choice', 'multiple_choice', 'code_fill', 'code_fill'], array_column($questions, 'type'));
            $this->assertSame($codeAnswers, array_column(array_slice($questions, 3), 'answer'));
            foreach ($questions as $index => $question) {
                if (($question['type'] ?? 'multiple_choice') === 'code_fill') {
                    $this->assertContains($question['answer'], $codeAnswers);
                    $this->assertNotEmpty($question['code']);
                    $this->assertArrayNotHasKey('options', $question);
                } else {
                    $this->assertCount(4, $question['options']);
                    $this->assertIsInt($question['correct']);
                    $this->assertArrayHasKey($question['correct'], $question['options']);
                }
                $this->assertArrayNotHasKey('explanation', $question);
                $this->assertNotEmpty($response->viewData('content')['quiz'][$index]['explanation']);
            }
            $this->assertStringContainsString('js/oopy-quiz.js', $html);
            $this->assertStringContainsString('data-chapter-slug="'.$slug.'"', $html);
            $this->assertStringContainsString('data-quiz="results" hidden', $html);
            $this->assertStringContainsString('role="status" aria-live="polite" aria-atomic="true"', $html);
            foreach (['score', 'correct', 'incorrect', 'status', 'message', 'retry'] as $role) {
                $this->assertStringContainsString('data-quiz="'.$role.'"', $html);
            }
            foreach (['oopy-quiz-review', 'data-quiz="review"', 'Pembahasan', 'Jawaban kamu:', 'Jawaban benar:', 'Penjelasan:'] as $removed) {
                $this->assertStringNotContainsString($removed, $html);
            }
            if ($response->viewData('nextChapter')) {
                $this->assertStringContainsString('data-quiz="continue" hidden', $html);
            } else {
                $this->assertStringNotContainsString('data-quiz="continue"', $html);
                $this->assertStringNotContainsString('data-quiz-next-link', $html);
            }
        }
    }

    public function test_chapter_one_follows_the_new_module_with_tables_practice_and_reflection(): void
    {
        $response = $this->get('/materi/dasar-pemrograman-oop')->assertOk()->assertViewIs('materi.show');
        $content = $response->viewData('content');
        $this->assertSame('Fondasi singkat yang dibutuhkan sebelum memasuki pemodelan object.', $content['description']);
        $this->assertCount(5, $content['objectives']);
        $this->assertCount(8, $content['sections']);
        $this->assertCount(4, $content['summary']);
        $this->assertCount(3, $content['reflection']);
        foreach ([...$content['objectives'], ...$content['reflection']] as $text) {
            $response->assertSee($text);
        }
        $sections = array_column($content['sections'], null, 'id');
        $this->assertArrayNotHasKey('code', $sections['apersepsi']);
        $this->assertCount(4, $sections['nilai-tipe-data-variabel']['tables'][0]['rows']);
        $this->assertCount(3, $sections['operator-ekspresi']['tables'][0]['rows']);
        $this->assertCount(5, $sections['prosedural-ke-oop']['tables'][0]['rows']);
        $this->assertCount(3, $sections['prosedural-ke-oop']['practice']);
        $this->assertSame(['multiple_choice', 'multiple_choice', 'multiple_choice', 'code_fill', 'code_fill'], array_column($content['quiz'], 'type'));
        $this->assertSame(['return', 'elif'], array_column(array_slice($content['quiz'], 3), 'answer'));
        $response->assertSee('Ayo Berlatih')->assertSee('klasifikasi_suhu(suhu)')->assertSee('rata_rata(a, b, c)');
        $response->assertSee('&lt;class &#039;str&#039;&gt;', false);
        $response->assertDontSee('bab1-variabel')->assertDontSee('id="oop"', false);
    }

    public function test_structured_section_extensions_escape_content_and_preserve_code(): void
    {
        $html = view('materi.partials.section', [
            'number' => 1,
            'section' => [
                'id' => 'contoh', 'title' => 'Contoh',
                'tables' => [['caption' => '<script>alert(1)</script>', 'headers' => ['Jenis'], 'rows' => [[['code' => '<img src=x onerror=alert(2)>']]]]],
                'output' => '<class \'str\'>',
                'practice' => ['<b>Latihan</b>'],
            ],
        ])->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;class &#039;str&#039;&gt;', $html);
        $this->assertStringContainsString('scope="col"', $html);
        $this->assertStringContainsString('material-output', $html);
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
        $this->assertCount(2, array_filter($content['quiz'], fn ($question) => ! empty($question['code'])));
        $response->assertSee('Bedah Kode SensorAir')->assertSee('name mangling')
            ->assertSee('non-public by convention')->assertSee('@property')
            ->assertSee('bukan data hasil pengukuran lapangan');
        $this->assertSame('pewarisan', $response->viewData('nextChapter')['slug']);
        $this->assertSame('kelas-dan-objek', $response->viewData('previousChapter')['slug']);
    }

    public function test_abstract_chapter_matches_the_learning_contract_and_renders_examples(): void
    {
        $response = $this->get('/materi/kelas-abstrak')->assertOk()->assertViewIs('materi.show');
        $content = $response->viewData('content');
        $this->assertSame('Menyatakan kontrak perilaku minimum ketika desain memerlukannya.', $content['description']);
        $this->assertSame([
            'Menjelaskan perbedaan class konkret dan abstract base class.',
            'Menggunakan ABC dan abstractmethod dari modul abc.',
            'Membuat subclass konkret yang memenuhi abstract method.',
            'Menggabungkan ABC dengan inheritance dan polimorfisme.',
            'Menjelaskan bahwa ABC bersifat pilihan desain dalam Python, bukan syarat untuk semua polimorfisme.',
        ], $content['objectives']);
        $this->assertSame(['apersepsi', 'membuat-abstract-base-class', 'abstract-method-method-konkret', 'kapan-abc-digunakan', 'ayo-coba-kelas-abstrak', 'ayo-berlatih-kelas-abstrak'], array_column($content['sections'], 'id'));
        $this->assertCount(5, $content['summary']);
        $this->assertCount(3, $content['reflection']);
        $sections = array_column($content['sections'], null, 'id');
        $this->assertCount(3, $sections['ayo-berlatih-kelas-abstrak']['practice']);
        $this->assertCount(9, $sections['ayo-coba-kelas-abstrak']['instructions']);
        $this->assertCount(2, $sections['ayo-coba-kelas-abstrak']['exploration']);
        $this->assertSame(['SensorPH', 'SensorSuhu'], array_column($sections['membuat-abstract-base-class']['hierarchy']['children'], 'label'));
        $this->assertSame("OOPy\n29.5", $sections['abstract-method-method-konkret']['output']);
        $this->assertSame([1, 3, 1], array_column(array_slice($content['quiz'], 0, 3), 'correct'));
        foreach (['Gambar 6.1', 'Bedah Kode', 'TypeError: SensorBelumLengkap', 'Abstract method dan method konkret', 'Class Biasa vs Abstract Base Class', 'Coba sendiri: Kontrak AlatPantau'] as $text) {
            $response->assertSee($text);
        }
        $this->assertSame('polimorfisme', $response->viewData('previousChapter')['slug']);
        $this->assertSame('evaluasi-akhir', $response->viewData('nextChapter')['slug']);
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@id="ayo-coba-kelas-abstrak"]/*[@class="material-practice"]/following-sibling::*[@data-live-code]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="ayo-coba-kelas-abstrak"]/*[@data-live-code]/following-sibling::aside[@aria-label="Eksplorasi setelah latihan"]')->length);
    }

    public function test_extended_section_examples_and_hierarchy_escape_developer_content(): void
    {
        $html = view('materi.partials.section', [
            'number' => 1,
            'section' => [
                'id' => 'kontrak', 'title' => 'Kontrak', 'breakdown' => ['<b>Uraian</b>'],
                'hierarchy' => ['caption' => '<img src=x>', 'label' => 'Induk', 'contract' => '<script>', 'children' => [['label' => 'Anak', 'contract' => '<b>Method</b>']]],
                'examples' => [['title' => '<img src=x>', 'paragraphs' => ['<script>'], 'code' => '<script>', 'output' => '<b>Output</b>']],
                'instructions' => ['<script>'], 'exploration' => ['<img src=x>'],
            ],
        ])->render();
        foreach (['<img ', '<script>', '<b>'] as $markup) {
            $this->assertStringNotContainsString($markup, $html);
        }
        $this->assertStringContainsString('aria-labelledby="kontrak-hierarchy-caption"', $html);
        $this->assertStringContainsString('id="kontrak-example-0-title"', $html);
    }

    public function test_home_and_editor_remain_available(): void
    {
        $this->get('/')->assertOk()->assertSee(route('materi.index'));
        $this->get('/editor')->assertOk()->assertSee('Live Coding Component Demo');
    }
}
