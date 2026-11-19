<fieldset class="admin-material-option-row" data-option-row data-option-index="{{ $optionIndex }}">
    <legend>Pilihan {{ is_numeric($optionIndex) ? $optionIndex + 1 : 'baru' }}</legend>
    <div class="admin-material-option-grid">
        <div><label class="form-label">Teks pilihan</label><input class="form-control" name="sections[{{ $sectionIndex }}][learning_check][options][{{ $optionIndex }}][label]" value="{{ $option['label'] ?? '' }}" maxlength="400">@error('sections.'.$sectionIndex.'.learning_check.options.'.$optionIndex.'.label')<p class="text-danger small">{{ $message }}</p>@enderror</div>
        <div><label class="form-label">Jawaban benar?</label><select class="form-select" name="sections[{{ $sectionIndex }}][learning_check][options][{{ $optionIndex }}][correct]"><option value="0" @selected(!filter_var($option['correct'] ?? false, FILTER_VALIDATE_BOOLEAN))>Tidak</option><option value="1" @selected(filter_var($option['correct'] ?? false, FILTER_VALIDATE_BOOLEAN))>Ya, ini benar</option></select></div>
        <div><label class="form-label">Feedback untuk pilihan ini</label><textarea class="form-control" name="sections[{{ $sectionIndex }}][learning_check][options][{{ $optionIndex }}][feedback]" rows="2" maxlength="1000">{{ $option['feedback'] ?? '' }}</textarea>@error('sections.'.$sectionIndex.'.learning_check.options.'.$optionIndex.'.feedback')<p class="text-danger small">{{ $message }}</p>@enderror</div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-move-option-up>Naik</button> <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-move-option-down>Turun</button> <button type="button" class="btn btn-sm btn-outline-danger mt-2" data-remove-option>Hapus pilihan</button>
</fieldset>
