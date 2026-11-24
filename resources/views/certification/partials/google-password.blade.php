@if ($googlePasswordReady)
    <form class="certification-google-password" id="password-oopy" action="{{ route('certification.password.store') }}" method="post" data-identity-form>
        @csrf
        <h3>Tetapkan Password OOPy</h3>
        <p>Akun Google terverifikasi. Buat password OOPy dengan minimal 8 karakter.</p>
        <label for="certificate-new-password">Password OOPy baru</label>
        <input id="certificate-new-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <label for="certificate-password-confirmation">Ulangi password OOPy</label>
        <input id="certificate-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <button type="submit" class="certification-press" data-busy-label="Menyimpan…">Simpan Password OOPy</button>
    </form>
@elseif ($googlePasswordAvailable)
    <form action="{{ route('certification.password.google') }}" method="post" data-identity-form>
        @csrf
        <button type="submit" class="certification-secondary" data-busy-label="Membuka Google…">Tetapkan password OOPy dengan Google</button>
    </form>
@endif
