(() => {
    'use strict';

    const dialog = document.getElementById('oopy-logout-dialog');
    if (!dialog || typeof dialog.showModal !== 'function' || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';

    const cancel = dialog.querySelector('[data-logout-cancel]');
    const confirm = dialog.querySelector('[data-logout-confirm]');
    let form = null;
    let trigger = null;
    let submitting = false;

    document.addEventListener('submit', event => {
        if (!event.target.matches('form[data-logout-confirmation]')) return;
        if (dialog.open || submitting) {
            event.preventDefault();
            return;
        }

        form = event.target;
        trigger = event.submitter || form.querySelector('button[type="submit"]');
        try {
            dialog.showModal();
            event.preventDefault();
            cancel.focus();
        } catch {
            // Keep the original POST available when the browser cannot open a dialog.
            form = null;
            trigger = null;
        }
    });

    cancel.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        if (submitting || dialog.open) return;
        const accountToggle = trigger?.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
        if (accountToggle && window.bootstrap?.Dropdown) {
            window.bootstrap.Dropdown.getOrCreateInstance(accountToggle).show();
        }
        trigger?.focus();
        if (trigger && document.activeElement !== trigger) {
            const sidebar = trigger.closest('aside');
            const menu = [...document.querySelectorAll('[aria-controls]')]
                .find(button => button.getAttribute('aria-controls') === sidebar?.id);
            menu?.focus();
        }
        form = null;
        trigger = null;
    });

    document.addEventListener('keydown', event => {
        // Let Escape cancel the native dialog without also closing an underlying menu.
        if (dialog.open && event.key === 'Escape') event.stopImmediatePropagation();
    }, true);

    confirm.addEventListener('click', () => {
        if (!form || submitting) return;
        submitting = true;
        cancel.disabled = true;
        confirm.disabled = true;
        // Submit the original CSRF form once, bypassing this submit interceptor.
        HTMLFormElement.prototype.submit.call(form);
    });

    window.addEventListener('pageshow', () => {
        submitting = false;
        cancel.disabled = false;
        confirm.disabled = false;
        if (dialog.open) dialog.close();
    });
})();
