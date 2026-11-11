export function publicationPreview(types, minimum, maximum) {
    const count = types.length;
    const multipleChoice = types.filter(type => type === 'multiple_choice').length;
    const codeFill = types.filter(type => type === 'code_fill').length;
    return {
        count, multipleChoice, codeFill,
        weight: count > 0 ? 100 / count : null,
        valid: count >= minimum && count <= maximum && multipleChoice + codeFill === count,
    };
}

if (typeof document !== 'undefined') document.querySelectorAll('[data-question-publication]').forEach(form => {
    const find = name => form.querySelector(`[data-publication="${name}"]`);
    const minimum = Number(form.dataset.minimum);
    const maximum = Number(form.dataset.maximum);
    const format = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    function update() {
        const types = [...form.querySelectorAll('input[name="question_ids[]"]:checked')].map(input => input.dataset.questionType);
        const preview = publicationPreview(types, minimum, maximum);
        find('count').textContent = String(preview.count);
        find('multiple-choice').textContent = String(preview.multipleChoice);
        find('code-fill').textContent = String(preview.codeFill);
        find('weight').textContent = preview.weight === null ? 'Belum tersedia' : `${format.format(preview.weight)} poin`;
        find('notice').textContent = preview.valid
            ? `${preview.count} soal terpilih. Bobot setiap soal ${format.format(preview.weight)} poin.`
            : `Pilih ${minimum} sampai ${maximum} soal pilihan ganda atau isian kode untuk menerbitkan paket.`;
        find('notice').classList.toggle('text-danger', !preview.valid);
        form.querySelector('button[type="submit"]').disabled = !preview.valid;
    }
    form.addEventListener('change', update);
    update();
});
