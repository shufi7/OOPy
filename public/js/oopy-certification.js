(() => {
    'use strict';
    const form = document.querySelector('[data-simulation-form]');
    if (form) {
        const dialog = form.querySelector('[data-simulation-dialog]');
        const open = form.querySelector('[data-simulation-open]');
        const submit = form.querySelector('[data-simulation-submit]');
        const cancel = form.querySelector('[data-simulation-cancel]');
        const status = form.querySelector('[data-simulation-status]');
        let submitting = false;
        open.addEventListener('click', () => {
            if (!form.querySelector('[name="payment_method"]:checked')) return;
            dialog.showModal();
        });
        cancel.addEventListener('click', () => { dialog.close(); open.focus(); });
        dialog.addEventListener('cancel', event => {
            if (submitting) event.preventDefault();
        });
        form.addEventListener('submit', event => {
            if (submitting || !dialog.open) { event.preventDefault(); return; }
            if (!form.reportValidity()) { event.preventDefault(); return; }
            submitting = true;
            submit.disabled = true;
            cancel.disabled = true;
            form.setAttribute('aria-busy', 'true');
            status.textContent = 'Memeriksa persyaratan dan menerbitkan sertifikat simulasi…';
        });
    }
    document.querySelectorAll('[data-copy-verification]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = document.querySelector('[data-copy-status]');
            try {
                await navigator.clipboard.writeText(button.dataset.copyVerification);
                status.textContent = 'Tautan verifikasi disalin.';
            } catch {
                status.textContent = `Penyalinan otomatis tidak tersedia. Salin tautan ini: ${button.dataset.copyVerification}`;
            }
        });
    });
})();
