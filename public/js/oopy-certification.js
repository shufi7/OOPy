(() => {
    'use strict';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    document.querySelectorAll('[data-midtrans-payment]').forEach(root => {
        const feedback = root.querySelector('[data-payment-feedback]');
        const label = root.querySelector('[data-payment-label]');
        const check = root.querySelector('[data-payment-check]');
        const open = root.querySelector('[data-payment-open]');
        const dialog = root.querySelector('[data-payment-dialog]');
        const confirm = root.querySelector('[data-payment-confirm]');
        const cancel = root.querySelector('[data-payment-cancel]');
        const consent = root.querySelector('[data-payment-consent]');
        const dialogMessage = root.querySelector('[data-payment-dialog-message]');
        const redirect = root.querySelector('[data-payment-redirect]');
        const initiallyReady = root.dataset.currentReady === '1';
        let checking = false;
        let creating = false;
        const request = async (url, data = {}) => {
            const response = await fetch(url, { method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(data) });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (response.status === 419 || response.status === 401) throw new Error('Sesi berakhir. Muat ulang halaman lalu masuk kembali.');
                if (response.status === 429) throw new Error('Terlalu banyak percobaan. Tunggu sebentar sebelum mencoba kembali.');
                throw new Error(body.errors ? Object.values(body.errors).flat().join(' ') : (body.message || 'Pembayaran Sandbox belum dapat diproses. Coba lagi.'));
            }
            return body;
        };
        const apply = payment => {
            root.dataset.statusUrl = payment.status_url;
            check.disabled = false;
            if (label) label.textContent = payment.label;
            feedback.textContent = payment.message;
            if (open) open.textContent = ['deny', 'cancel', 'expire', 'failure', 'api_error', 'expired_unselected'].includes(payment.status) ? 'Coba Lagi' : 'Bayar dengan QRIS';
            if (payment.ready !== initiallyReady) {
                // A fragment-only navigation would leave the old payment panel on screen.
                window.history.replaceState(null, '', '/sertifikasi#sertifikat');
                window.location.reload();
            }
        };
        const refresh = async () => {
            if (checking || !root.dataset.statusUrl) return;
            checking = true;
            check.disabled = true;
            feedback.textContent = 'Memeriksa status langsung ke server Midtrans Sandbox…';
            try { apply((await request(root.dataset.statusUrl)).payment); }
            catch (error) { feedback.textContent = error.message; }
            finally { checking = false; check.disabled = false; }
        };
        check?.addEventListener('click', refresh);
        open?.addEventListener('click', () => { dialogMessage.textContent = ''; dialog.showModal(); });
        cancel?.addEventListener('click', () => { dialog.close(); open.focus(); });
        dialog?.addEventListener('cancel', event => { if (creating) event.preventDefault(); });
        confirm?.addEventListener('click', async () => {
            if (creating) return;
            if (!consent.checked) { dialogMessage.textContent = 'Centang persetujuan Sandbox sebelum melanjutkan.'; consent.focus(); return; }
            creating = true;
            confirm.disabled = true; cancel.disabled = true; open.disabled = true;
            root.setAttribute('aria-busy', 'true');
            dialogMessage.textContent = 'Menyiapkan pesanan Rp24.999 di Midtrans Sandbox…';
            try {
                const result = await request(root.dataset.storeUrl, { sandbox_confirmed: true });
                apply(result.payment);
                dialog.close();
                if (!result.payment.ready) {
                    const target = new URL(result.redirect_url);
                    if (target.origin !== 'https://app.sandbox.midtrans.com' || !target.pathname.startsWith('/snap/')) throw new Error('Alamat pembayaran Sandbox tidak valid.');
                    redirect.href = target.href; redirect.hidden = false;
                    feedback.textContent = 'Selesaikan pembayaran pada Snap Sandbox. Jika popup tidak terbuka, gunakan tautan halaman pembayaran.';
                    if (window.snap?.pay) {
                        // Every callback only triggers server verification, never local paid state.
                        window.snap.pay(result.snap_token, { uiMode: 'qr', onSuccess: refresh, onPending: refresh, onError: refresh, onClose: refresh });
                    } else { window.location.assign(target.href); }
                }
            } catch (error) { dialogMessage.textContent = error.message; feedback.textContent = error.message; }
            finally {
                creating = false; confirm.disabled = false; cancel.disabled = false; open.disabled = false;
                root.removeAttribute('aria-busy');
            }
        });
    });
    document.querySelectorAll('[data-copy-verification]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = document.querySelector('[data-copy-status]');
            try { await navigator.clipboard.writeText(button.dataset.copyVerification); status.textContent = 'Tautan verifikasi disalin.'; }
            catch { status.textContent = `Penyalinan otomatis tidak tersedia. Salin tautan ini: ${button.dataset.copyVerification}`; }
        });
    });
})();
