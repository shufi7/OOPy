<header class="oopy-dashboard-topbar">
    <a class="oopy-dashboard-brand" href="{{ route('home') }}" aria-label="OOPy, kembali ke Beranda">
        <img src="{{ asset('images/oopy-logo.png') }}" width="44" height="44" alt="">
        <span>OOPy</span>
    </a>
    <div class="oopy-dashboard-topbar-main">
        <button class="oopy-dashboard-menu-toggle" type="button" aria-controls="oopyDashboardSidebar" aria-expanded="true" aria-label="Tutup menu dashboard" hidden><x-dashboard-icon name="menu" /></button>
        <span class="oopy-dashboard-topbar-title">Dashboard</span>
        <a class="oopy-dashboard-topbar-home" href="{{ route('home') }}">Beranda <span aria-hidden="true">↗</span></a>
    </div>
</header>

<aside class="oopy-dashboard-sidebar" id="oopyDashboardSidebar" aria-label="Menu dashboard">
    <div class="oopy-dashboard-welcome">
        <p>Selamat datang kembali,</p>
        <strong class="oopy-dashboard-greeting">{{ auth()->user()->name }}</strong>
        <span>Selamat belajar bersama OOPy.</span>
    </div>
    <nav class="oopy-dashboard-menu" aria-label="Navigasi dashboard">
        <a class="oopy-dashboard-menu-link" href="{{ route('materi.index') }}"><x-dashboard-icon name="book" /><span>Belajar</span></a>
        <a href="{{ route('dashboard') }}" class="oopy-dashboard-menu-link is-active" aria-current="page"><x-dashboard-icon name="home" /><span>Dashboard</span></a>
        <a class="oopy-dashboard-menu-link" href="#profil"><x-dashboard-icon name="user" /><span>Profil Saya</span></a>
        <a class="oopy-dashboard-menu-link" href="#informasi"><x-dashboard-icon name="info" /><span>Informasi</span></a>
    </nav>
    <div class="oopy-dashboard-sidebar-bottom">
        <form method="POST" action="{{ route('logout') }}" data-dashboard-logout>
            @csrf
            <button class="oopy-dashboard-logout" type="submit"><x-dashboard-icon name="logout" /><span>Keluar</span></button>
        </form>
    </div>
</aside>
<button class="oopy-dashboard-backdrop" type="button" aria-label="Tutup menu dashboard" hidden></button>
