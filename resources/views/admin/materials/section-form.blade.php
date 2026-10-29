<fieldset class="border rounded p-3 mb-3" data-material-section-form>
    <legend class="float-none w-auto px-2 fs-5">Bagian <span data-section-number>{{ is_numeric($index) ? $index + 1 : 'baru' }}</span></legend>
    <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section['id'] ?? '' }}">
    @error('sections.'.$index.'.id')<p class="text-danger">{{ $message }}</p>@enderror
    <div class="row g-3 mb-3">
        <div class="col-sm-3">
            <label class="form-label" for="section-{{ $index }}-order">Urutan</label>
            <input class="form-control" type="number" min="1" max="999" required id="section-{{ $index }}-order" name="sections[{{ $index }}][order]" value="{{ $section['order'] }}">
            @error('sections.'.$index.'.order')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
        <div class="col-sm-9">
            <label class="form-label" for="section-{{ $index }}-title">Judul bagian</label>
            <input class="form-control" required maxlength="255" id="section-{{ $index }}-title" name="sections[{{ $index }}][title]" value="{{ $section['title'] }}">
            @error('sections.'.$index.'.title')<p class="text-danger">{{ $message }}</p>@enderror
        </div>
    </div>
    @foreach (['paragraphs' => ['Paragraf penjelasan', 5, 30000], 'code' => ['Contoh kode Python', 7, 30000], 'output' => ['Output contoh', 3, 15000], 'tip' => ['Catatan belajar', 2, 5000]] as $field => [$label, $rows, $max])
    <div class="mb-3">
        <label class="form-label" for="section-{{ $index }}-{{ $field }}">{{ $label }}</label>
        <textarea class="form-control {{ in_array($field, ['code', 'output']) ? 'font-monospace' : '' }}" id="section-{{ $index }}-{{ $field }}" name="sections[{{ $index }}][{{ $field }}]" rows="{{ $rows }}" maxlength="{{ $max }}" @if (in_array($field, ['code', 'output'])) spellcheck="false" autocapitalize="off" @endif>{{ $section[$field] ?? '' }}</textarea>
        @error('sections.'.$index.'.'.$field)<p class="text-danger">{{ $message }}</p>@enderror
    </div>
    @endforeach
    @if (empty($section['id']))
    <button class="btn btn-outline-secondary" type="button" data-remove-new-section>Batalkan bagian baru</button>
    @else
    <p class="small mb-0">Bagian lama dipertahankan agar anchor, contoh lanjutan, dan konfigurasi latihan tetap aman.</p>
    @endif
</fieldset>
