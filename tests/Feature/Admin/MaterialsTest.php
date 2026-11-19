<?php

namespace Tests\Feature\Admin;

use App\Models\Material;
use App\Models\MaterialContent;
use App\Models\User;
use App\Services\Admin\MaterialContentService;
use Database\Seeders\OopyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MaterialsTest extends TestCase
{
    use RefreshDatabase;

    private Material $material;
    private array $temporaryPublicAssets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OopyContentSeeder::class);
        $this->material = Material::where('slug', 'dasar-pemrograman-oop')->firstOrFail();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryPublicAssets as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
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
        $this->get('/admin/materials/'.$this->material->id.'/sync')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/materials')->assertForbidden();
        $this->get('/admin/materials/'.$this->material->id.'/sync')->assertForbidden();
        $this->put('/admin/materials/'.$this->material->id, $this->payload())->assertForbidden();
        $this->post('/admin/materials/'.$this->material->id.'/sync', ['version' => 0, 'sync_fields' => ['global:description'], 'acknowledge_source' => '1'])->assertForbidden();
        $this->post('/admin/materials/'.$this->material->id.'/preview', $this->payload())->assertForbidden();
        $this->post('/admin/materials/'.$this->material->id.'/publish', ['version' => 0])->assertForbidden();
        $this->assertDatabaseCount('material_contents', 0);
    }

    public function test_draft_preview_and_publish_preserve_old_live_code_and_render_escaped_text(): void
    {
        $this->actingAs($this->admin())->get('/admin/materials')->assertOk()->assertSee('Materi bawaan');
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

    public function test_legacy_learning_checks_are_loaded_from_the_single_php_backed_source(): void
    {
        $legacy = json_decode(file_get_contents(resource_path('materi/learning-checks.json')), true);
        $service = app(MaterialContentService::class);
        $this->assertCount(19, $legacy);
        $matched = 0;
        foreach ($service->registry() as $slug => $chapter) {
            $byId = collect($service->source($slug)['sections'])->keyBy('id');
            foreach ($legacy as $sectionId => $config) {
                if ($byId->has($sectionId)) {
                    $this->assertSame($config, $byId[$sectionId]['learning_check']);
                    $matched++;
                }
            }
        }
        $this->assertSame(count($legacy), $matched);
        $this->get('/materi/'.$this->material->slug)->assertOk()->assertSee('data-learning-check-config', false);
        $this->assertStringNotContainsString('const learningChecks = {', file_get_contents(public_path('js/oopy-learning-checks.js')));
    }

    public function test_new_ungraded_activity_is_saved_previewed_published_and_can_be_disabled(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $payload['sections'][] = [
            'id' => '', 'order' => 99, 'title' => 'Bagian aktivitas baru', 'paragraphs' => 'Penjelasan baru.', 'media_present' => 1,
            'learning_check' => [
                'enabled' => '1', 'kind' => 'Pahami konsep', 'prompt' => 'Apa fungsi class?', 'code' => '',
                'options' => [
                    ['label' => 'Membuat rancangan object', 'correct' => '1', 'feedback' => 'Class mendeskripsikan data dan perilaku.'],
                    ['label' => 'Menyimpan semua nilai global', 'correct' => '0', 'feedback' => 'Nilai tiap instance dapat berbeda.'],
                ],
                'visualization' => ['enabled' => '0', 'caption' => '', 'layout' => 'compare', 'items' => []],
            ],
        ];
        $url = '/admin/materials/'.$this->material->id;
        $this->put($url, $payload)->assertRedirect();
        $record = MaterialContent::sole();
        $added = collect($record->draft['sections'])->firstWhere('title', 'Bagian aktivitas baru');
        $this->assertStringStartsWith('admin-', $added['id']);
        $this->assertSame('Apa fungsi class?', $added['learning_check']['prompt']);
        $preview = $payload;
        $preview['version'] = 1;
        $preview['sections'][count($preview['sections']) - 1]['id'] = $added['id'];
        $this->post($url.'/preview', $preview)->assertOk()->assertSee('Apa fungsi class?')->assertSee('data-learning-check-config', false);
        $this->get('/materi/'.$this->material->slug)->assertDontSee('Apa fungsi class?');
        $this->post($url.'/publish', ['version' => 1])->assertRedirect();
        $this->get('/materi/'.$this->material->slug)->assertSee('Apa fungsi class?')->assertSee('data-learning-check-config', false);

        $current = MaterialContent::sole()->fresh()->draft;
        $disable = $this->payload();
        $disable['version'] = 2;
        $disable['sections'] = array_map(fn ($section, $index) => [
            'id' => $section['id'], 'order' => $index + 1, 'title' => $section['title'],
            'paragraphs' => implode("\n\n", $section['paragraphs'] ?? []), 'code' => $section['code'] ?? '',
            'output' => $section['output'] ?? '', 'tip' => $section['tip'] ?? '',
        ], $current['sections'], array_keys($current['sections']));
        $disable['sections'][1]['learning_check'] = ['enabled' => '0'];
        $this->put($url, $disable)->assertRedirect();
        $this->assertNull(MaterialContent::sole()->draft['sections'][1]['learning_check']);
        $this->post($url.'/publish', ['version' => 3])->assertRedirect();
        $this->get('/materi/'.$this->material->slug)->assertDontSee('Variabel ph pada potongan kode ini menyimpan nilai bertipe apa?');
    }

    public function test_media_library_selection_is_local_and_unsafe_paths_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $payload['sections'][0]['media_present'] = 1;
        $payload['sections'][0]['media'] = [[
            'type' => 'diagram', 'src' => 'images/materi/diagram/bab1-prosedural-vs-oop.svg',
            'alt' => 'Diagram pengelompokan data dan perilaku.', 'caption' => 'Data serta perilaku ditempatkan bersama di dalam object.',
        ]];
        $url = '/admin/materials/'.$this->material->id;
        $this->put($url, $payload)->assertRedirect();
        $saved = MaterialContent::sole()->draft['sections'][0]['media'][0];
        $this->assertSame('images/materi/diagram/bab1-prosedural-vs-oop.svg', $saved['src']);
        $this->get($url.'/preview')->assertOk()->assertSee('Data serta perilaku ditempatkan bersama');

        $payload['version'] = 1;
        $payload['sections'][0]['media'][0]['src'] = '../outside.svg';
        $this->put($url, $payload)->assertSessionHasErrors('sections.0.media.0.src');
        $this->assertSame('images/materi/diagram/bab1-prosedural-vs-oop.svg', MaterialContent::sole()->fresh()->draft['sections'][0]['media'][0]['src']);

        $payload['sections'][0]['media'][0]['src'] = 'https://example.test/diagram.svg';
        $this->put($url, $payload)->assertSessionHasErrors('sections.0.media.0.src');
    }

    public function test_raster_uploads_are_saved_locally_and_uploaded_svg_is_rejected(): void
    {
        $this->actingAs($this->admin());
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/z2cAAAAASUVORK5CYII=');
        $upload = UploadedFile::fake()->createWithContent('diagram.png', $png);
        $payload = $this->payload();
        $payload['sections'][0]['media_present'] = 1;
        $payload['sections'][0]['media'] = [[
            'type' => 'diagram', 'src' => '', 'upload' => $upload, 'alt' => 'Diagram baru.', 'caption' => 'Diagram yang diunggah untuk penjelasan materi.',
        ]];
        $this->put('/admin/materials/'.$this->material->id, $payload)->assertRedirect();
        $path = public_path(MaterialContent::sole()->draft['sections'][0]['media'][0]['src']);
        $this->temporaryPublicAssets[] = $path;
        $this->assertFileExists($path);

        $payload['version'] = 1;
        $payload['sections'][0]['media'][0]['upload'] = UploadedFile::fake()->createWithContent('unsafe.svg', '<svg onload="alert(1)"></svg>');
        $payload['sections'][0]['media'][0]['src'] = '';
        $this->put('/admin/materials/'.$this->material->id, $payload)->assertSessionHasErrors('sections.0.media.0.upload');
    }

    public function test_uploaded_file_is_removed_when_later_draft_validation_rolls_back(): void
    {
        $this->actingAs($this->admin());
        $directory = public_path('images/materi/uploads');
        $before = is_dir($directory) ? array_map('basename', glob($directory.'/*') ?: []) : [];
        $payload = $this->payload();
        $payload['sections'] = array_slice($payload['sections'], 0, 1);
        $payload['sections'][0]['media_present'] = 1;
        $payload['sections'][0]['media'] = [[
            'type' => 'diagram', 'src' => '',
            'upload' => UploadedFile::fake()->createWithContent('rollback.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/z2cAAAAASUVORK5CYII=')),
            'alt' => 'Diagram sementara.', 'caption' => 'Upload ini harus dibersihkan setelah draf ditolak.',
        ]];

        $this->put('/admin/materials/'.$this->material->id, $payload)->assertSessionHasErrors('sections');
        $after = is_dir($directory) ? array_map('basename', glob($directory.'/*') ?: []) : [];
        sort($before);
        sort($after);
        $this->assertSame($before, $after);
        $this->assertDatabaseCount('material_contents', 0);
    }

    public function test_sync_is_explicit_keeps_publication_immutable_and_rejects_stale_versions(): void
    {
        $source = app(MaterialContentService::class)->source($this->material->slug);
        $published = $source;
        $published['description'] = 'Snapshot terbit lama';
        $draft = $source;
        $draft['description'] = 'Perubahan yang ditulis admin';
        MaterialContent::create([
            'material_id' => $this->material->id, 'draft_title' => $this->material->title, 'draft' => $draft,
            'published_title' => $this->material->title, 'published' => $published, 'published_at' => now(),
            'draft_source_hash' => str_repeat('a', 64), 'published_source_hash' => str_repeat('a', 64), 'lock_version' => 4,
        ]);
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/materials/'.$this->material->id.'/edit')->assertOk()->assertSee('Perubahan file sumber terdeteksi');
        $this->get('/admin/materials/'.$this->material->id.'/sync')->assertOk()->assertSee('Perubahan yang ditulis admin')->assertSee('Snapshot terbit lama');
        $this->post('/admin/materials/'.$this->material->id.'/sync', ['version' => 3, 'sync_fields' => ['global:description'], 'acknowledge_source' => '1'])->assertSessionHasErrors('version');
        $this->assertSame('Perubahan yang ditulis admin', MaterialContent::sole()->fresh()->draft['description']);

        $this->post('/admin/materials/'.$this->material->id.'/sync', ['version' => 4, 'sync_fields' => ['global:description'], 'acknowledge_source' => '1'])->assertRedirect();
        $record = MaterialContent::sole()->fresh();
        $this->assertSame($source['description'], $record->draft['description']);
        $this->assertSame('Snapshot terbit lama', $record->published['description']);
        $this->assertSame(str_repeat('a', 64), $record->published_source_hash);
    }

    public function test_legacy_hashes_are_not_assigned_by_an_ordinary_draft_save(): void
    {
        MaterialContent::create([
            'material_id' => $this->material->id, 'draft_title' => $this->material->title,
            'draft' => app(MaterialContentService::class)->source($this->material->slug), 'published_at' => now(),
            'published_title' => $this->material->title, 'published' => app(MaterialContentService::class)->source($this->material->slug), 'lock_version' => 0,
        ]);
        $this->actingAs($this->admin())->get('/admin/materials')->assertSee('Belum terverifikasi / perlu ditinjau');
        $payload = $this->payload();
        $this->put('/admin/materials/'.$this->material->id, $payload)->assertRedirect();
        $this->assertNull(MaterialContent::sole()->fresh()->draft_source_hash);
        $this->assertNull(MaterialContent::sole()->fresh()->published_source_hash);
    }

    public function test_published_snapshot_is_returned_without_shallow_source_merge(): void
    {
        $service = app(MaterialContentService::class);
        $source = $service->source($this->material->slug);
        $snapshot = $source;
        $snapshot['description'] = 'Snapshot terbit tetap';
        $snapshot['sections'] = array_slice($snapshot['sections'], 0, 1);
        MaterialContent::create([
            'material_id' => $this->material->id, 'draft_title' => 'Draf', 'draft' => $source,
            'published_title' => 'Terbit', 'published' => $snapshot, 'published_at' => now(), 'lock_version' => 2,
        ]);
        $changedFileInput = $source;
        $changedFileInput['new_source_field'] = 'Tidak boleh digabung ke snapshot';
        [$actual, $title] = $service->published($this->material->slug, $changedFileInput);

        $this->assertSame($snapshot, $actual);
        $this->assertSame('Terbit', $title);
        $this->assertArrayNotHasKey('new_source_field', $actual);
    }

    public function test_admin_can_explicitly_keep_every_draft_field_after_reviewing_source(): void
    {
        $source = app(MaterialContentService::class)->source($this->material->slug);
        $draft = $source;
        $draft['description'] = 'Draf admin dipertahankan.';
        MaterialContent::create([
            'material_id' => $this->material->id, 'draft_title' => 'Judul admin', 'draft' => $draft,
            'draft_source_hash' => str_repeat('b', 64), 'lock_version' => 1,
        ]);
        $this->actingAs($this->admin());
        $this->post('/admin/materials/'.$this->material->id.'/sync', ['version' => 1, 'acknowledge_source' => '1'])->assertRedirect();
        $record = MaterialContent::sole()->fresh();
        $this->assertSame('Draf admin dipertahankan.', $record->draft['description']);
        $this->assertSame('Judul admin', $record->draft_title);
        $this->assertSame(app(MaterialContentService::class)->sourceHash($this->material->slug), $record->draft_source_hash);
    }

    public function test_activity_validation_rejects_multiple_correct_options_and_untrusted_fields(): void
    {
        $this->actingAs($this->admin());
        $payload = $this->payload();
        $payload['sections'][1]['learning_check'] = [
            'enabled' => '1', 'kind' => 'Pahami', 'prompt' => 'Pilih jawaban.', 'code' => '',
            'options' => [
                ['label' => 'A', 'correct' => '1', 'feedback' => 'Feedback A'],
                ['label' => 'B', 'correct' => '1', 'feedback' => 'Feedback B'],
            ],
            'visualization' => ['enabled' => '0', 'caption' => '', 'layout' => 'compare', 'items' => []],
        ];
        $url = '/admin/materials/'.$this->material->id;
        $this->put($url, $payload)->assertSessionHasErrors('sections.1.learning_check.options');

        $payload['sections'][1]['learning_check']['options'][1]['correct'] = '0';
        $payload['sections'][1]['learning_check']['checker'] = ['code' => 'malicious'];
        $this->put($url, $payload)->assertSessionHasErrors('sections.1.learning_check');
        $this->get($url.'/edit')->assertOk()->assertSee('Coba pahami');

        $payload['sections'][1]['learning_check'] = 'not-an-array';
        $this->put($url, $payload)->assertSessionHasErrors('sections.1.learning_check');
        $this->get($url.'/edit')->assertOk();
        $this->assertDatabaseCount('material_contents', 0);
    }
}
