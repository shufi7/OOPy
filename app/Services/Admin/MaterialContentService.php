<?php

namespace App\Services\Admin;

use App\Models\Material;
use App\Models\MaterialContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaterialContentService
{
    private const SECTION_SYNC_FIELDS = [
        'title', 'nav_title', 'paragraphs', 'code', 'output', 'tip', 'media', 'learning_check',
        'tables', 'breakdown', 'hierarchy', 'examples', 'instructions', 'exploration', 'practice',
    ];

    private const GLOBAL_SYNC_FIELDS = ['description', 'objectives', 'summary', 'reflection'];
    private const PROTECTED_SYNC_FIELDS = ['quiz', 'live_codes', 'checker', 'assessment', 'test_cases', 'grading'];

    public function __construct(private MaterialAssetService $assets) {}

    public function registry(): array
    {
        return array_filter(require resource_path('materi/chapters.php'), fn ($chapter, $slug) => $slug !== 'evaluasi-akhir' && ! empty($chapter['content']), ARRAY_FILTER_USE_BOTH);
    }

    public function source(string $slug): array
    {
        $metadata = $this->registry()[$slug] ?? abort(404);
        $content = require resource_path('materi/'.$metadata['content']);
        $legacyChecks = $this->legacyChecks();
        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as &$section) {
                if (! array_key_exists('learning_check', $section) && isset($legacyChecks[$section['id']])) {
                    $section['learning_check'] = $legacyChecks[$section['id']];
                }
            }
            unset($section);
        }

        return $content;
    }

    public function sourceHash(string $slug): string
    {
        $metadata = $this->registry()[$slug] ?? abort(404);
        $fingerprint = ['chapter' => $metadata, 'content' => $this->source($slug)];

        return hash('sha256', json_encode($this->canonicalize($fingerprint), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function publishedRegistry(array $registry): array
    {
        if (! Schema::hasTable('material_contents')) {
            return $registry;
        }
        $records = MaterialContent::with('material:id,slug')->whereNotNull('published_at')->get();
        foreach ($records as $record) {
            if (isset($registry[$record->material->slug]) && $record->published_title !== null) {
                $registry[$record->material->slug]['judul'] = $record->published_title;
            }
        }

        return $registry;
    }

    public function published(string $slug, array $source): array
    {
        if (! isset($this->registry()[$slug]) || ! Schema::hasTable('material_contents')) {
            return [$this->withLegacyChecks($source), null];
        }
        $record = MaterialContent::whereHas('material', fn ($query) => $query->where('slug', $slug))->first();
        if ($record?->published === null) {
            return [$this->withLegacyChecks($source), null];
        }

        $published = $record->published;
        if (empty($published['quiz']) && ! empty($source['quiz'])) {
            $published['quiz'] = $source['quiz'];
        }

        return [$this->withLegacyChecks($published), $record->published_title];
    }

    public function editor(Material $material): array
    {
        $source = $this->source($material->slug);
        $record = Schema::hasTable('material_contents') ? MaterialContent::where('material_id', $material->id)->first() : null;
        $content = $record ? $this->withLegacyChecks($record->draft) : $source;

        return [
            'content' => $content,
            'title' => $record?->draft_title ?? $material->title,
            'version' => $record?->lock_version ?? 0,
            'record' => $record,
            'source_hash' => $this->sourceHash($material->slug),
            'source_status' => $this->versionStatus($material, $record),
        ];
    }

    public function versionStatus(Material $material, ?MaterialContent $record = null): array
    {
        $record ??= Schema::hasTable('material_contents') ? MaterialContent::where('material_id', $material->id)->first() : null;
        $current = $this->sourceHash($material->slug);
        if (! $record) {
            return ['label' => 'Materi bawaan', 'tone' => 'neutral', 'drift' => false, 'review' => false, 'published' => false, 'draft_changed' => false, 'source_hash' => $current];
        }

        $published = $record->published_at !== null;
        $legacy = ! $record->draft_source_hash || ($published && ! $record->published_source_hash);
        $drift = ($record->draft_source_hash && ! hash_equals($record->draft_source_hash, $current))
            || ($record->published_source_hash && ! hash_equals($record->published_source_hash, $current));
        $draftChanged = $published && ($record->draft_source_hash !== $record->published_source_hash
            || $record->draft !== $record->published || $record->draft_title !== $record->published_title);
        $label = $legacy ? 'Belum terverifikasi / perlu ditinjau'
            : ($drift ? 'Perubahan file sumber terdeteksi'
                : (! $published ? 'Draf belum dipublikasikan'
                    : ($draftChanged ? 'Draf belum dipublikasikan' : 'Sudah dipublikasikan')));

        return ['label' => $label, 'tone' => $legacy || $drift ? 'warning' : ($published && ! $draftChanged ? 'success' : 'neutral'), 'drift' => $drift, 'review' => $legacy, 'published' => $published, 'draft_changed' => $draftChanged, 'source_hash' => $current];
    }

    public function compose(Material $material, array $data, bool $persistUploads = false, array &$createdFiles = []): array
    {
        $editor = $this->editor($material);
        if ($editor['version'] !== (int) $data['version']) {
            throw ValidationException::withMessages(['version' => 'Materi sudah diubah di tab lain. Muat ulang sebelum menyimpan.']);
        }
        $base = $editor['content'];
        $existing = collect($base['sections'])->keyBy('id');
        $seen = [];
        $sections = [];
        foreach ($data['sections'] as $index => $input) {
            $id = $input['id'] ?? null;
            if ($id && (! $existing->has($id) || isset($seen[$id]))) {
                throw ValidationException::withMessages(["sections.$index.id" => 'Identitas bagian tidak valid.']);
            }
            if ($id) {
                $seen[$id] = true;
            }
            $section = $id ? $existing[$id] : ['id' => 'admin-'.strtolower((string) Str::ulid())];
            if (($section['title'] ?? null) !== $input['title']) {
                $section['nav_title'] = $input['title'];
            }
            $section['title'] = $input['title'];
            $section['paragraphs'] = $this->paragraphs($input['paragraphs'] ?? '');
            foreach (['code', 'output', 'tip'] as $field) {
                if (($input[$field] ?? '') !== '' && $input[$field] !== null) {
                    $section[$field] = $input[$field];
                } else {
                    unset($section[$field]);
                }
            }
            if (array_key_exists('media_present', $input) || array_key_exists('media', $input)) {
                $media = $this->assets->normalize($input['media'] ?? [], $persistUploads, $createdFiles, (string) $index);
                if ($media) {
                    $section['media'] = $media;
                } else {
                    unset($section['media']);
                }
            }
            if (array_key_exists('learning_check', $input)) {
                $section['learning_check'] = $this->normalizeLearningCheck($input['learning_check']);
            }
            $sections[] = ['order' => (int) $input['order'], 'section' => $section];
        }
        if (array_diff($existing->keys()->all(), array_keys($seen))) {
            throw ValidationException::withMessages(['sections' => 'Seluruh bagian lama harus tetap disertakan agar aktivitas pembelajaran tidak hilang.']);
        }
        usort($sections, fn ($a, $b) => $a['order'] <=> $b['order']);

        return [
            'description' => $data['description'] ?? '',
            'objectives' => $this->lines($data['objectives'] ?? ''),
            'summary' => $this->lines($data['summary'] ?? ''),
            'reflection' => $this->lines($data['reflection'] ?? ''),
            'sections' => array_column($sections, 'section'),
        ];
    }

    public function save(Material $material, array $data, int $userId): MaterialContent
    {
        $createdFiles = [];
        try {
            return DB::transaction(function () use ($material, $data, $userId, &$createdFiles) {
                Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
                $record = MaterialContent::where('material_id', $material->id)->lockForUpdate()->first();
                $version = $record?->lock_version ?? 0;
                if ($version !== (int) $data['version']) {
                    throw ValidationException::withMessages(['version' => 'Draf sudah berubah di tab lain. Muat ulang sebelum menyimpan.']);
                }
                $content = $this->compose($material, $data, true, $createdFiles);
                $isNew = $record === null;
                $record ??= new MaterialContent(['material_id' => $material->id]);
                if ($isNew) {
                    $record->draft_source_hash = $this->sourceHash($material->slug);
                }
                $record->fill(['draft_title' => $data['title'], 'draft' => $content, 'lock_version' => $version + 1, 'updated_by' => $userId])->save();

                return $record;
            }, 3);
        } catch (\Throwable $error) {
            foreach ($createdFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $error;
        }
    }

    public function syncChanges(Material $material): array
    {
        $source = $this->source($material->slug);
        $current = $this->editor($material)['content'];
        $changes = [];
        $sourceTitle = $this->registry()[$material->slug]['judul'] ?? $material->title;
        $currentTitle = $this->editor($material)['title'];
        if ($currentTitle !== $sourceTitle) {
            $changes[] = ['key' => 'global:title', 'label' => 'Judul BAB', 'section' => null, 'field' => 'title', 'current' => $currentTitle, 'source' => $sourceTitle];
        }
        foreach (self::GLOBAL_SYNC_FIELDS as $field) {
            if (($current[$field] ?? null) !== ($source[$field] ?? null)) {
                $changes[] = ['key' => 'global:'.$field, 'label' => ucfirst($field), 'section' => null, 'field' => $field, 'current' => $current[$field] ?? null, 'source' => $source[$field] ?? null];
            }
        }
        foreach (self::PROTECTED_SYNC_FIELDS as $field) {
            if (($current[$field] ?? null) !== ($source[$field] ?? null)) {
                $changes[] = ['key' => 'protected:global:'.$field, 'label' => ucfirst(str_replace('_', ' ', $field)).' · komponen terlindungi', 'section' => null, 'field' => '__protected', 'current' => empty($current[$field]) ? 'Tidak ada pada snapshot saat ini' : 'Ada pada snapshot saat ini', 'source' => empty($source[$field]) ? 'Tidak ada pada file sumber' : 'Ada pada file sumber'];
            }
        }
        $currentSections = collect($current['sections'] ?? [])->keyBy('id');
        $sourceSections = collect($source['sections'] ?? [])->keyBy('id');
        if ($currentSections->keys()->all() !== $sourceSections->keys()->all()) {
            $changes[] = ['key' => 'global:sections_order', 'label' => 'Urutan bagian berdasarkan file sumber', 'section' => null, 'field' => 'sections_order', 'current' => $currentSections->keys()->all(), 'source' => $sourceSections->keys()->all()];
        }
        foreach ($sourceSections as $id => $sourceSection) {
            if (! $currentSections->has($id)) {
                $changes[] = ['key' => 'section:'.$id.':__new', 'label' => 'Tambahkan bagian sumber “'.($sourceSection['title'] ?? $id).'”', 'section' => $id, 'field' => '__new', 'current' => null, 'source' => $sourceSection];
                continue;
            }
            $currentSection = $currentSections[$id];
            foreach (self::SECTION_SYNC_FIELDS as $field) {
                if (($currentSection[$field] ?? null) !== ($sourceSection[$field] ?? null)) {
                    $changes[] = ['key' => 'section:'.$id.':'.$field, 'label' => ($sourceSection['title'] ?? $id).' · '.$field, 'section' => $id, 'field' => $field, 'current' => $currentSection[$field] ?? null, 'source' => $sourceSection[$field] ?? null];
                }
            }
            foreach (self::PROTECTED_SYNC_FIELDS as $field) {
                if (($currentSection[$field] ?? null) !== ($sourceSection[$field] ?? null)) {
                    $changes[] = ['key' => 'protected:section:'.$id.':'.$field, 'label' => ($sourceSection['title'] ?? $id).' · '.str_replace('_', ' ', $field).' terlindungi', 'section' => $id, 'field' => '__protected', 'current' => empty($currentSection[$field]) ? 'Tidak ada pada draf' : 'Berbeda/tersedia pada draf', 'source' => empty($sourceSection[$field]) ? 'Tidak ada pada file sumber' : 'Berbeda/tersedia pada file sumber'];
                }
            }
        }
        foreach ($currentSections->keys()->diff($sourceSections->keys()) as $id) {
            $changes[] = ['key' => 'section:'.$id.':__removed', 'label' => 'Bagian “'.($currentSections[$id]['title'] ?? $id).'” tidak ada di file sumber', 'section' => $id, 'field' => '__removed', 'current' => $currentSections[$id], 'source' => null];
        }
        $record = Schema::hasTable('material_contents') ? MaterialContent::where('material_id', $material->id)->first() : null;
        $currentSourceHash = $this->sourceHash($material->slug);
        $draftNeedsReview = $record && (! $record->draft_source_hash || ! hash_equals($record->draft_source_hash, $currentSourceHash));
        if (! $changes && $draftNeedsReview) {
            $changes[] = ['key' => 'review:source', 'label' => 'Tidak ada perubahan isi; catat bahwa versi sumber saat ini telah ditinjau', 'section' => null, 'field' => '__review', 'current' => 'Versi tersimpan', 'source' => 'Versi sumber saat ini'];
        }

        return $changes;
    }

    public function publishedSummary(Material $material): ?array
    {
        $record = MaterialContent::where('material_id', $material->id)->first();
        if (! $record || $record->published === null) {
            return null;
        }
        $source = $this->source($material->slug);
        $published = $this->withLegacyChecks($record->published);
        $sourceSections = collect($source['sections'] ?? [])->keyBy('id');
        $publishedSections = collect($published['sections'] ?? [])->keyBy('id');
        $changed = [];
        foreach ($sourceSections as $id => $section) {
            if (! $publishedSections->has($id)) {
                $changed[$id] = ['tidak ada di publikasi'];
                continue;
            }
            foreach (array_merge(self::SECTION_SYNC_FIELDS, ['live_codes', 'checker']) as $field) {
                if (($publishedSections[$id][$field] ?? null) !== ($section[$field] ?? null)) {
                    $changed[$id][] = $field;
                }
            }
        }
        foreach ($publishedSections->keys()->diff($sourceSections->keys()) as $id) {
            $changed[$id] = ['tidak ada di file sumber'];
        }
        $globalChanged = array_values(array_filter(array_merge(['title'], self::GLOBAL_SYNC_FIELDS), function ($field) use ($material, $record, $source, $published) {
            $publishedValue = $field === 'title' ? $record->published_title : ($published[$field] ?? null);
            $sourceValue = $field === 'title' ? ($this->registry()[$material->slug]['judul'] ?? $material->title) : ($source[$field] ?? null);

            return $publishedValue !== $sourceValue;
        }));

        return [
            'title' => $record->published_title,
            'description' => $published['description'] ?? '',
            'source_description' => $source['description'] ?? '',
            'published_at' => $record->published_at,
            'source_hash' => $record->published_source_hash,
            'global_changed' => $globalChanged,
            'order_changed' => $sourceSections->keys()->all() !== $publishedSections->keys()->all(),
            'source_section_ids' => $sourceSections->keys()->values()->all(),
            'published_section_ids' => $publishedSections->keys()->values()->all(),
            'section_changes' => $changed,
        ];
    }

    public function synchronize(Material $material, array $keys, int $version, int $userId, bool $acknowledgeSource): MaterialContent
    {
        if (! $keys && ! $acknowledgeSource) {
            throw ValidationException::withMessages(['acknowledge_source' => 'Tinjau perubahan lalu pilih sumber atau tegaskan bahwa draf saat ini dipertahankan.']);
        }
        return DB::transaction(function () use ($material, $keys, $version, $userId, $acknowledgeSource) {
            Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $record = MaterialContent::where('material_id', $material->id)->lockForUpdate()->first();
            if (($record?->lock_version ?? 0) !== $version) {
                throw ValidationException::withMessages(['version' => 'Draf berubah sejak halaman sinkronisasi dibuka. Muat ulang dan tinjau perbedaannya lagi.']);
            }
            $source = $this->source($material->slug);
            $editor = $this->editor($material);
            $content = $editor['content'];
            $available = collect($this->syncChanges($material))->keyBy('key');
            $sections = collect($content['sections'])->keyBy('id');
            foreach (array_unique($keys) as $key) {
                if (! $available->has($key)) {
                    throw ValidationException::withMessages(['sync_fields' => 'Pilihan sinkronisasi tidak lagi sesuai dengan perubahan sumber. Muat ulang halaman.']);
                }
                $change = $available[$key];
                if (in_array($change['field'], ['__removed', '__protected'], true)) {
                    continue;
                }
                if ($change['field'] === '__review') {
                    continue;
                }
                if ($change['field'] === 'sections_order') {
                    continue;
                }
                if ($change['field'] === '__new') {
                    $sections->put($change['section'], $change['source']);
                } elseif ($change['field'] === 'title' && $change['section'] === null) {
                    $content['__draft_title'] = $change['source'];
                } elseif ($change['section'] === null) {
                    if (array_key_exists($change['field'], $source)) {
                        $content[$change['field']] = $source[$change['field']];
                    } else {
                        unset($content[$change['field']]);
                    }
                } elseif (in_array($change['field'], self::SECTION_SYNC_FIELDS, true)) {
                    $section = $sections->get($change['section']);
                    if (array_key_exists($change['field'], $this->sourceSection($source, $change['section']))) {
                        $section[$change['field']] = $this->sourceSection($source, $change['section'])[$change['field']];
                    } else {
                        unset($section[$change['field']]);
                    }
                    $sections->put($change['section'], $section);
                }
            }
            if (in_array('global:sections_order', $keys, true)) {
                $sourceIds = collect($source['sections'] ?? [])->pluck('id')->all();
                $orderedIds = array_values(array_filter($sourceIds, fn ($id) => $sections->has($id)));
                $orderedIds = array_merge($orderedIds, $sections->keys()->diff($orderedIds)->values()->all());
                $sections = collect($orderedIds)->mapWithKeys(fn ($id) => [$id => $sections->get($id)]);
            }
            $content['sections'] = $sections->values()->all();
            $record ??= new MaterialContent(['material_id' => $material->id]);
            $draftTitle = $content['__draft_title'] ?? $record->draft_title ?? $material->title;
            unset($content['__draft_title']);
            $record->fill([
                'draft_title' => $draftTitle,
                'draft' => $content,
                'draft_source_hash' => $acknowledgeSource ? $this->sourceHash($material->slug) : $record->draft_source_hash,
                'lock_version' => $version + 1,
                'updated_by' => $userId,
            ])->save();

            return $record;
        }, 3);
    }

    public function publish(Material $material, int $version, int $userId): void
    {
        DB::transaction(function () use ($material, $version, $userId) {
            Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $record = MaterialContent::where('material_id', $material->id)->lockForUpdate()->firstOrFail();
            if ($record->lock_version !== $version) {
                throw ValidationException::withMessages(['version' => 'Draf telah berubah. Periksa kembali pratinjau terbaru.']);
            }
            $record->update(['published_title' => $record->draft_title, 'published' => $record->draft, 'published_source_hash' => $record->draft_source_hash, 'published_at' => now(), 'updated_by' => $userId, 'lock_version' => $version + 1]);
        }, 3);
    }

    private function sourceSection(array $source, string $id): array
    {
        return collect($source['sections'] ?? [])->firstWhere('id', $id) ?? [];
    }

    private function withLegacyChecks(array $content): array
    {
        $legacyChecks = $this->legacyChecks();
        if (isset($content['sections']) && is_array($content['sections'])) {
            foreach ($content['sections'] as &$section) {
                if (! array_key_exists('learning_check', $section) && isset($legacyChecks[$section['id']])) {
                    $section['learning_check'] = $legacyChecks[$section['id']];
                }
            }
            unset($section);
        }

        return $content;
    }

    private function legacyChecks(): array
    {
        $checks = json_decode(file_get_contents(resource_path('materi/learning-checks.json')), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($checks)) {
            throw new \UnexpectedValueException('Konfigurasi Coba pahami harus berupa object JSON.');
        }

        return $checks;
    }

    private function normalizeLearningCheck(array $input): ?array
    {
        if (! filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }
        $options = array_values(array_map(fn ($option) => [
            'label' => trim((string) ($option['label'] ?? '')),
            'correct' => filter_var($option['correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'feedback' => trim((string) ($option['feedback'] ?? '')),
        ], $input['options'] ?? []));
        if (count($options) < 2 || count($options) > 6 || count(array_filter($options, fn ($option) => $option['correct'])) !== 1
            || empty(trim((string) ($input['kind'] ?? ''))) || empty(trim((string) ($input['prompt'] ?? '')))
            || collect($options)->contains(fn ($option) => $option['label'] === '' || $option['feedback'] === '')) {
            throw ValidationException::withMessages(['learning_check' => 'Aktivitas harus berisi kategori, pertanyaan, 2–6 pilihan, tepat satu jawaban benar, dan feedback pada semua pilihan.']);
        }
        $check = ['kind' => trim($input['kind']), 'prompt' => trim($input['prompt']), 'options' => $options];
        if (trim((string) ($input['code'] ?? '')) !== '') {
            $check['code'] = $input['code'];
        }
        $visualization = $input['visualization'] ?? [];
        if (filter_var($visualization['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $items = array_values(array_map(fn ($item) => array_filter([
                'label' => trim((string) ($item['label'] ?? '')),
                'code' => trim((string) ($item['code'] ?? '')),
                'detail' => trim((string) ($item['detail'] ?? '')),
            ], fn ($value) => $value !== ''), $visualization['items'] ?? []));
            if (count($items) < 2 || count($items) > 6 || collect($items)->contains(fn ($item) => empty($item['label']) || empty($item['code']) || empty($item['detail']))) {
                throw ValidationException::withMessages(['learning_check' => 'Visualisasi aktivitas harus memiliki 2–6 item lengkap.']);
            }
            $layout = $visualization['layout'] ?? 'compare';
            if (! in_array($layout, ['compare', 'flow'], true) || empty(trim((string) ($visualization['caption'] ?? '')))) {
                throw ValidationException::withMessages(['learning_check' => 'Lengkapi caption dan tata letak visualisasi.']);
            }
            $check['visualization'] = ['caption' => trim($visualization['caption']), 'layout' => $layout, 'items' => $items];
        }

        return $check;
    }

    private function canonicalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonicalize($item);
            }
        }
        unset($item);

        return $value;
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($line) => $line !== ''));
    }

    private function paragraphs(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', $text)), fn ($line) => $line !== ''));
    }
}
