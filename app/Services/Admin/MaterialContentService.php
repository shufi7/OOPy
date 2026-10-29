<?php

namespace App\Services\Admin;

use App\Models\Material;
use App\Models\MaterialContent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaterialContentService
{
    public function registry(): array
    {
        return array_filter(require resource_path('materi/chapters.php'), fn ($chapter, $slug) => $slug !== 'evaluasi-akhir' && ! empty($chapter['content']), ARRAY_FILTER_USE_BOTH);
    }

    public function source(string $slug): array
    {
        $metadata = $this->registry()[$slug] ?? abort(404);

        return require resource_path('materi/'.$metadata['content']);
    }

    public function publishedRegistry(array $registry): array
    {
        try {
            $records = MaterialContent::with('material:id,slug')->whereNotNull('published_at')->get();
        } catch (QueryException) {
            return $registry;
        }
        foreach ($records as $record) {
            if (isset($registry[$record->material->slug]) && $record->published_title !== null) {
                $registry[$record->material->slug]['judul'] = $record->published_title;
            }
        }

        return $registry;
    }

    public function published(string $slug, array $source): array
    {
        if (! isset($this->registry()[$slug])) {
            return [$source, null];
        }
        try {
            $record = MaterialContent::whereHas('material', fn ($query) => $query->where('slug', $slug))->first();
        } catch (QueryException) {
            // Public reading remains available from the original files before the additive migration is deployed.
            return [$source, null];
        }

        return $record?->published !== null
            ? [array_replace($source, $record->published), $record->published_title]
            : [$source, null];
    }

    public function editor(Material $material): array
    {
        $source = $this->source($material->slug);
        $record = MaterialContent::where('material_id', $material->id)->first();

        return [
            'content' => $record ? array_replace($source, $record->draft) : $source,
            'title' => $record?->draft_title ?? $material->title,
            'version' => $record?->lock_version ?? 0,
            'record' => $record,
        ];
    }

    public function compose(Material $material, array $data): array
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
            // Only these plain-text fields can come from the browser. All runtime/checker configuration stays trusted.
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
        return DB::transaction(function () use ($material, $data, $userId) {
            Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $content = $this->compose($material, $data);
            $record = MaterialContent::firstOrNew(['material_id' => $material->id]);
            $record->fill(['draft_title' => $data['title'], 'draft' => $content, 'lock_version' => (int) $data['version'] + 1, 'updated_by' => $userId])->save();

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
            $record->update(['published_title' => $record->draft_title, 'published' => $record->draft, 'published_at' => now(), 'updated_by' => $userId, 'lock_version' => $version + 1]);
        }, 3);
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
