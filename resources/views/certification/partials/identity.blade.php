<section class="certification-identity" id="nama-sertifikat" aria-labelledby="name-title" data-certificate-identity>
    <div>
        <h2 id="name-title">Konfirmasi Nama Sertifikat</h2>
        @if (session('certificate_status'))
            <p class="certification-success" role="status">{{ session('certificate_status') }}</p>
        @endif
    </div>
    <div>
        <div class="certification-participant"><span>Nama peserta</span><strong data-participant-name>{{ $identity['name'] }}</strong></div>
        <p class="certification-action-note" data-name-locked @if (! $identity['locked']) hidden @endif>
            {{ $identity['pre_upgrade_order'] ? 'Pembayaran dimulai sebelum fitur konfirmasi nama tersedia. Sistem memakai nama akun saat penerbitan.' : 'Nama terkunci selama pembayaran ini diproses.' }}
        </p>
        @if ($identity['available'])
            <div data-name-controls @if ($identity['locked']) hidden @endif>
                @if ($identity['confirmed'])
                    <p class="certification-state is-complete">Nama sudah dikonfirmasi</p>
                @elseif ($identity['valid'])
                    <form action="{{ route('certification.name.confirm') }}" method="post" data-identity-form>
                        @csrf
                        <input type="hidden" name="confirmed_name" value="{{ $identity['name'] }}">
                        <label class="certification-consent" for="name-confirmed">
                            <input type="checkbox" id="name-confirmed" name="name_confirmed" value="1" required>
                            Saya memastikan bahwa nama di atas sudah benar dan memahami bahwa nama akan dikunci setelah proses penerbitan sertifikat.
                        </label>
                        <button type="submit" class="certification-press" data-busy-label="Mengonfirmasi…">Konfirmasi Nama</button>
                    </form>
                @else
                    <p class="certification-error" role="status">Perbarui nama sebelum mengonfirmasinya.</p>
                @endif
                <details class="certification-edit-name" data-name-edit @if ($errors->certificateName->any()) open @endif>
                    <summary>Ganti Nama</summary>
                    <form action="{{ route('certification.name.update') }}" method="post" data-identity-form>
                        @csrf
                        <p>Untuk menjaga keamanan identitas sertifikat Anda, masukkan password akun OOPy untuk mengonfirmasi perubahan nama.</p>
                        <label for="participant-name">Nama lengkap baru</label>
                        <input id="participant-name" name="participant_name" type="text" autocomplete="name" maxlength="100" required
                            value="{{ old('participant_name', $identity['name']) }}" @if ($errors->certificateName->has('participant_name')) aria-invalid="true" @endif>
                        <label for="certificate-current-password">Password akun OOPy saat ini</label>
                        <div class="certification-password-field">
                            <input id="certificate-current-password" name="password" type="password" autocomplete="current-password" required
                                @if ($errors->certificateName->has('password')) aria-invalid="true" @endif>
                            <button type="button" class="certification-secondary" data-toggle-password="certificate-current-password">Tampilkan password</button>
                        </div>
                        <div class="certification-actions">
                            <button type="button" class="certification-secondary" data-cancel-name>Batal</button>
                            <button type="submit" class="certification-press" data-busy-label="Menyimpan…">Simpan Perubahan</button>
                        </div>
                    </form>
                </details>
                @include('certification.partials.google-password')
            </div>
        @else
            <p class="certification-action-note">Konfirmasi nama sertifikat sedang tidak tersedia.</p>
        @endif
    </div>
</section>
