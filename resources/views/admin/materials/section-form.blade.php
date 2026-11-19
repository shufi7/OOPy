@php
    $check = is_array($section['learning_check'] ?? null) ? $section['learning_check'] : ['enabled' => 0, 'kind' => '', 'prompt' => '', 'code' => '', 'options' => [], 'visualization' => []];
    $visualization = is_array($check['visualization'] ?? null) ? $check['visualization'] : [];
    $check['visualization'] = array_merge(['enabled' => 0, 'caption' => '', 'layout' => 'compare', 'items' => []], $visualization);
    $options = is_array($check['options'] ?? null) ? array_values(array_filter($check['options'], 'is_array')) : [];
    $visualItems = is_array($check['visualization']['items'] ?? null) ? array_values(array_filter($check['visualization']['items'], 'is_array')) : [];
    $optionRows = array_pad($options, 2, ['label' => '', 'correct' => false, 'feedback' => '']);
    $visualRows = array_pad($visualItems, 2, ['label' => '', 'code' => '', 'detail' => '']);
    $mediaRows = is_array($section['media'] ?? null) ? array_values(array_filter($section['media'], 'is_array')) : [];
@endphp
<fieldset class="admin-material-section-card" data-material-section-form>
    <legend>Bagian <span data-section-number>{{ is_numeric($index) ? $index + 1 : 'baru' }}</span></legend>
    <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section['id'] ?? '' }}">
    <div class="admin-material-section-meta">
        <div><label class="form-label" for="section-{{ $index }}-order">Urutan</label><input class="form-control" type="number" min="1" max="999" required id="section-{{ $index }}-order" name="sections[{{ $index }}][order]" value="{{ $section['order'] }}"></div>
        <div><label class="form-label" for="section-{{ $index }}-title">Judul bagian</label><input class="form-control" required maxlength="255" id="section-{{ $index }}-title" name="sections[{{ $index }}][title]" value="{{ $section['title'] }}">@error('sections.'.$index.'.title')<p class="text-danger">{{ $message }}</p>@enderror</div>
    </div>
    <details class="admin-material-subpanel" open>
        <summary>Isi materi</summary>
        @foreach (['paragraphs' => ['Paragraf penjelasan', 5, 30000], 'code' => ['Contoh Python yang sudah ada', 7, 30000], 'output' => ['Output contoh', 3, 15000], 'tip' => ['Catatan belajar', 2, 5000]] as $field => [$label, $rows, $max])
        <div class="mb-3"><label class="form-label" for="section-{{ $index }}-{{ $field }}">{{ $label }}</label><textarea class="form-control {{ in_array($field, ['code', 'output']) ? 'font-monospace' : '' }}" id="section-{{ $index }}-{{ $field }}" name="sections[{{ $index }}][{{ $field }}]" rows="{{ $rows }}" maxlength="{{ $max }}" @if (in_array($field, ['code', 'output'])) spellcheck="false" autocapitalize="off" @endif>{{ $section[$field] ?? '' }}</textarea>@error('sections.'.$index.'.'.$field)<p class="text-danger">{{ $message }}</p>@enderror</div>
        @endforeach
    </details>
    <details class="admin-material-subpanel" data-media-panel>
        <summary>Foto &amp; diagram <span class="admin-material-count">{{ count($mediaRows) }} visual</span></summary>
        <p class="form-text">Maksimal dua visual per bagian. Pilih aset project atau unggah JPEG, PNG, WebP. SVG baru tidak diterima; pilih diagram SVG bawaan yang sudah diverifikasi.</p>
        <input type="hidden" name="sections[{{ $index }}][media_present]" value="1">
        <div data-media-rows>
            @foreach ($mediaRows as $mediaIndex => $visual)
                @include('admin.materials.media-row', ['mediaIndex' => $mediaIndex, 'visual' => $visual, 'sectionIndex' => $index, 'assetChoices' => $assetChoices])
            @endforeach
        </div>
        <template data-media-template>@include('admin.materials.media-row', ['mediaIndex' => '__MEDIA_INDEX__', 'visual' => [], 'sectionIndex' => $index, 'assetChoices' => $assetChoices])</template>
        <button type="button" class="btn btn-sm btn-outline-primary" data-add-media>Tambah foto/diagram</button>
    </details>
    <details class="admin-material-subpanel" data-learning-panel>
        <summary>Coba pahami <span class="admin-material-count">Aktivitas non-nilai</span></summary>
        <p class="form-text">Aktivitas ini hanya untuk latihan mandiri. Jawaban tidak dikirim ke kuis, nilai, atau progres.</p>
        <div class="mb-3"><label class="form-label" for="section-{{ $index }}-check-enabled">Status aktivitas</label><select class="form-select" id="section-{{ $index }}-check-enabled" name="sections[{{ $index }}][learning_check][enabled]"><option value="0" @selected(!filter_var($check['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))>Nonaktif</option><option value="1" @selected(filter_var($check['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))>Aktif</option></select></div>
        <div class="admin-material-check-grid">
            <div><label class="form-label" for="section-{{ $index }}-check-kind">Label/kategori</label><input class="form-control" id="section-{{ $index }}-check-kind" name="sections[{{ $index }}][learning_check][kind]" value="{{ $check['kind'] ?? '' }}" maxlength="120" placeholder="Contoh: Pahami konsep">@error('sections.'.$index.'.learning_check.kind')<p class="text-danger small">{{ $message }}</p>@enderror</div>
            <div><label class="form-label" for="section-{{ $index }}-check-prompt">Pertanyaan</label><textarea class="form-control" id="section-{{ $index }}-check-prompt" name="sections[{{ $index }}][learning_check][prompt]" rows="3" maxlength="2000">{{ $check['prompt'] ?? '' }}</textarea>@error('sections.'.$index.'.learning_check.prompt')<p class="text-danger small">{{ $message }}</p>@enderror</div>
        </div>
        <div class="mb-3"><label class="form-label" for="section-{{ $index }}-check-code">Potongan kode opsional</label><textarea class="form-control font-monospace" id="section-{{ $index }}-check-code" name="sections[{{ $index }}][learning_check][code]" rows="4" maxlength="5000" spellcheck="false">{{ $check['code'] ?? '' }}</textarea></div>
        <div class="admin-material-nested-heading"><div><h3>Pilihan jawaban</h3><p>Tepat satu jawaban benar. Setiap pilihan memiliki feedback.</p></div><button type="button" class="btn btn-sm btn-outline-secondary" data-add-option>Tambah pilihan</button></div>
        <div data-option-rows>
            @foreach ($optionRows as $optionIndex => $option)
                @include('admin.materials.option-row', ['optionIndex' => $optionIndex, 'option' => $option, 'sectionIndex' => $index])
            @endforeach
        </div>
        <template data-option-template>@include('admin.materials.option-row', ['optionIndex' => '__OPTION_INDEX__', 'option' => [], 'sectionIndex' => $index])</template>
        @error('sections.'.$index.'.learning_check.options')<p class="text-danger">{{ $message }}</p>@enderror
        <details class="admin-material-visualization"><summary>Visualisasi aktivitas opsional</summary>
            <label class="form-label" for="section-{{ $index }}-visual-enabled">Status visualisasi</label>
            <select class="form-select mb-3" id="section-{{ $index }}-visual-enabled" name="sections[{{ $index }}][learning_check][visualization][enabled]"><option value="0" @selected(!filter_var($check['visualization']['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))>Tanpa diagram kecil</option><option value="1" @selected(filter_var($check['visualization']['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN))>Tampilkan diagram kecil</option></select>
            <div class="admin-material-check-grid"><div><label class="form-label">Caption visualisasi</label><input class="form-control" name="sections[{{ $index }}][learning_check][visualization][caption]" value="{{ $check['visualization']['caption'] ?? '' }}" maxlength="300">@error('sections.'.$index.'.learning_check.visualization')<p class="text-danger small">{{ $message }}</p>@enderror</div><div><label class="form-label">Tata letak</label><select class="form-select" name="sections[{{ $index }}][learning_check][visualization][layout]"><option value="compare" @selected(($check['visualization']['layout'] ?? 'compare') === 'compare')>Perbandingan</option><option value="flow" @selected(($check['visualization']['layout'] ?? '') === 'flow')>Alur</option></select></div></div>
            <div class="admin-material-nested-heading"><h3>Item visualisasi</h3><button type="button" class="btn btn-sm btn-outline-secondary" data-add-visual-item>Tambah item</button></div>
            <div data-visual-item-rows>
                @foreach ($visualRows as $itemIndex => $item)
                @include('admin.materials.visual-item-row', ['itemIndex' => $itemIndex, 'item' => $item, 'sectionIndex' => $index])
                @endforeach
            </div>
            <template data-visual-item-template>@include('admin.materials.visual-item-row', ['itemIndex' => '__VISUAL_INDEX__', 'item' => [], 'sectionIndex' => $index])</template>
        </details>
        <div class="admin-material-live-preview">
            <button type="button" class="btn btn-sm btn-outline-primary" data-preview-learning-check>Perbarui pratinjau aktivitas</button>
            <div class="oopy-material" data-check-preview-wrapper hidden><section class="material-section" data-material-section id="check-preview-{{ $index }}"><div data-learning-check-slot data-learning-check-config="{}"></div></section></div>
        </div>
    </details>
    <details class="admin-material-subpanel"><summary>Komponen bawaan yang dipertahankan</summary>
        <ul class="admin-material-components">
            <li>{{ !empty($section['live_codes']) ? count($section['live_codes']).' Live Coding beserta checker' : 'Tidak ada Live Coding di bagian ini' }}</li>
            <li>{{ !empty($section['tables']) ? count($section['tables']).' tabel' : 'Tidak ada tabel kompleks' }}</li>
            <li>{{ !empty($section['examples']) ? count($section['examples']).' contoh lanjutan' : 'Tidak ada contoh lanjutan' }}</li>
            <li>Anchor <code>{{ $section['id'] ?: 'dibuat saat menyimpan' }}</code> dan komponen kuis resmi tidak diedit dari panel ini.</li>
        </ul>
    </details>
    @if (empty($section['id']))
    <button class="btn btn-outline-danger" type="button" data-remove-new-section>Batalkan bagian baru</button>
    @else
    <p class="small text-muted mb-0">ID section <code>{{ $section['id'] }}</code> dipertahankan untuk anchor dan aktivitas yang sudah ada.</p>
    @endif
</fieldset>
