<?php

namespace Tests\Feature;

use App\View\Components\LiveCode;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Tests\TestCase;

class LiveCodeTest extends TestCase
{
    public function test_demo_renders_three_independent_components_and_loads_assets_once(): void
    {
        $response = $this->get('/editor')->assertOk();
        $html = $response->getContent();

        $this->assertSame(3, substr_count($html, 'data-live-code'));
        $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
        $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
        preg_match_all('/\sid="([^"]+)"/', $html, $matches);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));

        foreach (['demo-dasar', 'demo-objek', 'demo-pewarisan'] as $id) {
            $response->assertSee('id="'.$id.'-monaco-editor"', false);
        }
    }

    public function test_component_supports_custom_entry_nested_paths_and_more_than_four_files(): void
    {
        $files = array_fill_keys(['start.py', 'a.py', 'b.py', 'c.py', 'd.py', 'package/tool.py'], 'print("ok")');
        $config = ['id' => 'custom-exercise', 'files' => $files, 'entry_file' => 'start.py'];
        $html = Blade::render('<x-live-code :config="$config" />', compact('config'));

        $this->assertSame(6, substr_count($html, 'role="tab"'));
        $this->assertStringContainsString('Jalankan start.py', $html);
        $this->assertStringContainsString('data-file="package/tool.py"', $html);
    }

    public function test_config_is_embedded_without_allowing_script_injection(): void
    {
        $config = ['id' => 'safe-config', 'files' => ['main.py' => 'print("</script><script>alert(1)</script>")']];
        $html = Blade::render('<x-live-code :config="$config" />', compact('config'));

        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        preg_match('/data-role="config">(.*?)<\/script>/s', $html, $match);
        $this->assertSame($config['files'], json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['files']);
    }

    public function test_missing_entry_is_rejected_with_a_clear_configuration_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entry file');
        new LiveCode(['id' => 'missing-entry', 'files' => ['other.py' => '']]);
    }

    public function test_workspace_traversal_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LiveCode(['id' => 'invalid-path', 'files' => ['../main.py' => '']]);
    }

    public function test_chapter_two_renders_incomplete_exercises_with_assets_loaded_once(): void
    {
        $html = $this->get('/materi/kelas-dan-objek')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'data-live-code'));
        $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
        $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
        preg_match_all('/data-role="config">(.*?)<\/script>/s', $html, $matches);
        $configs = array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
        $this->assertSame(['bab2-spesies', 'bab2-sensor-air'], array_column($configs, 'id'));
        foreach ($configs as $config) {
            $this->assertSame('main.py', $config['entry_file']);
            $this->assertStringContainsString('pass', $config['files']['main.py']);
            $this->assertStringNotContainsString('return f"', $config['files']['main.py']);
            $this->assertNotEmpty($config['checker']);
            $this->assertStringContainsString('<h3 id="'.$config['id'].'-workspace-title"', $html);
            $this->assertStringContainsString('<h3 id="'.$config['id'].'-output-title"', $html);
        }
    }

    public function test_default_heading_level_is_preserved_for_the_editor_demo(): void
    {
        $html = $this->get('/editor')->assertOk()->getContent();
        $this->assertStringContainsString('<h2 id="demo-dasar-workspace-title"', $html);
        $this->assertStringContainsString('<h3 class="oopy-explorer-title"', $html);
    }

    public function test_chapter_one_uses_one_incomplete_status_air_exercise(): void
    {
        $html = $this->get('/materi/dasar-pemrograman-oop')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-live-code'));
        $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
        preg_match('/data-role="config">(.*?)<\/script>/s', $html, $matches);
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('bab1-status-air', $config['id']);
        $this->assertSame('Coba sendiri: Status Air', $config['title']);
        $this->assertSame(['main.py'], array_keys($config['files']));
        $this->assertSame(1, substr_count($config['files']['main.py'], 'pass'));
        $this->assertStringContainsString('print(status_air(120))', $config['files']['main.py']);
        $this->assertStringContainsString('results = []', $config['checker']);
        $this->assertStringContainsString('<h3 id="bab1-status-air-workspace-title"', $html);
    }

    public function test_chapter_three_reuses_live_code_with_an_incomplete_starter_and_checker(): void
    {
        $html = $this->get('/materi/enkapsulasi')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-live-code'));
        $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
        $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
        preg_match('/data-role="config">(.*?)<\/script>/s', $html, $matches);
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('bab3-enkapsulasi-sensor', $config['id']);
        $this->assertSame('main.py', $config['entry_file']);
        $this->assertSame(['main.py'], array_keys($config['files']));
        $starter = $config['files']['main.py'];
        $this->assertSame(3, substr_count($starter, 'pass'));
        $this->assertStringContainsString('@property', $starter);
        $this->assertStringContainsString('@tinggi_air.setter', $starter);
        $this->assertStringNotContainsString('return self.', $starter);
        $this->assertStringNotContainsString('raise ValueError(', $starter);
        $this->assertNotEmpty($config['checker']);
        $this->assertStringContainsString('results = []', $config['checker']);
        $this->assertStringContainsString('<h3 id="'.$config['id'].'-workspace-title"', $html);
        $this->assertStringContainsString('<h3 id="'.$config['id'].'-output-title"', $html);
        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        $this->assertSame($ids[1], array_values(array_unique($ids[1])));
    }

    public function test_chapters_four_and_five_embed_their_registered_exercise_contracts(): void
    {
        foreach (['pewarisan' => 'bab4-pewarisan-ekosistem', 'polimorfisme' => 'bab5-polimorfisme-sensor'] as $slug => $id) {
            $chapter = require resource_path('materi/'.$slug.'.php');
            $expected = array_merge(...array_column(array_filter($chapter['sections'], fn ($section) => isset($section['live_codes'])), 'live_codes'));
            $html = $this->get('/materi/'.$slug)->assertOk()->getContent();
            preg_match_all('/data-role="config">(.*?)<\/script>/s', $html, $matches);
            $this->assertCount(1, $matches[1]);
            $config = json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($id, $config['id']);
            $this->assertSame('main.py', $config['entry_file']);
            $this->assertSame(['main.py'], array_keys($config['files']));
            $this->assertSame($expected[0]['files'], $config['files']);
            $this->assertSame($expected[0]['checker'], $config['checker']);
            $this->assertStringContainsString('pass', $config['files']['main.py']);
            $this->assertStringContainsString('results = []', $config['checker']);
            $this->assertSame(1, substr_count($html, 'data-live-code'));
            $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
            $this->assertSame(1, substr_count($html, 'css/oopy/live-code/live-code.css'));
            $this->assertStringContainsString('<h3 id="'.$id.'-workspace-title"', $html);
            preg_match_all('/\sid="([^"]+)"/', $html, $ids);
            $this->assertSame($ids[1], array_values(array_unique($ids[1])));
        }
    }

    public function test_material_activity_headers_render_one_task_before_the_editor(): void
    {
        foreach (['dasar-pemrograman-oop' => 1, 'kelas-dan-objek' => 2, 'enkapsulasi' => 1, 'pewarisan' => 1, 'polimorfisme' => 1] as $slug => $count) {
            $html = $this->get('/materi/'.$slug)->assertOk()->getContent();
            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($dom);
            $headers = $xpath->query('//header[@class="oopy-activity-header"]');
            $this->assertSame($count, $headers->length);
            foreach ($headers as $header) {
                $this->assertSame(1, $xpath->query('.//*[@class="oopy-activity-title"]', $header)->length);
                $this->assertSame(1, $xpath->query('.//*[@class="oopy-live-task"]', $header)->length);
                $this->assertSame(1, $xpath->query('.//*[@class="oopy-live-task-description"]', $header)->length);
                $this->assertGreaterThanOrEqual(2, $xpath->query('.//*[@class="oopy-live-task"]//code', $header)->length);
                $this->assertSame(0, $xpath->query('.//ol', $header)->length);
                $this->assertSame(1, $xpath->query('following-sibling::div[@class="oopy-workspace-body"]', $header)->length);
                $this->assertSame(0, $xpath->query('.//*[@tabindex]', $header)->length);
            }
            foreach (['run-code', 'reset-code', 'check-code'] as $role) {
                $this->assertSame($count, $xpath->query('//button[@data-role="'.$role.'"]')->length);
            }
            $this->assertSame(1, substr_count($html, 'js/live-code/live-code.js'));
        }
    }

    public function test_instruction_tokens_are_escaped_without_rendering_html(): void
    {
        $config = [
            'id' => 'safe-instructions',
            'title' => '<b>Judul latihan</b>',
            'description' => 'Ubah `<img src=x onerror=alert(1)>` menjadi `"<script>alert(2)</script>"`. <strong>Catatan</strong> dan `sisa.',
            'files' => ['main.py' => ''],
        ];
        $html = Blade::render('<x-live-code :config="$config" />', compact('config'));
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        $header = $xpath->query('//header')->item(0);
        $this->assertSame(0, $xpath->query('.//img | .//script | .//b', $header)->length);
        $this->assertSame(['<img src=x onerror=alert(1)>', '"<script>alert(2)</script>"'], array_map(fn ($node) => $node->textContent, iterator_to_array($xpath->query('.//code', $header))));
        $this->assertSame('Ubah <img src=x onerror=alert(1)> menjadi "<script>alert(2)</script>". <strong>Catatan</strong> dan `sisa.', $xpath->query('.//*[@class="oopy-live-task-description"]', $header)->item(0)->textContent);
        $this->assertSame('<b>Judul latihan</b>', $xpath->query('.//*[@data-role="workspace-title"]', $header)->item(0)->textContent);
    }

    public function test_legacy_config_without_description_still_renders(): void
    {
        $config = ['id' => 'legacy-exercise', 'files' => ['main.py' => '']];
        $html = Blade::render('<x-live-code :config="$config" />', compact('config'));
        $this->assertStringContainsString('AKTIVITAS LIVE CODING', $html);
        $this->assertStringContainsString('class="oopy-activity-title">Live Coding', $html);
        $this->assertStringNotContainsString('class="oopy-live-task"', $html);
        $this->assertStringNotContainsString('Langkah pengerjaan', $html);
    }
}
