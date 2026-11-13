<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigasi administrator">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand admin-desktop-brand"><img src="{{ asset('images/oopy-logo.png') }}" width="52" height="52" alt="">OOPy <span>Admin</span></a>
    <p class="admin-sidebar-label">Pengelolaan</p>
    <nav aria-label="Navigasi pengelolaan">
        @foreach([
            ['admin.dashboard', 'Dashboard', 'admin.dashboard', 'home'],
            ['admin.users.index', 'Manajemen Pengguna', 'admin.users.*', 'user'],
            ['admin.scores.index', 'Rekap Nilai', 'admin.scores.*,admin.evaluations.*', 'scores'],
            ['admin.questions.index', 'Bank Soal', 'admin.questions.*', 'menu'],
            ['admin.materials.index', 'Manajemen Materi', 'admin.materials.*', 'book']
        ] as [$route, $label, $pattern, $icon])
            @php($active = request()->routeIs(...explode(',', $pattern)))
            <a href="{{ route($route) }}" class="admin-nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif><x-dashboard-icon :name="$icon" /><span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <div class="admin-sidebar-footer">
        <a href="{{ route('home') }}" class="admin-nav-link"><x-dashboard-icon name="home" /><span>Kembali ke OOPy</span></a>
        <form method="POST" action="{{ route('logout') }}" data-logout-confirmation>@csrf<button type="submit" class="admin-nav-link admin-logout"><x-dashboard-icon name="logout" /><span>Logout</span></button></form>
    </div>
</aside>
