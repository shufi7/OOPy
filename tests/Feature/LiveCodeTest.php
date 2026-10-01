<?php

namespace Tests\Feature;

use App\View\Components\LiveCode;
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
        $this->assertSame(1, substr_count($html, 'css/oopy-live-code.css'));
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
        $this->assertSame(1, substr_count($html, 'css/oopy-live-code.css'));
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
}
