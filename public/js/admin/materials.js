'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('material-editor');
    const sections = document.getElementById('material-sections');
    const addSection = document.getElementById('add-material-section');
    const sectionTemplate = document.getElementById('material-section-template');
    if (!sections || !addSection || !sectionTemplate) return;

    let nextSectionIndex = sections.querySelectorAll('[data-material-section-form]').length;
    const nextRowIndex = (rows, dataName) => {
        const values = [...rows.children].map(row => Number(row.dataset[dataName])).filter(Number.isFinite);
        return Math.max(-1, ...values) + 1;
    };
    sections.querySelectorAll('[data-material-section-form]').forEach(card => {
        if (card.querySelectorAll('[data-media-row]').length >= 2) card.querySelector('[data-add-media]')?.setAttribute('disabled', 'disabled');
        if (card.querySelectorAll('[data-option-row]').length >= 6) card.querySelector('[data-add-option]')?.setAttribute('disabled', 'disabled');
        if (card.querySelectorAll('[data-visual-item-row]').length >= 6) card.querySelector('[data-add-visual-item]')?.setAttribute('disabled', 'disabled');
    });
    const addFromTemplate = (card, templateName, placeholder, dataName, rowsName) => {
        const template = card.querySelector(`[${templateName}]`);
        const rows = card.querySelector(`[${rowsName}]`);
        if (!template || !rows) return;
        const max = templateName === 'data-media-template' ? 2 : 6;
        if (rows.children.length >= max) return;
        const index = nextRowIndex(rows, dataName);
        rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll(placeholder, String(index)));
        const addSelector = { 'data-media-template': '[data-add-media]', 'data-option-template': '[data-add-option]', 'data-visual-item-template': '[data-add-visual-item]' }[templateName];
        const addButton = card.querySelector(addSelector);
        if (addButton) addButton.disabled = rows.children.length >= max;
        rows.lastElementChild?.querySelector('input,select,textarea')?.focus();
    };
    const moveRow = (row, direction, dataName, segment) => {
        const parent = row?.parentElement;
        if (!parent) return;
        const sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling) return;
        if (direction < 0) parent.insertBefore(row, sibling);
        else parent.insertBefore(sibling, row);
        [...parent.children].forEach((item, index) => {
            item.dataset[dataName] = String(index);
            item.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(new RegExp(`\\[${segment}\\]\\[\\d+\\]`), `[${segment}][${index}]`);
            });
        });
        row.querySelector('input,select,textarea,button')?.focus();
    };

    addSection.addEventListener('click', () => {
        const orders = [...sections.querySelectorAll('input[type="number"]')].map(input => Number(input.value) || 0);
        const index = nextSectionIndex++;
        const html = sectionTemplate.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__ORDER__', String(Math.max(0, ...orders) + 1));
        sections.insertAdjacentHTML('beforeend', html);
        const nav = document.querySelector('.admin-material-section-nav ol');
        const title = sections.lastElementChild.querySelector('[name$="[title]"]')?.value || `Bagian ${index + 1}`;
        if (nav) {
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = `#material-section-${index}`;
            link.textContent = `${index + 1}. ${title}`;
            item.append(link);
            nav.append(item);
        }
        sections.lastElementChild.querySelector('input:not([type="hidden"])')?.focus();
    });

    sections.addEventListener('click', event => {
        const target = event.target;
        const card = target.closest('[data-material-section-form]');
        if (target.closest('[data-remove-new-section]')) {
            card?.closest('[id^="material-section-"]')?.remove();
            addSection.focus();
            return;
        }
        if (!card) return;
        if (target.closest('[data-add-media]')) addFromTemplate(card, 'data-media-template', '__MEDIA_INDEX__', 'mediaIndex', 'data-media-rows');
        if (target.closest('[data-remove-media]')) {
            target.closest('[data-media-row]')?.remove();
            card.querySelector('[data-add-media]')?.removeAttribute('disabled');
        }
        if (target.closest('[data-move-media-up]')) moveRow(target.closest('[data-media-row]'), -1, 'mediaIndex', 'media');
        if (target.closest('[data-move-media-down]')) moveRow(target.closest('[data-media-row]'), 1, 'mediaIndex', 'media');
        if (target.closest('[data-add-option]')) addFromTemplate(card, 'data-option-template', '__OPTION_INDEX__', 'optionIndex', 'data-option-rows');
        if (target.closest('[data-remove-option]')) {
            target.closest('[data-option-row]')?.remove();
            card.querySelector('[data-add-option]')?.removeAttribute('disabled');
        }
        if (target.closest('[data-move-option-up]')) moveRow(target.closest('[data-option-row]'), -1, 'optionIndex', 'options');
        if (target.closest('[data-move-option-down]')) moveRow(target.closest('[data-option-row]'), 1, 'optionIndex', 'options');
        if (target.closest('[data-add-visual-item]')) addFromTemplate(card, 'data-visual-item-template', '__VISUAL_INDEX__', 'visualItemIndex', 'data-visual-item-rows');
        if (target.closest('[data-remove-visual-item]')) {
            target.closest('[data-visual-item-row]')?.remove();
            card.querySelector('[data-add-visual-item]')?.removeAttribute('disabled');
        }
        if (target.closest('[data-move-visual-up]')) moveRow(target.closest('[data-visual-item-row]'), -1, 'visualItemIndex', 'items');
        if (target.closest('[data-move-visual-down]')) moveRow(target.closest('[data-visual-item-row]'), 1, 'visualItemIndex', 'items');
        if (target.closest('[data-preview-learning-check]')) previewCheck(card);
    });

    sections.addEventListener('change', event => {
        const input = event.target;
        if (input.matches('[data-media-src]')) {
            const preview = input.closest('[data-media-row]')?.querySelector('[data-media-thumbnail]');
            if (preview && input.value) {
                preview.src = `${window.location.origin}/${input.value}`;
                preview.hidden = false;
            } else if (preview) preview.hidden = true;
        }
        if (input.matches('input[type="file"]')) {
            const preview = input.closest('[data-media-row]')?.querySelector('[data-media-thumbnail]');
            if (preview && input.files?.[0]) {
                preview.src = URL.createObjectURL(input.files[0]);
                preview.hidden = false;
            }
        }
    });

    sections.addEventListener('input', event => {
        const titleInput = event.target.closest('input[name$="[title]"]');
        if (!titleInput) return;
        const wrapper = titleInput.closest('[id^="material-section-"]');
        const link = document.querySelector(`.admin-material-section-nav a[href="#${CSS.escape(wrapper?.id || '')}"]`);
        if (link) link.textContent = `${Number(titleInput.closest('[data-material-section-form]')?.querySelector('[data-section-number]')?.textContent) || ''}. ${titleInput.value || 'Bagian tanpa judul'}`;
    });

    if (form) {
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', event => {
            if (!dirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
    }
});

function previewCheck(card) {
    const read = suffix => card.querySelector(`[name$="[${suffix}]"]`);
    const enabled = read('learning_check][enabled]')?.value === '1';
    const wrapper = card.querySelector('[data-check-preview-wrapper]');
    const section = wrapper?.querySelector('[data-material-section]');
    if (!enabled || !wrapper || !section || !window.OopyLearningChecks?.render) {
        if (wrapper) wrapper.hidden = false;
        wrapper?.querySelector('[data-learning-check-slot]')?.replaceChildren(document.createTextNode('Aktifkan aktivitas untuk melihat pratinjau.'));
        return;
    }
    const options = [...card.querySelectorAll('[data-option-row]')].map(row => ({
        label: row.querySelector('input[name$="[label]"]')?.value || '',
        correct: row.querySelector('select[name$="[correct]"]')?.value === '1',
        feedback: row.querySelector('textarea[name$="[feedback]"]')?.value || '',
    }));
    const visualEnabled = card.querySelector('select[name$="[visualization][enabled]"]')?.value === '1';
    const visualization = visualEnabled ? {
        caption: card.querySelector('input[name$="[visualization][caption]"]')?.value || '',
        layout: card.querySelector('select[name$="[visualization][layout]"]')?.value || 'compare',
        items: [...card.querySelectorAll('[data-visual-item-row]')].map(row => ({
            label: row.querySelector('input[name$="[label]"]')?.value || '',
            code: row.querySelector('input[name$="[code]"]')?.value || '',
            detail: row.querySelector('textarea[name$="[detail]"]')?.value || '',
        })),
    } : null;
    const config = {
        kind: read('learning_check][kind]')?.value || 'Coba pahami',
        prompt: read('learning_check][prompt]')?.value || 'Pertanyaan aktivitas',
        code: read('learning_check][code]')?.value || '', options, visualization,
    };
    wrapper.hidden = false;
    section.querySelector('[data-learning-check-slot]')?.replaceChildren();
    window.OopyLearningChecks.render(section, config);
}
