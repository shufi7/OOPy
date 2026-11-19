<fieldset class="admin-material-option-row" data-visual-item-row data-visual-item-index="{{ $itemIndex }}">
    <legend>Item visual {{ is_numeric($itemIndex) ? $itemIndex + 1 : 'baru' }}</legend>
    <div class="admin-material-option-grid">
        <div><label class="form-label">Label</label><input class="form-control" name="sections[{{ $sectionIndex }}][learning_check][visualization][items][{{ $itemIndex }}][label]" value="{{ $item['label'] ?? '' }}" maxlength="200">@error('sections.'.$sectionIndex.'.learning_check.visualization.items.'.$itemIndex.'.label')<p class="text-danger small">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Teks kode / konsep</label><input class="form-control" name="sections[{{ $sectionIndex }}][learning_check][visualization][items][{{ $itemIndex }}][code]" value="{{ $item['code'] ?? '' }}" maxlength="300">@error('sections.'.$sectionIndex.'.learning_check.visualization.items.'.$itemIndex.'.code')<p class="text-danger small">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Penjelasan</label><textarea class="form-control" name="sections[{{ $sectionIndex }}][learning_check][visualization][items][{{ $itemIndex }}][detail]" rows="2" maxlength="500">{{ $item['detail'] ?? '' }}</textarea>@error('sections.'.$sectionIndex.'.learning_check.visualization.items.'.$itemIndex.'.detail')<p class="text-danger small">{{ $message }}</p>@enderror</div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-move-visual-up>Naik</button> <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-move-visual-down>Turun</button> <button type="button" class="btn btn-sm btn-outline-danger mt-2" data-remove-visual-item>Hapus item</button>
</fieldset>
