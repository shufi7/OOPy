<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaterialRequest;
use App\Models\Material;
use App\Models\MaterialContent;
use App\Services\Admin\MaterialAssetService;
use App\Services\Admin\MaterialContentService;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(private MaterialContentService $contents) {}

    public function index()
    {
        $materials = Material::whereIn('slug', array_keys($this->contents->registry()))->orderBy('chapter_id')->get();
        $records = MaterialContent::whereIn('material_id', $materials->pluck('id'))->get()->keyBy('material_id');
        $registry = $this->contents->registry();
        $statuses = $materials->mapWithKeys(fn ($material) => [$material->id => $this->contents->versionStatus($material, $records->get($material->id))]);

        return view('admin.materials.index', compact('materials', 'records', 'registry', 'statuses'));
    }

    public function edit(Material $material, MaterialAssetService $assets)
    {
        return view('admin.materials.edit', ['material' => $material, 'assetChoices' => $assets->choices(), 'chapterMeta' => $this->contents->registry()[$material->slug]] + $this->contents->editor($material));
    }

    public function update(MaterialRequest $request, Material $material)
    {
        $this->contents->save($material, $request->validated(), $request->user()->id);

        return redirect()->route('admin.materials.edit', $material)->with('success', 'Draf materi disimpan. Periksa pratinjau lalu publikasikan agar tampil ke pengguna.');
    }

    public function preview(MaterialRequest $request, Material $material)
    {
        $content = $this->contents->compose($material, $request->validated());
        $title = $request->validated('title');
        $saved = false;

        return response()->view('admin.materials.preview', compact('material', 'content', 'title', 'saved'))->header('Cache-Control', 'no-store, private');
    }

    public function showPreview(Material $material)
    {
        $editor = $this->contents->editor($material);

        return response()->view('admin.materials.preview', ['material' => $material, 'saved' => true] + $editor)->header('Cache-Control', 'no-store, private');
    }

    public function sync(Material $material)
    {
        $editor = $this->contents->editor($material);
        $changes = $this->contents->syncChanges($material);
        $publishedSummary = $this->contents->publishedSummary($material);

        return view('admin.materials.sync', ['material' => $material, 'changes' => $changes, 'publishedSummary' => $publishedSummary] + $editor);
    }

    public function synchronize(Request $request, Material $material)
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'sync_fields' => ['nullable', 'array', 'max:80'],
            'sync_fields.*' => ['required', 'string', 'max:300', 'distinct'],
            'acknowledge_source' => ['required', 'accepted'],
        ]);
        $this->contents->synchronize($material, $data['sync_fields'] ?? [], (int) $data['version'], $request->user()->id, true);

        return redirect()->route('admin.materials.edit', $material)->with('success', 'Pilihan sumber disalin ke draf. Pratinjau dan publikasikan secara terpisah setelah diperiksa.');
    }

    public function publish(Request $request, Material $material)
    {
        $this->contents->source($material->slug);
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        $this->contents->publish($material, (int) $data['version'], $request->user()->id);

        return redirect()->route('admin.materials.edit', $material)->with('success', 'Materi dipublikasikan dan sudah tampil pada halaman pengguna.');
    }
}
