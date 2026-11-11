@once
    <dialog class="oopy-logout-dialog" id="oopy-logout-dialog" aria-labelledby="oopy-logout-title" aria-describedby="oopy-logout-description">
        <h2 id="oopy-logout-title">Yakin ingin keluar dari OOPy?</h2>
        <p id="oopy-logout-description">Anda akan keluar dari akun OOPy. Pastikan pekerjaan yang belum disimpan sudah diselesaikan.</p>
        <div class="oopy-logout-actions">
            <button type="button" class="oopy-logout-button" data-logout-cancel autofocus>Batal</button>
            <button type="button" class="oopy-logout-button oopy-logout-confirm" data-logout-confirm>Ya, Logout</button>
        </div>
    </dialog>
    <script src="{{ asset('js/oopy-logout.js') }}" defer></script>
@endonce
