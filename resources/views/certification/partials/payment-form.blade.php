<form method="POST" action="{{ route('certification.confirm') }}" data-simulation-form novalidate>
    @csrf
    <div class="certification-participant"><span>Nama pada sertifikat</span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div>
    <fieldset class="certification-methods"><legend>Metode simulasi</legend>
        @foreach (\App\Services\CertificateIssuanceService::METHODS as $value => $label)
            <label><input type="radio" name="payment_method" value="{{ $value }}" required @checked(old('payment_method', 'e_wallet') === $value)> {{ $label }}</label>
        @endforeach
    </fieldset>
    <dl class="certification-totals"><div><dt>Subtotal</dt><dd>Rp24.999</dd></div><div><dt>Biaya layanan</dt><dd>Rp0</dd></div><div><dt>Total simulasi</dt><dd>Rp24.999</dd></div></dl>
    <button type="button" class="certification-press" data-simulation-open>Ambil Sertifikat</button>
    <p class="certification-action-note">Anda akan diminta mengonfirmasi simulasi sebelum sertifikat diterbitkan.</p>
    <noscript><p>Aktifkan JavaScript untuk membuka dialog konfirmasi simulasi.</p></noscript>
    <dialog class="certification-dialog" data-simulation-dialog aria-labelledby="simulation-title" aria-describedby="simulation-description">
        <p class="certification-section-label">Konfirmasi penerbitan</p>
        <h2 id="simulation-title">Terbitkan sertifikat simulasi?</h2>
        <p id="simulation-description">Total Rp24.999 hanya disimulasikan. Tidak ada penagihan, pembayaran bank, atau transfer uang nyata. Anda akan mendapatkan satu sertifikat berlabel SIMULASI / DEMO.</p>
        <p>Nama peserta: <strong>{{ auth()->user()->name }}</strong>. Nama, program, nomor, tanggal, dan status sertifikat dapat dilihat melalui tautan verifikasi publik.</p>
        <label class="certification-consent"><input type="checkbox" name="simulation_confirmed" value="1" required> Saya memahami bahwa transaksi dan sertifikat ini adalah simulasi.</label>
        <div class="certification-actions"><button type="button" class="certification-secondary" data-simulation-cancel autofocus>Batal</button><button type="submit" class="certification-press" data-simulation-submit>Ya, Selesaikan Simulasi</button></div>
        <p role="status" data-simulation-status class="certification-action-note"></p>
    </dialog>
</form>
