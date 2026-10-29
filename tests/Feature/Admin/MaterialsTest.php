<?php

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\MaterialContent;
use App\Models\User;
use App\Services\Admin\MaterialContentService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialsTest extends TestCase
{
    use RefreshDatabase;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OopyContentSeeder::class);
        $this->material = Material::where('slug', 'dasar-pemrograman-oop')->firstOrFail();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->role = 'admin';
        $user->save();

        return $user;
    }

    private function payload(): array
    {
        $source = app(MaterialContentService::class)->source($this->material->slug);

        return [
            'version' => 0,
            'title' => 'Judul materi yang diperbarui',
            'description' => 'Penjelasan yang diperbarui',
            'objectives' => implode("\n", $source['objectives']),
            'summary' => implode("\n", $source['summary']),
            'reflection' => implode("\n", $source['reflection']),
            'sections' => array_map(fn ($section, $index) => [
                'id' => $section['id'], 'order' => $index + 1, 'title' => $section['title'],
                'paragraphs' => implode("\n\n", $section['paragraphs'] ?? []),
                'code' => $section['code'] ?? '', 'output' => $section['output'] ?? '', 'tip' => $section['tip'] ?? '',
            ], $source['sections'], array_keys($source['sections'])),
        ];
    }

    public function test_material_routes_require_admin_and_mutations_are_forbidden_for_users(): void
    {
        $this->get('/admin/materials')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/materials')->assertForbidden();
        $this->put('/admin/materials/'.$this->material->id, $this->payload())->assertForbidden();
        $this->post('/admin/materials/'.$this->material->id.'/preview', $this->payload())->assertForbidden();
        $this->post('/admin/materials/'.$this->material->id.'/publish', ['version' => 0])->assertForbidden();
        $this->assertDatabaseCount('material_contents', 0);
    }

    public function test_draft_preview_and_publish_preserve_old_live_code_and_render_escaped_text(): void
    {
        $this->actingAs($this->admin())->get('/admin/materials')->assertOk()->assertSee('Materi awal');
        $this->get('/admin/materials/'.$this->material->id.'/edit')->assertOk()->assertSee('Simpan draf');
        $payload = $this->payload();
        $payload['sections'][0]['paragraphs'] = '<script>alert("xss")</script>';
        $payload['sections'][0]['code'] = "    print('teks')\n";
        $payload['sections'][] = ['id' => '', 'order' => 99, 'title' => 'Bagian tambahan', 'paragraphs' => 'Paragraf pertama'."\n\n".'Paragraf kedua', 'code' => "class Sensor:\n    pass", 'output' => 'Contoh output'];
        $url = '/admin/materials/'.$this->material->id;
        $this->post($url.'/preview', $payload)->assertOk()->assertSee('Bagian tambahan')->assertDontSee('<script>alert("xss")</script>', false);
        $this->assertDatabaseCount('material_contents', 0);
        $this->put($url, $payload)->assertRedirect();
        $record = MaterialContent::sole();
        $this->assertSame("    print('teks')\n", $record->draft['sections'][0]['code']);
        $this->assertNull($record->published);
        $this->get('/materi/'.$this->material->slug)->assertDontSee('Bagian tambahan');
        $this->get($url.'/preview')->assertOk()->assertSee('Publikasikan draf ini');
        $this->post($url.'/publish', ['version' => 1])->assertRedirect();
        $response = $this->get('/materi/'.$this->material->slug)->assertOk()->assertSee('Judul materi yang diperbarui')->assertSee('Bagian tambahan')->assertSee('data-live-code', false);
        $response->assertDontSee('<script>alert("xss")</script>', false)->assertSee('&lt;script&gt;', false);
        $this->get('/materi')->assertSee('Judul materi yang diperbarui');
        $original = app(MaterialContentService::class)->source($this->material->slug);
        $originalLive = collect($original['sections'])->flatMap(fn ($section) => $section['live_codes'] ?? [])->all();
        $publishedLive = collect($record->fresh()->published['sections'])->flatMap(fn ($section) => $section['live_codes'] ?? [])->all();
        $this->assertSame($originalLive, $publishedLive);
    }

    public function test_stale_write_and_complex_runtime_tampering_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $url = '/admin/materials/'.$this->material->id;
        $this->put($url, $payload)->assertRedirect();
        $this->put($url, $payload)->assertSessionHasErrors('version');
        $this->post($url.'/publish', ['version' => 0])->assertSessionHasErrors('version');
        $payload['version'] = 1;
        $payload['sections'][0]['live_codes'] = [['checker' => 'malicious']];
        $this->put($url, $payload)->assertSessionHasErrors('sections.0');
        unset($payload['sections'][0]['live_codes']);
        array_shift($payload['sections']);
        $this->put($url, $payload)->assertSessionHasErrors('sections');
        $this->assertSame(1, MaterialContent::sole()->lock_version);
    }

    public function test_reordering_sections_and_reseeding_keep_admin_content_and_old_source(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $payload['sections'][0]['order'] = 99;
        $url = '/admin/materials/'.$this->material->id;
        $this->put($url, $payload)->assertRedirect();
        $this->post($url.'/publish', ['version' => 1])->assertRedirect();
        $this->seed(OopyContentSeeder::class);
        $published = MaterialContent::sole()->published;
        $this->assertSame('apersepsi', end($published['sections'])['id']);
        $this->get('/materi/'.$this->material->slug)->assertSee('Judul materi yang diperbarui');
        $this->assertFileExists(resource_path('materi/dasar-pemrograman-oop.php'));
    }
}
