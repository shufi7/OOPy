'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const sections = document.getElementById('material-sections');
    const add = document.getElementById('add-material-section');
    const template = document.getElementById('material-section-template');
    if (!sections || !add || !template) return;
    let nextIndex = sections.querySelectorAll('[data-material-section-form]').length;
    add.addEventListener('click', () => {
        const orders = [...sections.querySelectorAll('input[type="number"]')].map(input => Number(input.value) || 0);
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).replaceAll('__ORDER__', String(Math.max(0, ...orders) + 1));
        sections.insertAdjacentHTML('beforeend', html);
        sections.lastElementChild.querySelector('input:not([type="hidden"])')?.focus();
    });
    sections.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-new-section]');
        if (remove) {
            remove.closest('[data-material-section-form]').remove();
            add.focus();
        }
    });
});
