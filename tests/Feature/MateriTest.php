<?php

namespace Tests\Feature;

use Tests\TestCase;

class MateriTest extends TestCase
{
    public function test_material_list_links_only_to_the_available_chapter(): void
    {
        $response = $this->get('/materi')->assertOk();
        $response->assertSee(route('materi.show', 'dasar-pemrograman-oop'));
        $this->assertSame(5, substr_count($response->getContent(), 'Segera hadir'));

        foreach (['kelas-dan-objek', 'enkapsulasi', 'pewarisan', 'polimorfisme', 'kelas-abstrak'] as $slug) {
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

        foreach (['tujuan', 'python', 'variabel', 'tipe-data', 'input-output', 'operator', 'percabangan', 'perulangan', 'fungsi', 'oop', 'rangkuman', 'latihan', 'kuis'] as $id) {
            $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
        }

        $response->assertSee('Latihan BAB 1')->assertSee('Kuis BAB 1');
        $response->assertSee('disabled aria-describedby="quiz-availability"', false);
        $response->assertDontSee(route('materi.show', 'kelas-dan-objek'));
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

    public function test_home_and_editor_remain_available(): void
    {
        $this->get('/')->assertOk()->assertSee(route('materi.index'));
        $this->get('/editor')->assertOk()->assertSee('Live Coding Component Demo');
    }
}
