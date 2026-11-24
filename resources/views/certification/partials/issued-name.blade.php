<div id="nama-sertifikat" class="certification-issued-name">
    @if (session('certificate_status'))
        <p class="certification-success" role="status">{{ session('certificate_status') }}</p>
    @endif
    @if ($nameCorrectionAvailable)
        <details class="certification-edit-name" data-issued-name-edit
            @if ($errors->issuedCertificateName->any() || $errors->certificatePassword->any() || $errors->has('google_password') || $googlePasswordReady) open @endif>
            <summary>Periksa atau ganti nama sertifikat</summary>
            <form action="{{ route('certification.name.correct', $certificate->public_id) }}" method="post" data-identity-form>
                @csrf
                <input type="hidden" name="name_revision" value="{{ $certificate->currentNameRevision()?->id ?? 0 }}">
                <p>Periksa ejaan nama Anda. Nama yang dikoreksi akan tampil pada PDF dan halaman verifikasi.</p>
                @if ($errors->issuedCertificateName->any())
                    <ul class="certification-error" role="alert">
                        @foreach ($errors->issuedCertificateName->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <label for="corrected-participant-name">Nama pada sertifikat</label>
                <input id="corrected-participant-name" name="participant_name" type="text" autocomplete="name" maxlength="100" required
                    value="{{ old('participant_name', $certificate->currentParticipantName()) }}" @if ($errors->issuedCertificateName->has('participant_name')) aria-invalid="true" @endif>
                <label for="correction-current-password">Password akun OOPy</label>
                <div class="certification-password-field">
                    <input id="correction-current-password" name="password" type="password" autocomplete="current-password" required
                        @if ($errors->issuedCertificateName->has('password')) aria-invalid="true" @endif>
                    <button type="button" class="certification-secondary" data-toggle-password="correction-current-password">Tampilkan password</button>
                </div>
                <label class="certification-consent" for="corrected-name-confirmed">
                    <input type="checkbox" id="corrected-name-confirmed" name="name_confirmed" value="1" required>
                    Saya memastikan nama pada formulir ini sudah benar.
                </label>
                <div class="certification-actions">
                    <button type="button" class="certification-secondary" data-cancel-name>Batal</button>
                    <button type="submit" class="certification-press" data-busy-label="Menyimpan…">Simpan Nama Sertifikat</button>
                </div>
            </form>
            @include('certification.partials.google-password')
        </details>
    @else
        <p class="certification-action-note">Koreksi nama sertifikat belum tersedia.</p>
    @endif
</div>
