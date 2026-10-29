<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaterialRequest;
use App\Models\Material;
use App\Models\MaterialContent;
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

        return view('admin.materials.index', compact('materials', 'records', 'registry'));
    }

    public function edit(Material $material)
    {
        return view('admin.materials.edit', ['material' => $material] + $this->contents->editor($material));
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

    public function publish(Request $request, Material $material)
    {
        $this->contents->source($material->slug);
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        $this->contents->publish($material, (int) $data['version'], $request->user()->id);

        return redirect()->route('admin.materials.edit', $material)->with('success', 'Materi dipublikasikan dan sudah tampil pada halaman pengguna.');
    }
}
