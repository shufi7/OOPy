<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigasi administrator">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand admin-desktop-brand"><img src="{{ asset('images/oopy-logo.png') }}" width="52" height="52" alt="">OOPy <span>Admin</span></a>
    <p class="admin-sidebar-label">Pengelolaan</p>
    <nav>
        @foreach([
            ['admin.dashboard', 'Dashboard', 'admin.dashboard'],
            ['admin.users.index', 'Manajemen Pengguna', 'admin.users.*'],
            ['admin.scores.index', 'Rekap Nilai', 'admin.scores.*,admin.evaluations.*'],
            ['admin.questions.index', 'Bank Soal', 'admin.questions.*'],
            ['admin.materials.index', 'Manajemen Materi', 'admin.materials.*']
        ] as [$route, $label, $pattern])
            @php($active = request()->routeIs(...explode(',', $pattern)))
            <a href="{{ route($route) }}" class="admin-nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="admin-sidebar-footer">
        <a href="{{ route('home') }}" class="admin-nav-link">Kembali ke OOPy</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="admin-nav-link">Logout</button></form>
    </div>
</aside>
