<div data-midtrans-payment data-store-url="{{ route('midtrans.store') }}" data-status-url="{{ $payment['status_url'] ?? '' }}" data-current-ready="0"
    data-confirmed-name="{{ $identity['name'] }}" data-name-confirmed="{{ $identity['confirmed'] ? '1' : '0' }}">
    <dl class="certification-totals"><div><dt>Harga sertifikat</dt><dd>Rp24.999</dd></div><div><dt>Biaya layanan OOPy</dt><dd>Rp0</dd></div><div><dt>Total Sandbox</dt><dd>Rp24.999</dd></div></dl>
    <div class="certification-payment-state" role="status"><strong data-payment-label>{{ $payment['label'] ?? 'Belum Dibayar' }}</strong><p data-payment-feedback>{{ $payment['message'] ?? 'Sertifikat diterbitkan setelah pembayaran dikonfirmasi oleh server Midtrans Sandbox.' }}</p></div>
    <button type="button" class="certification-press" data-payment-open aria-describedby="payment-name-required" @disabled(! $identity['confirmed'] && ! $identity['locked'])>{{ in_array($payment['status'] ?? '', ['deny', 'cancel', 'expire', 'failure', 'api_error', 'expired_unselected'], true) ? 'Coba Lagi' : 'Bayar dengan QRIS' }}</button>
    <p class="certification-action-note" id="payment-name-required" data-payment-name-required @if ($identity['confirmed'] || $identity['locked']) hidden @endif>Konfirmasi nama sertifikat untuk membuka pembayaran.</p>
    <button type="button" class="certification-secondary" data-payment-check @disabled(! $payment)>Cek Status Pembayaran</button>
    <a class="certification-next-step" data-payment-redirect hidden target="_blank" rel="noopener noreferrer">Buka halaman pembayaran Sandbox</a>
    <p class="certification-action-note">QRIS mengikuti ketersediaan kanal merchant. Jika tidak muncul, pengelola perlu mengaktifkan GoPay/QRIS pada Snap Sandbox. Tidak ada QR buatan OOPy.</p>
    <noscript><p>Aktifkan JavaScript untuk membuka pembayaran Sandbox.</p></noscript>
    <dialog class="certification-dialog" data-payment-dialog aria-labelledby="payment-title" aria-describedby="payment-description">
        <h2 id="payment-title">Lanjut ke Midtrans Sandbox?</h2>
        <p id="payment-description">Pesanan sertifikat Rp24.999 akan dibuka di Snap Sandbox. Tidak ada pembayaran uang sungguhan. Jangan gunakan aplikasi bank nyata; gunakan simulator Sandbox.</p>
        <p>Nama peserta <strong>{{ $identity['name'] }}</strong>. Nama dan status sertifikat dapat dilihat melalui tautan verifikasi publik.</p>
        <label class="certification-consent"><input type="checkbox" data-payment-consent> Saya memahami bahwa ini pembayaran Sandbox dan sertifikat demo.</label>
        <div class="certification-actions"><button type="button" class="certification-secondary" data-payment-cancel autofocus>Batal</button><button type="button" class="certification-press" data-payment-confirm>Lanjut ke Sandbox</button></div>
        <p class="certification-action-note" data-payment-dialog-message role="status"></p>
    </dialog>
</div>
